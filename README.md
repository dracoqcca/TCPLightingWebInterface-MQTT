![HeaderWebInterface](https://user-images.githubusercontent.com/23568795/64644718-85070980-d3d0-11e9-83a8-034f91ee0f4f.png)

## TCPLightingWebInterface-MQTT

A fork of [TCPLightingWebInterface](https://github.com/bren1818/TCPLightingWebInterface) by bren1818.

Removes cloud dependence by bridging TCP Connected smart lights to MQTT with Home Assistant auto-discovery. A persistent Python daemon handles all MQTT communication (subscribe, publish state, HA discovery) over a single connection — no generated scripts, no per-device reconnects, no runtime `pip install`.

## Architecture

```
MQTT Broker <──── mqtt-bridge (Python) ────> PHP Web UI (api.php)
     │                                             │
     │  subscribes: light/+/+/+/switch             │  HTTP calls to
     │              light/+/+/+/brightness/set      │  TCP Lighting Bridge
     │              light/+/+/switch                │
     │              light/+/+/brightness/set        │
     │              control/tcp                     │
     │              hass/status                     │
     │                                             │
     │  publishes:  light/{room}/{device}/{did}/*   │
     │              homeassistant/light/{did}/config│
     │                                             │
Home Assistant  <── MQTT Discovery ──────────────────
```

## Requirements

- Docker and Docker Compose
- A TCP Connected smart lighting bridge on your network
- An MQTT broker (e.g. Mosquitto)

## Quick Start

### 1. Clone the repository

```bash
git clone https://github.com/dracoqcca/TCPLightingWebInterface-MQTT.git
cd TCPLightingWebInterface-MQTT
```

### 2. Configure environment

Copy the example environment file and fill in your values:

```bash
cp .env.example .env
```

Edit `.env` with your settings:

```env
# TCP Lighting Bridge
BRIDGE_IP=192.168.1.100        # IP of your TCP lighting bridge
BRIDGE_PORT=443                # 443 for new firmware, 80 for legacy
TCP_TOKEN=your_token_here      # Your bridge token (see Token Setup below)
LOCAL_URL=http://TCPLightingWebInterface

# MQTT Broker
MQTT_BROKER=192.168.1.200      # IP of your MQTT broker
MQTT_PORT=1883
MQTT_USERNAME=your_username
MQTT_PASSWORD=your_password

# MQTT Topics (defaults are usually fine)
MQTT_CLIENT_ID=tcp-bridge
MQTT_PREFIX=light
MQTT_CONTROL=tcp

# Home Assistant Discovery
ENABLE_HA_DISCOVERY=1
HASS_TOPIC_ID=homeassistant
HASS_ONLINE_TOPIC=hass/status

# Bridge Settings
API_BASE_URL=http://TCPLightingWebInterface
POLL_INTERVAL=5                # Seconds between state polls
```

### 3. Start the stack

```bash
docker compose up -d --build
```

### 4. Get your token

If you don't have a token yet:

1. Open the web UI at `http://your-docker-host:8000`
2. The setup page will guide you through generating a token
3. Once generated, copy the token into your `.env` file as `TCP_TOKEN`
4. Restart the stack: `docker compose up -d`

If your bridge uses legacy firmware (port 80), the token is not required.

## Verifying It Works

Check the bridge logs:

```bash
docker logs mqtt-bridge
```

You should see:

```
API is ready
MQTT connected (rc=0)
Published state for 6 device(s)
Published HA discovery for 6 device(s)
```

Verify MQTT topics are published:

```bash
mosquitto_sub -h YOUR_BROKER_IP -u YOUR_USER -P YOUR_PASS -t "light/#" -v
```

## Home Assistant

Devices appear automatically if MQTT discovery is enabled in HA (the default). Check **Settings > Devices & Services > MQTT** for your TCP lights.

Each device gets:
- On/off control
- Brightness control (0-100)
- Online/offline availability
- Suggested room area from the TCP bridge

If devices don't appear, restart Home Assistant after the bridge is running — retained discovery messages will be picked up on startup.

## MQTT Topics

### State topics (published by the bridge, retained)

| Topic | Payload | Description |
|-------|---------|-------------|
| `light/{room}/{device}/{did}/status` | `0` / `1` | On/off state |
| `light/{room}/{device}/{did}/brightness` | `0`-`100` | Brightness level |
| `light/{room}/{device}/{did}/LWT` | `online` / `offline` | Device availability |

### Command topics (subscribe to control lights)

| Topic | Payload | Description |
|-------|---------|-------------|
| `light/{room}/{device}/{did}/switch` | `0` / `1` | Toggle device |
| `light/{room}/{device}/{did}/brightness/set` | `0`-`100` | Set device brightness |
| `light/{room}/{rid}/switch` | `0` / `1` | Toggle room |
| `light/{room}/{rid}/brightness/set` | `0`-`100` | Set room brightness |
| `light/{scene}/{sid}/switch` | `0` / `1` | Activate scene |

### Control topics

| Topic | Payload | Description |
|-------|---------|-------------|
| `control/tcp` | `QUIT` | Graceful shutdown |
| `control/tcp` | `REBOOT` | Restart bridge (Docker restarts it) |

## Environment Variables Reference

### PHP Web UI (`TCPLightingWebInterface`)

| Variable | Default | Description |
|----------|---------|-------------|
| `BRIDGE_IP` | `192.168.1.TCP` | TCP lighting bridge IP |
| `BRIDGE_PORT` | `443` | Bridge port (443 or 80) |
| `TCP_TOKEN` | _(none)_ | Bridge auth token |
| `LOCAL_URL` | `http://lighting.local` | Internal URL for the web UI |
| `EXTERNAL_API_PASSWORD` | `P@ssW0rd` | Password for external API access |

### MQTT Bridge (`mqtt-bridge`)

| Variable | Default | Description |
|----------|---------|-------------|
| `MQTT_BROKER` | _(required)_ | MQTT broker hostname or IP |
| `MQTT_PORT` | `1883` | MQTT broker port |
| `MQTT_USERNAME` | _(empty)_ | MQTT username |
| `MQTT_PASSWORD` | _(empty)_ | MQTT password |
| `MQTT_CLIENT_ID` | `tcp-bridge` | MQTT client identifier |
| `MQTT_PREFIX` | `light` | Topic prefix for lights |
| `MQTT_CONTROL` | `tcp` | Control topic identifier |
| `ENABLE_HA_DISCOVERY` | `1` | Enable HA MQTT discovery |
| `HASS_TOPIC_ID` | `homeassistant` | HA discovery topic prefix |
| `HASS_ONLINE_TOPIC` | `hass/status` | HA birth/will topic |
| `API_BASE_URL` | _(required)_ | URL of the PHP web UI |
| `POLL_INTERVAL` | `5` | Seconds between state polls |

## Web UI

The PHP web interface is available at `http://your-docker-host:8000` and provides:

- Room and device control
- Scene management
- Lighting scheduler
- IFTTT query builder

## API

The REST API is available at `http://your-docker-host:8000/api.php`:

| Endpoint | Description |
|----------|-------------|
| `?fx=getState` | Get full state (rooms, devices, scenes) |
| `?fx=toggle&type=device&uid={did}&val=0/1` | Toggle a device |
| `?fx=dim&type=device&uid={did}&val=0-100` | Set device brightness |
| `?fx=toggle&type=room&uid={rid}&val=0/1` | Toggle a room |
| `?fx=dim&type=room&uid={rid}&val=0-100` | Set room brightness |
| `?fx=scene&type=0/1&uid={sid}` | Activate/deactivate a scene |
