#!/usr/bin/env python3
"""
MQTT Bridge for TCP Lighting Web Interface.

Persistent daemon that:
- Subscribes to MQTT command topics (wildcard-based)
- Polls the PHP API for device state and publishes diffs
- Publishes Home Assistant MQTT discovery configs
"""

import json
import logging
import os
import signal
import sys
import threading
import time

import paho.mqtt.client as mqtt
import requests

log = logging.getLogger("mqtt-bridge")


API_PATH = "/api.php"


class Config:
    """Read and validate configuration from environment variables."""

    def __init__(self):
        self.broker = os.environ.get("MQTT_BROKER", "")
        self.port = int(os.environ.get("MQTT_PORT", "1883"))
        self.username = os.environ.get("MQTT_USERNAME", "")
        self.password = os.environ.get("MQTT_PASSWORD", "")
        self.client_id = os.environ.get("MQTT_CLIENT_ID", "tcp-bridge")
        self.prefix = os.environ.get("MQTT_PREFIX", "light")
        self.control = os.environ.get("MQTT_CONTROL", "tcp")
        self.enable_ha_discovery = os.environ.get("ENABLE_HA_DISCOVERY", "1") == "1"
        self.hass_topic_id = os.environ.get("HASS_TOPIC_ID", "homeassistant")
        self.hass_online_topic = os.environ.get("HASS_ONLINE_TOPIC", "hass/status")
        self.api_base_url = os.environ.get("API_BASE_URL", "")
        self.poll_interval = int(os.environ.get("POLL_INTERVAL", "5"))
        self.bridge_ip = os.environ.get("BRIDGE_IP", "")
        self.bridge_ssh_user = os.environ.get("BRIDGE_SSH_USER", "root")
        self.bridge_ssh_password = os.environ.get("BRIDGE_SSH_PASSWORD", "")
        self.reboot_threshold = int(os.environ.get("BRIDGE_REBOOT_THRESHOLD", "3"))
        self.reboot_cooldown = int(os.environ.get("BRIDGE_REBOOT_COOLDOWN", "300"))
        self.reboot_max_retries = int(os.environ.get("BRIDGE_REBOOT_MAX_RETRIES", "3"))

        missing = []
        if not self.broker:
            missing.append("MQTT_BROKER")
        if not self.api_base_url:
            missing.append("API_BASE_URL")
        if missing:
            raise ValueError(f"Missing required environment variables: {', '.join(missing)}")


class StateDiffer:
    """Track previous device states and return only changes."""

    def __init__(self):
        self._prev = {}  # keyed by device id

    def diff(self, devices):
        """Return list of devices whose state/level/online changed since last call.

        On first call, returns all devices (seeds MQTT).
        """
        changed = []
        for dev in devices:
            did = dev["id"]
            key = (str(dev.get("state", "")), str(dev.get("level", "")), str(dev.get("online", "")))
            if self._prev.get(did) != key:
                self._prev[did] = key
                changed.append(dev)
        return changed


class BridgeRebootManager:
    """Manages auto-reboot of the physical TCP bridge after consecutive failures."""

    def __init__(self, config):
        self.cfg = config
        self._consecutive_failures = 0
        self._last_reboot_time = 0.0
        self._reboot_count = 0

    def record_failure(self):
        """Record a poll failure."""
        self._consecutive_failures += 1

    def reset_failures(self):
        """Reset the consecutive failure counter and reboot count on a successful poll."""
        self._consecutive_failures = 0
        self._reboot_count = 0

    def maybe_reboot(self, auto_reboot_enabled):
        """Check if conditions are met and reboot the bridge if so."""
        if not self.cfg.bridge_ssh_password or not self.cfg.bridge_ip:
            return  # Feature disabled

        if not auto_reboot_enabled:
            return  # Disabled via web UI

        if self._consecutive_failures < self.cfg.reboot_threshold:
            return  # Not enough failures yet

        if self._reboot_count >= self.cfg.reboot_max_retries:
            if self._reboot_count == self.cfg.reboot_max_retries:
                log.error(
                    "Bridge reboot limit reached (%d/%d). Giving up until next successful poll.",
                    self._reboot_count, self.cfg.reboot_max_retries,
                )
                self._reboot_count += 1  # Only log once
            return

        now = time.time()
        elapsed = now - self._last_reboot_time
        if elapsed < self.cfg.reboot_cooldown:
            log.warning(
                "Bridge reboot on cooldown (%ds remaining)",
                int(self.cfg.reboot_cooldown - elapsed),
            )
            return

        self._reboot()

    def _reboot(self):
        """SSH into the physical TCP bridge and reboot it."""
        import paramiko  # lazy import — only needed when rebooting

        log.warning(
            "Rebooting TCP bridge at %s after %d consecutive failures",
            self.cfg.bridge_ip, self._consecutive_failures,
        )
        try:
            ssh = paramiko.SSHClient()
            ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            ssh.connect(
                self.cfg.bridge_ip,
                username=self.cfg.bridge_ssh_user,
                password=self.cfg.bridge_ssh_password,
                timeout=10,
            )
            ssh.exec_command("/sbin/reboot")
            ssh.close()
            self._last_reboot_time = time.time()
            self._consecutive_failures = 0
            self._reboot_count += 1
            log.warning("Reboot command sent to TCP bridge (%d/%d)",
                        self._reboot_count, self.cfg.reboot_max_retries)
        except Exception as e:
            log.error("Failed to SSH reboot bridge: %s", e)


