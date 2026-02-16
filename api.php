<?php
/*
 *
 * PHP API CALLS
 *
 */

	ob_start();
	include "include.php";

	global $REMOTE_IP;

	if( REQUIRE_EXTERNAL_API_PASSWORD && ! isLocalIPAddress($REMOTE_IP)){
		$password = 		isset($_REQUEST['password']) ? $_REQUEST['password'] : "";		//passed password
		if( $password != EXTERNAL_API_PASSWORD ){
			//invalid password
			echo "Invalid API Password";
			APILog("Attempted API Access, invalid or no password provided.");
			exit;
		}
	}

	if( RESTRICT_EXTERNAL_PORT == 1 && ! isLocalIPAddress($REMOTE_IP) ){
		if( $_SERVER['SERVER_PORT'] != EXTERNAL_PORT ){
			echo "Invalid Port";
			APILog("Attempted API Access on invalid port");
			exit;
		}
	}

	$function = isset($_REQUEST['fx']) ? $_REQUEST['fx'] : ""; 		//Toggle or Brightness
	$type = 	isset($_REQUEST['type']) ? $_REQUEST['type'] : "";		//Device or Room
	$UID = 		isset($_REQUEST['uid']) ? $_REQUEST['uid'] : "";		//DeviceID or Room ID
	$val = 		isset($_REQUEST['val']) ? $_REQUEST['val'] : "";		//DeviceID or Room ID


	APILog('- Function: '.$function." Type: " . $type . " ID : " . $UID . " Value: " . $val);

	$val = clampBrightness($val);

	/*Function to facilitate device dimming on / off */
	function dimOnOff($deviceInfoArray, $onOff ){
		//store original value
		$olevel = $deviceInfoArray["level"];
		$did = $deviceInfoArray['did'];
		//check state?
		if( $onOff == 1){
			//fade on,
			$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $did, 0, "level"));
			//set on
			$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $did, 1));
			//dim to olevel
			$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $did, $olevel, "level"));
		}else{
			//fade off
			$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $did, 0, "level"));
			//set off
			$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $did, 0));
			//dim to olevel
			$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $did, $olevel, "level"));

		}
		return $result;
	}

	function deviceOn($UID){
		$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $UID, 1));
		$array = xmlToArray($result);
	}

	if( $function != "" && $type != "" && $UID != "" && $val != ""){

		$DEVICES = getDevices();
		if( $type == "device"){

			$THE_DEVICE = null;
			if( sizeof($DEVICES) > 0 ){
				foreach($DEVICES as $device){
					if( $device["did"] == $UID ){
						$THE_DEVICE = $device;
						break;
					}
				}
			}

			switch ($function){
				case "toggle":

					//$val = 1 | 0 - on | off
					$val = ($val > 0) ? 1 : 0;


					if( ($val == 1 && FORCE_FADE_ON) || ($val == 0 && FORCE_FADE_OFF) ){
						//get current lighitng value

						dimOnOff($THE_DEVICE, $val );
						echo json_encode( array("toggle" => $val, "device" => $UID, "return" => "DimFade") );

					}else{
						$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $UID, $val));
						$array = xmlToArray($result);
						echo json_encode( array("toggle" => $val, "device" => $UID, "return" => $array) );
					}


				break;
				case "dim":
					if( $THE_DEVICE['state'] == 0){
						//turn light on
						deviceOn( $UID );
					}

					$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $UID, $val, "level"));
					$array = xmlToArray($result);
					echo json_encode( array("dim" => $val, "device" => $UID, "return" => $array) );
				break;


				case "dimby":
					$darkenTo = clampBrightness($THE_DEVICE['level'] - $val);
					$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $UID, $darkenTo, "level"));
					$array = xmlToArray($result);
					echo json_encode( array("dimby" => $val, "device" => $UID, "return" => $array) );


				break;
				case "brightenby":

					if( $THE_DEVICE['state'] == 0){
						//turn light on
						deviceOn( $UID );
					}

					$brightenTo = clampBrightness($THE_DEVICE['level'] + $val);
					$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $UID, $brightenTo, "level"));
					$array = xmlToArray($result);
					echo json_encode( array("brightenby" => $val, "device" => $UID, "return" => $array) );


				break;
				default:
				echo json_encode( array("error" => "unknown function, required: toggle | dim") );
			}
		}elseif($type == "room"){

			$THE_ROOM = null;

			$DATA = getRoomCarouselData();

			foreach($DATA as $room){
				if($room["rid"] == $UID){
					$THE_ROOM = $room;
					$DEVICES_IN_ROOM = extractDevicesFromRoom($room);
					$THE_ROOM['brightness'] = 0;
					foreach($DEVICES_IN_ROOM as $d){
						$THE_ROOM['brightness'] += $d['level'];
					}
					if(count($DEVICES_IN_ROOM) > 0){
						$THE_ROOM['brightness'] = $THE_ROOM['brightness'] / count($DEVICES_IN_ROOM);
					}
					break;
				}
			}



			if( $function == "toggle" ){
				//turn on | off
				$tval = ($val > 0) ? 1 : 0;
				if( ($tval == 1 && FORCE_FADE_ON) || ($tval == 0 && FORCE_FADE_OFF) ){

					$DEVICES = array();
					foreach($DATA as $room){
						if($room["rid"] == $UID){
							$DEVICES = extractDevicesFromRoom($room);
							foreach($DEVICES as &$d){
								$d["roomID"] = $room["rid"];
								$d["roomName"] = $room["name"];
							}
							unset($d);
							break;
						}
					}

					//get current room lighitng value??
					if( sizeof($DEVICES) > 0 ){
						$dcount = 0;
						$roomBrightness = 0;
						foreach($DEVICES as $device){
							if( $device["roomID"] == $UID ){
								$roomBrightness += $device["level"];
								$dcount++;
							}
						}
						if( $dcount > 0){
							$roomBrightness = ($roomBrightness/$dcount);
						}else{
							$roomBrightness = 100;
						}
					}else{
						$roomBrightness = 100;
					}

					$result = "";

					if( $tval == 1){
						//fade on --ensure off?
						$result = getCurlReturn(buildRoomCommand("RoomSendCommand", $UID, 0, "level"));
						//turn on
						$result.= getCurlReturn(buildRoomCommand("RoomSendCommand", $UID, 1));
						//fade to 100
						$result.= getCurlReturn(buildRoomCommand("RoomSendCommand", $UID, $roomBrightness, "level"));

					}else{
						//fade off -- ensure already off
						$result = getCurlReturn(buildRoomCommand("RoomSendCommand", $UID, 0, "level"));
						//turn off
						$result.= getCurlReturn(buildRoomCommand("RoomSendCommand", $UID, 0));
						//reset brightness to 100
						$result.= getCurlReturn(buildRoomCommand("RoomSendCommand", $UID, $roomBrightness, "level"));
					}


					$val = $roomBrightness;
					$array = $result;

				}else{
					$result = getCurlReturn(buildRoomCommand("RoomSendCommand", $UID, $val));
					$array = xmlToArray($result);
				}



			}else if( $function == "dim"){

				//turn room on if by chance it is off
				$result = getCurlReturn(buildRoomCommand("RoomSendCommand", $UID, 1));
				$array = xmlToArray($result);


				// dim
				$result = getCurlReturn(buildRoomCommand("RoomSendCommand", $UID, $val, "level"));
				$array = xmlToArray($result);

			}elseif( $function == "dimby" ){

				$roomBrightness = clampBrightness($THE_ROOM['brightness'] - $val);

				$result = getCurlReturn(buildRoomCommand("RoomSendCommand", $UID, $roomBrightness, "level"));
				$array = xmlToArray($result);


			}elseif( $function == "brightenby" ){

				$roomBrightness = clampBrightness($THE_ROOM['brightness'] + $val);

				$result = getCurlReturn(buildRoomCommand("RoomSendCommand", $UID, $roomBrightness, "level"));
				$array = xmlToArray($result);


			}


			echo json_encode( array("room" => $UID, "fx" => $function, "val" => $val,  "return" => $array) );

		}elseif($type == "all"){


			if( sizeof($DEVICES) > 0 ){
				foreach($DEVICES as $device){

					if( $function == "toggle" ){
						//only toggle if it needs to be toggled
						if( isset($device['state']) && $device['state']  != $val ){

							$tval = ($val > 0) ? 1 : 0;
							if( ($tval == 1 && FORCE_FADE_ON) || ($tval == 0 && FORCE_FADE_OFF) ){
								$result = dimOnOff( $device, $tval );

							}else{

								$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $device['did'], $val));
							}



						}

					}elseif( $function == "dim"){

						$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $device['did'], $val, "level"));

						//turn light on if it is not in order to dim it
						if( isset($device['state']) && $device['state']  == 0 ){
							$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $device['did'], 1));
						}


					}elseif ( $function == "dimby" ){

						$dBrightness = clampBrightness((isset($device['level']) ? $device['level'] : 100) - $val);

						$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $device['did'], $dBrightness, "level"));
						$array = xmlToArray($result);

						//turn light on if it is not in order to dim it
						if( isset($device['state']) && $device['state']  == 0 ){
							$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $device['did'], 1));
						}


					}elseif( $function == "brightenby" ){

						//turn light on if it is not in order to dim it
						if( isset($device['state']) && $device['state']  == 0 ){
							$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $device['did'], 1));
						}

						$dBrightness = clampBrightness((isset($device['level']) ? $device['level'] : 0) + $val);

						$result = getCurlReturn(buildDeviceCommand("DeviceSendCommand", $device['did'], $dBrightness, "level"));
						$array = xmlToArray($result);


					}


				}

				echo json_encode( array("success" => 1, "devices" => sizeof($DEVICES), "fx" => $function, "val" => $val) );

			}else{
				echo json_encode( array("error" => "no devices in home") );
			}


		}else{
			echo json_encode( array("error" => "unknown type, required: device | room") );
		}
	}else{

		if( $function == "scene" && $type != "" && $UID != "" ){
			//Run scene
			$CMD = "";
			if($type == "run"){
				$CMD = buildGipCommand("SceneRun", "<sid>".$UID."</sid>");
			}elseif( $type == "0" ){
				$CMD = buildGipCommand("SceneRun", "<sid>".$UID."</sid><val>0</val>");
			}elseif( $type == "1"){
				$CMD = buildGipCommand("SceneRun", "<sid>".$UID."</sid><val>1</val>");
			}elseif( $type == "delete" ){
			}

			if( $CMD != "" ){
				$result = getCurlReturn($CMD);
				$array = xmlToArray($result);
				echo json_encode( array("success" => 1, "scene" => $UID, "fx" => $function, "resp" => $array) );
			}else{
				echo json_encode( array("error" => "No Scene mode specified") );
			}
			exit;
		}

		if($function == "setAutoReboot"){
			$enabled = ($val == 1 || $val === "true") ? true : false;
			saveSetting("autoRebootEnabled", $enabled);
			echo json_encode(array("success" => 1, "autoRebootEnabled" => $enabled));
			exit;
		}

		if($function == "pushState"){
			$json = file_get_contents("php://input");
			$devices = json_decode($json, true);
			if(is_array($devices)){
				$cache = array("ts" => time(), "devices" => $devices);
				file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . "state_cache.json", json_encode($cache), LOCK_EX);
				echo json_encode(array("success" => 1));
			} else {
				echo json_encode(array("error" => "Invalid JSON"));
			}
			exit;
		}

		if($function == "getStateCache"){
			$file = dirname(__FILE__) . DIRECTORY_SEPARATOR . "state_cache.json";
			ob_clean();
			header('Content-Type: application/json');
			if(file_exists($file)){
				readfile($file);
			} else {
				echo json_encode(array("ts" => 0, "devices" => array()));
			}
			exit;
		}

		$sceneDevices = array("rooms"=> array(), "devices" => array(), "count" => 0);
		$sceneList = array();


		if( $function == "getSceneState" || $function == "getState"){
			if( $UID != "" || $function == "getState" ){

				$CMD = buildGipCommand("SceneGetListDetails", "<bigicon>1</bigicon>");
				$result = getCurlReturn($CMD);
				$array = xmlToArray($result);
				$scenes = $array["scene"];
				if( is_array($scenes) ){
					$sceneItemCount = 0;
					for($x = 0; $x < sizeof($scenes); $x++){
						$sceneList[] = array("id" => $scenes[$x]["sid"], "name" => $scenes[$x]["name"], "icon" => $scenes[$x]["icon"], "active" =>  $scenes[$x]["active"] );
						if( $scenes[$x]["sid"] == $UID ){

							$sceneItems = extractSceneDevices($scenes[$x]);
							foreach($sceneItems as $item){
								if(isset($item["id"])){
									$sceneItemCount++;
									if( $item["type"] == "D" ){
										$sceneDevices["devices"][] = $item["id"];
									}elseif( $item["type"] == "R" ){
										$sceneDevices["rooms"][] = $item["id"];
									}
								}
							}

							$sceneDevices["count"] = $sceneItemCount;
						}
					}


				}


			}else{
				echo json_encode( array("error" => "No Scene ID specified") );
			}
		}


		if( $function == "getState" || $function == "getDeviceState" || $function == "getRoomState" || $function == "getSceneState" ){

			$sceneDeviceObjectsON = 0;

			$DATA = getRoomCarouselData();
			if( $DATA === null ){ exit; }

			$DEVICES = array();
			$ROOMS  = array();
			$BRIDGE = array();
			if( sizeof($DATA) > 0 ){


				foreach($DATA as $room){
					$thisRoom = array();


					if( isset($room['rid'] ) ){
						$thisRoom["room_id"] = $room['rid'];
						$thisRoom["name"] = $room['name'];
						$thisRoom["color"] = $room['color'];
						$thisRoom["colorid"] = $room['colorid'];
						$thisRoom["brightness"] = 0;
						$thisRoom["state"] = 0;

						$DEVICES_IN_ROOM = extractDevicesFromRoom($room);
						foreach($DEVICES_IN_ROOM as $d){
							$rd = array();
							$rd["id"] = $d["did"];
							$rd["name"] = $d["name"] ?? "";
							$rd["level"] = isset($d["level"]) ? (int)$d["level"] : 0;
							$rd["state"] = $d["state"] ?? 0;
							$rd["online"] = (isset($d['offline']) && $d['offline'] == 1) ? 1 : 0;
							if( isset($d["other"]) && isset( $d["other"]["rcgroup"] ) && $d["other"]["rcgroup"] != null ){
								$rd["buttonNum"] = $d["other"]["rcgroup"];
							}
							$thisRoom["brightness"] += $rd["level"];
							$thisRoom["devices"][] = $rd;

							if( $d["state"] > 0 ){
								$thisRoom["state"] = (int)$thisRoom["state"] + 1;
							}

							if( $function == "getSceneState" && in_array( $rd["id"], $sceneDevices["devices"] ) && $rd["state"] > 0 ){
								$sceneDeviceObjectsON++;
							}

							if( $function == "getDeviceState" && $UID == $d["did"] ){
								ob_clean();
								echo trim($d["state"]);
								exit;
							}
						}


						if( $function == "getRoomState" && $UID ==  $room['rid'] ){
							ob_clean();
							echo ( $thisRoom["state"] > 0 ) ? 1 : 0;
							exit;
						}

						if( $function == "getSceneState" && in_array( $room['rid'], $sceneDevices["rooms"] ) && $thisRoom["state"] > 0 ){
							$sceneDeviceObjectsON++;
						}


						$thisRoom["devicesCount"] = sizeof( $thisRoom["devices"] );
						$thisRoom["brightness"] = (int)($thisRoom["brightness"] / $thisRoom["devicesCount"]);
						$thisRoom["state"] = ( $thisRoom["state"] > 0 ) ? ( $thisRoom["state"] / sizeof( $thisRoom["devices"] ) ) : 0;

						$ROOMS[] = $thisRoom;
					}
				}

			}

			$BRIDGE["rooms"] = $ROOMS;
			$BRIDGE["roomCount"] = sizeof($ROOMS);
			$BRIDGE["scenes"] = $sceneList;
			$BRIDGE["sceneCount"] = sizeof( $sceneList );


			if( $function == "getState"  ){
				$settings = getSettings();
				$BRIDGE["settings"] = $settings;
				ob_clean();
				header('Content-Type: application/json');
				echo json_encode( $BRIDGE );
				exit;
			}elseif( $function == "getSceneState" ){

				if( $sceneDeviceObjectsON == 0){
					echo 0;
				}elseif( $sceneDeviceObjectsON == $sceneDevices["count"] ){
					echo 1;
				}else{
					echo $sceneDeviceObjectsON / $sceneDevices["count"];
				}
				exit;
			}else{
				echo '-1';
				exit;
			}
		}

		echo json_encode( array("error" => "argument empty or invalid. Required: fx, type, UID, val", "recieved" => $_REQUEST) );

	}
?>