class MQTTBridge:
    """Core MQTT bridge daemon."""

    def __init__(self, config):
        self.cfg = config
        self.differ = StateDiffer()
        self._poll_event = threading.Event()
        self._stop_event = threading.Event()
        self._state_cache = None  # latest full state from API
        self._lock = threading.Lock()
        self._reboot_mgr = BridgeRebootManager(config)

        self.client = mqtt.Client(client_id=config.client_id, clean_session=True)
        if config.username:
            self.client.username_pw_set(config.username, config.password)

        # LWT: announce offline on ungraceful disconnect
        lwt_topic = f"control/{config.control}/status"
        self.client.will_set(lwt_topic, "offline", qos=0, retain=True)

        self.client.on_connect = self._on_connect
        self.client.on_message = self._on_message

    def start(self):
        """Wait for API, connect MQTT, start poll thread, run loop."""
        self._wait_for_api()
        log.info("Connecting to MQTT broker %s:%d", self.cfg.broker, self.cfg.port)
        self.client.connect(self.cfg.broker, self.cfg.port, keepalive=60)

        poll_thread = threading.Thread(target=self._poll_loop, daemon=True)
        poll_thread.start()

        # Publish bridge online status
        self.client.publish(f"control/{self.cfg.control}/status", "online", qos=0, retain=True)

        self.client.loop_forever()

    def stop(self):
        """Graceful shutdown."""
        log.info("Shutting down bridge")
        self._stop_event.set()
        self._poll_event.set()  # wake poll thread
        self.client.publish(f"control/{self.cfg.control}/status", "offline", qos=0, retain=True)
        self.client.disconnect()

    @staticmethod
    def _normalize_name(name):
        """Replace spaces with underscores for MQTT topic segments."""
        return name.replace(" ", "_")

    def _build_topic_base(self, room_name, dev_name, did):
        """Build the MQTT topic base path for a device."""
        return f"{self.cfg.prefix}/{self._normalize_name(room_name)}/{self._normalize_name(dev_name)}/{did}"

    def _get_cached_state(self):
        """Return the cached state under lock."""
        with self._lock:
            return self._state_cache

    def _wait_for_api(self):
        """Wait for the PHP API to become reachable with exponential backoff."""
        url = f"{self.cfg.api_base_url}{API_PATH}?fx=getState"
        delay = 2
        max_delay = 30
        while not self._stop_event.is_set():
            try:
                r = requests.get(url, timeout=5)
                if r.status_code == 200:
                    log.info("API is ready")
                    return
            except requests.RequestException:
                pass
            log.info("Waiting for API (%ds)...", delay)
            time.sleep(delay)
            delay = min(delay * 2, max_delay)

    def _on_connect(self, client, userdata, flags, rc):
        if rc != 0:
            log.error("MQTT connect failed with code %d", rc)
            return
        log.info("MQTT connected (rc=%d)", rc)

        p = self.cfg.prefix
        # Device command topics: light/+/+/+/switch and light/+/+/+/brightness/set
        client.subscribe(f"{p}/+/+/+/switch")
        client.subscribe(f"{p}/+/+/+/brightness/set")
        # Room/scene command topics: light/+/+/switch and light/+/+/brightness/set
        client.subscribe(f"{p}/+/+/switch")
        client.subscribe(f"{p}/+/+/brightness/set")
        # Control topic
        client.subscribe(f"control/{self.cfg.control}")
        # Home Assistant online topic
        client.subscribe(self.cfg.hass_online_topic)

        # Force full state publish on connect
        self.differ = StateDiffer()
        self._poll_event.set()

    def _on_message(self, client, userdata, msg):
        topic = msg.topic
        payload = msg.payload.decode("utf-8", errors="replace").strip()
        log.debug("Message: %s = %s", topic, payload)

        # Control topic
        if topic == f"control/{self.cfg.control}":
            self._handle_control(payload)
            return

        # Home Assistant online
        if topic == self.cfg.hass_online_topic:
            if payload == "online":
                log.info("Home Assistant online, re-publishing discovery")
                self._publish_discovery()
                # Also force full state republish
                self.differ = StateDiffer()
                self._poll_event.set()
            return

        # Command topics under the prefix
        parts = topic.split("/")
        p = self.cfg.prefix

        if parts[0] != p:
            return

        if len(parts) == 5 and parts[4] == "switch":
            # Device toggle: light/{room}/{device}/{did}/switch
            did = parts[3]
            self._call_api("toggle", "device", did, payload)
        elif len(parts) == 6 and parts[4] == "brightness" and parts[5] == "set":
            # Device brightness: light/{room}/{device}/{did}/brightness/set
            did = parts[3]
            self._call_api("dim", "device", did, payload)
        elif len(parts) == 4 and parts[3] == "switch":
            # Room or scene toggle: light/{name}/{id}/switch
            uid = parts[2]
            uid_type = self._classify_uid(uid)
            if uid_type == "scene":
                self._call_api("scene", payload, uid, "")
            else:
                self._call_api("toggle", "room", uid, payload)
        elif len(parts) == 5 and parts[3] == "brightness" and parts[4] == "set":
            # Room brightness: light/{name}/{id}/brightness/set
            uid = parts[2]
            self._call_api("dim", "room", uid, payload)

        # Trigger immediate re-poll after any command
        self._poll_event.set()

    def _handle_control(self, payload):
        if payload == "QUIT":
            log.info("QUIT received, disconnecting")
            self.stop()
        elif payload == "REBOOT":
            log.info("REBOOT received, exiting (Docker will restart)")
            self.stop()
            sys.exit(0)

    def _classify_uid(self, uid):
        """Determine if a UID belongs to a room or scene using cached state."""
        state = self._get_cached_state()
        if not state:
            return "room"  # default guess

        for room in state.get("rooms", []):
            if str(room.get("room_id")) == str(uid):
                return "room"
        for scene in state.get("scenes", []):
            if str(scene.get("id")) == str(uid):
                return "scene"
        return "room"  # fallback

    def _call_api(self, fx, type_or_val, uid, val):
        """Call the PHP API for a command."""
        url = self.cfg.api_base_url + API_PATH
        if fx == "scene":
            # Scene API: fx=scene&type=0/1&uid=sid
            params = {"fx": "scene", "type": type_or_val, "uid": uid}
        else:
            params = {"fx": fx, "type": type_or_val, "uid": uid, "val": val}
        try:
            r = requests.get(url, params=params, timeout=10)
            log.debug("API response (%s): %s", fx, r.text[:200])
        except requests.RequestException as e:
            log.error("API call failed: %s", e)

    def _poll_loop(self):
        """Background thread: poll API for state and publish diffs."""
        while not self._stop_event.is_set():
            try:
                self._poll_and_publish()
            except Exception:
                log.exception("Poll error")
            # Wait for poll interval or until woken by an event
            self._poll_event.wait(timeout=self.cfg.poll_interval)
            self._poll_event.clear()

    def _poll_and_publish(self):
        """Fetch current state from API and publish changes to MQTT."""
        url = f"{self.cfg.api_base_url}{API_PATH}?fx=getState"
        try:
            r = requests.get(url, timeout=10)
        except requests.RequestException as e:
            log.error("Failed to fetch state: %s", e)
            self._reboot_mgr.record_failure()
            auto_reboot = True
            cached = self._get_cached_state()
            if cached:
                auto_reboot = cached.get("settings", {}).get("autoRebootEnabled", True)
            self._reboot_mgr.maybe_reboot(auto_reboot)
            return

        if r.status_code >= 500:
            log.warning("API returned %d", r.status_code)
            self._reboot_mgr.record_failure()
            auto_reboot = True
            state = self._get_cached_state()
            if state:
                auto_reboot = state.get("settings", {}).get("autoRebootEnabled", True)
            self._reboot_mgr.maybe_reboot(auto_reboot)
            return
        if r.status_code != 200:
            log.warning("API returned %d", r.status_code)
            return  # 4xx etc — don't count toward reboot

        try:
            state = r.json()
        except ValueError:
            log.warning("Empty or invalid JSON from API (bridge may be busy)")
            return  # Don't count toward reboot — PHP returned 200 but bridge was unreachable

        self._reboot_mgr.reset_failures()

        with self._lock:
            self._state_cache = state

        # Build flat device list with room context
        all_devices = []
        for room in state.get("rooms", []):
            room_name = room.get("name", "")
            for dev in room.get("devices", []):
                dev["_room_name"] = room_name
                all_devices.append(dev)

        changed = self.differ.diff(all_devices)
        for dev in changed:
            did = dev["id"]
            base = self._build_topic_base(dev["_room_name"], dev.get("name", ""), did)

            self.client.publish(f"{base}/status", str(dev.get("state", "0")), qos=0, retain=True)
            self.client.publish(f"{base}/brightness", str(dev.get("level", "0")), qos=0, retain=True)
            lwt = "offline" if dev.get("online") == 1 else "online"
            self.client.publish(f"{base}/LWT", lwt, qos=0, retain=True)

        if changed:
            log.info("Published state for %d device(s)", len(changed))

        # Publish HA discovery on first poll (when differ returns all devices)
        if len(changed) == len(all_devices) and all_devices:
            self._publish_discovery()

    def _publish_discovery(self):
        """Publish Home Assistant MQTT discovery configs for all devices."""
        if not self.cfg.enable_ha_discovery:
            return

        state = self._get_cached_state()
        if not state:
            return

        count = 0
        for room in state.get("rooms", []):
            for dev in room.get("devices", []):
                did = dev["id"]
                base = self._build_topic_base(room.get("name", ""), dev.get("name", ""), did)

                config = {
                    "name": dev.get("name", ""),
                    "unique_id": did,
                    "object_id": f"tcp_{did}",
                    "command_topic": f"{base}/switch",
                    "state_topic": f"{base}/status",
                    "brightness_command_topic": f"{base}/brightness/set",
                    "brightness_state_topic": f"{base}/brightness",
                    "availability_topic": f"{base}/LWT",
                    "brightness_scale": 100,
                    "qos": 0,
                    "payload_on": "1",
                    "payload_off": "0",
                    "optimistic": False,
                    "device": {
                        "identifiers": [f"tcp_light_{did}"],
                        "name": dev.get("name", ""),
                        "manufacturer": "TCP",
                        "model": "Connected Smart Light",
                        "suggested_area": room.get("name", ""),
                    },
                }

                topic = f"{self.cfg.hass_topic_id}/light/{did}/config"
                self.client.publish(topic, json.dumps(config), qos=0, retain=True)
                count += 1

        log.info("Published HA discovery for %d device(s)", count)


def main():
    logging.basicConfig(
        level=logging.INFO,
        format="%(asctime)s [%(name)s] %(levelname)s: %(message)s",
        datefmt="%Y-%m-%d %H:%M:%S",
    )

    try:
        cfg = Config()
    except ValueError as e:
        log.error("Configuration error: %s", e)
        sys.exit(1)

    bridge = MQTTBridge(cfg)

    def handle_signal(signum, frame):
        bridge.stop()

    signal.signal(signal.SIGTERM, handle_signal)
    signal.signal(signal.SIGINT, handle_signal)

    log.info("Starting MQTT bridge (broker=%s, api=%s)", cfg.broker, cfg.api_base_url)
    try:
        bridge.start()
    except Exception:
        log.exception("Bridge crashed")
        sys.exit(1)


if __name__ == "__main__":
    main()
