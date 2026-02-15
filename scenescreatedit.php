<?php
	/*
	 *
	 * TCP Ligthing Web UI Scenes Create/Edit Script - By Brendon Irwin
	 * 
	 */

	include "include.php";
	
	if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST'){
		/*For Saving Scene*/
		$action = "";
		$sceneID = "";
		
		if( isset( $_POST ) && isset( $_POST['action'] ) && $_POST['action'] != "" ){
			$action = $_POST['action'];
		}
		
		if( isset( $_POST ) && isset( $_POST['sceneID'] ) && $_POST['sceneID'] != "" ){
			$sceneID = $_POST['sceneID'];
		}
		
		if( $sceneID == "" || $action == "" ){
			echo json_encode( array("error" => "Invalid SceneID or Action") );
			exit;
		}
		
		
				
		if( $action == "save"){
			echo '<pre>'.print_r($_POST,true).'</pre>';
			
			if( $sceneID == -1 ){
				//New Scene
				$sceneID = "<sid>0</sid>"; // will populate with number
			}else{
				//Updating a scene
				$sceneID = '<sid>'.$sceneID.'</sid>';
			}
			
			$icon = "images/scene/". $_POST['icon'];
			$type = "manualcustom";
			$active = isset($_POST["active"]) ? $_POST["active"] : 1 ;
			$name = isset($_POST['name']) ? $_POST['name'] : "New Scene";
			$schedule = -1;
			$startTime = -1;
			$stopTime = -1;
			$every = "0,1,2,3,4,5,6";
			
			$icon  = htmlspecialchars($icon, ENT_XML1 | ENT_QUOTES, 'UTF-8');
			$name  = htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8');
			
			$deviceStr = "";
			
			if( isset( $_POST['rooms'] ) && is_array($_POST['rooms'] ) ){
				foreach( $_POST['rooms'] as $room ){
					$deviceStr .= "<device><id>".$room["rid"]."</id><type>R</type><cmd><type>power</type><value>".$room["toggled"]."</value></cmd><cmd><type>level</type><value>".$room["value"]."</value></cmd></device>";	
				}
			}
			
			if( isset( $_POST['devices'] ) && is_array($_POST['devices'] ) ){
				foreach( $_POST['devices'] as $dev ){
					//if( $dev["toggled"] == 0 )
					$deviceStr .= "<device><id>".$dev["did"]."</id><type>D</type><cmd><type>power</type><value>".$dev["toggled"]."</value></cmd><cmd><type>level</type><value>".$dev["value"]."</value></cmd></device>";	
				}
			}
			
			if( isset( $_POST['schedule'] ) && is_array($_POST['schedule'] ) ){
				$startTime =  $_POST['schedule']['starttime'];
				$stopTime =  $_POST['schedule']['stoptime'];
				$every =  $_POST['schedule']['every'];
				$schedule = 1;
				$type = "schedulecustom";
			}
						

			$innerXml = $sceneID."<active>".$active."</active>";
			$innerXml .= "<name>".$name."</name>";
			$innerXml .= "<type>".$type."</type>";
			$innerXml .= "<islocal>1</islocal>";
			if( $schedule == 1){
				$innerXml .= "<every>".$every."</every>";
				if( $startTime !=  '-1' ){
					$innerXml .= "<starttime>".$startTime."</starttime>";
				}
				if( $stopTime != '-1' ){
					$innerXml .= "<stoptime>".$stopTime."</stoptime>";
				}
			}
			$innerXml .= "<icon>".$icon."</icon>";
			$innerXml .= $deviceStr;
			$cmd = buildGipCommand("SceneCreateEdit", $innerXml);
			
		
			
			$result = getCurlReturn($cmd);
			$array = xmlToArray($result);
			
			ob_clean();
			echo json_encode( array("success" => 1, "cmd" => $cmd, "scene" => $array["sid"], "fx" => $action, "resp" => $array) );
			
		
			
		}
		
		if( $action == "delete"){
			//<gip><version>1</version><token>%s</token><sid>%s</sid></gip>
			$CMD = buildGipCommand("SceneDelete", "<sid>".$sceneID."</sid>"); 
			$result = getCurlReturn($CMD);
			$array = xmlToArray($result);
			ob_clean();
			echo json_encode( array("success" => 1, "scene" => $sceneID, "fx" => $action, "resp" => $array) );
		}
		
		
		exit;
	}
	pageHeader("TCP Lighting - Scene Controller");
	
?>
<script src="js/scenes.js"></script>
<?php
	echo '<div class="roomContainer scenescreatedit" style="padding: 20px;">';
	echo '<h2>Scenes / Smart Control</h2>';
	
	$CMD = buildGipCommand("SceneGetListDetails", "<bigicon>1</bigicon>");
	$result = getCurlReturn($CMD);
	$array = xmlToArray($result);
	
	$scenes = $array["scene"];
	$SCENE_ICONS = array("away.png", "bolt.png", "clock.png", "coffee.png", "dim.png", "eat.png", "e_car.png", "fan_cool.png", "fan_heat.png", "heart.png", "home.png", "lamp.png", "light.png", "music.png", "night.png", "off_to_work.png", "rainy.png", "sensor.png", "star.png", "target.png", "thermostat.png", "tree.png", "tv.png", "vacation.png", "washing_machine.png", "zroom_00.png", "zroom_01.png", "zroom_02.png", "zroom_03.png", "zroom_04.png", "zroom_05.png", "zroom_06.png", "zroom_07.png", "zroom_08.png", "zroom_09.png");
	if( is_array($scenes) && isset($_REQUEST['SID']) && $_REQUEST['SID'] != ""){
		$scene =  $_REQUEST['SID'];
		//thought -> if -1 New Scene?
		$foundScene = 0;
		for($x = 0; $x < sizeof($scenes); $x++){
			if($scenes[$x]["sid"] == $scene ){
				$foundScene = 1;
			?>
			<div class="scene-container <?php echo $scenes[$x]["active"] == 1 ? "scene-enabled" : "scene-disabled"; ?>" id="scene-id-<?php echo $scenes[$x]["sid"]; ?>">
                	
					<!--<p><b><?php echo $scenes[$x]["name"]; ?></b> (<?php echo is_array($scenes[$x]["device"]) ? sizeof($scenes[$x]["device"]) : ""; ?>)</p>-->
					<p><img id="icon" src="css/<?php echo $scenes[$x]["icon"]; ?>" /></p>
					<p>
												<select name="sceneIcon" id="sceneIcon">
							<?php foreach($SCENE_ICONS as $icon ){
								echo '<option value="'.$icon.'"'.($scenes[$x]["icon"] == "images/scene/".$icon ? " selected" : "").'>'. ucfirst( pathinfo($icon)['filename'] ).'</option>';
							}
							?>
						</select>
					</p>
					<p>
                        <button id="runScene" data-scene-mode="run" data-scene-id="<?php echo $scenes[$x]["sid"]; ?>" class="runScene">Run Scene</button> 
                        <button id="sceneOff" data-scene-mode="off" data-scene-id="<?php echo $scenes[$x]["sid"]; ?>" class="runScene">Scene Devices Off</button> 
                        <button id="sceneOn" data-scene-mode="on" data-scene-id="<?php echo $scenes[$x]["sid"]; ?>" class="runScene">Scene Devices On</button> 
                    </p>
			</div>
			<p>Scene ID: <?php echo $scenes[$x]["sid"]; ?></p>
			<p>Scene Name: <input id="SceneName" type="text" value="<?php echo h($scenes[$x]["name"]); ?>" /><p>
			<p>Scene Enabled: <input type="radio" value="1" name="sceneActive" <?php echo ($scenes[$x]["active"] == 1 ? "checked" : "" ); ?>/> Scene Disabled: <input type="radio" value="0" name="sceneActive" <?php echo ($scenes[$x]["active"] == 0 ? "checked" : "" ); ?>/></p>
			
			<p>Save Scene: <button id="saveScene" data-scene-id="<?php echo $scenes[$x]["sid"]; ?>">Save Scene</button></p>
			<p>Delete Scene: <button id="deleteScene" data-scene-id="<?php echo $scenes[$x]["sid"]; ?>">Delete Scene</button></p>
			

			<input type="hidden" value="<?php echo $scenes[$x]["sid"]; ?>" name="sceneID" id="sceneID" />
			<!--<input type="hidden" value="<?php echo $scenes[$x]["sid"]; ?>" name="sceneIcon" id="sceneIcon" />-->
			
            <div class="clear"></div>
           
			<?php
				$scene = $scenes[$x];
				$devices = array();
				foreach(extractSceneDevices($scenes[$x]) as $d){
					$devices[] = $d["id"];
				}
			}
		}
		
		if( !isset( $scene )  || $foundScene == 0 && $scene != -1){
			echo "<p>Invalid Scene</p>
			<p>You may have an invalid ID, or the Scene may have been deleted.<p>";
			echo '</div>';
			pageFooter();
			exit;
		}
		
		if( $scene == -1){
			?>
			<div class="scene-container" id="scene-id-<?php echo $scenes[$x]["sid"]; ?>">
					<p><img id="icon" src="css/images/scene/bolt.png" /></p>
					<p>
												<select name="sceneIcon" id="sceneIcon">
							<?php foreach($SCENE_ICONS as $icon ){
								echo '<option value="'.$icon.'"'.( "images/scene/bolt.png" == "images/scene/".$icon ? " selected" : "").'>'. ucfirst( pathinfo($icon)['filename'] ).'</option>';
							}
							?>
						</select>
					</p>
					
			</div>
			<p>Scene ID: <?php echo "To be generated on save."; ?></p>
			<p>Scene Name: <input id="SceneName" type="text" value="New Scene" placeholder="Type a Scene Name" /><p>
			<p>Save Scene: <button id="saveScene" data-scene-id="-1">Save Scene</button></p>
			
			<input type="hidden" value="-1" name="sceneID" id="sceneID" />
            <div class="clear"></div>
			
			<?php
		}
		
		?>

		<div id="sceneSettings" style="padding: 20px;">    				
		<?php
			function renderSwitch($roomID, $deviceID, $ON_OFF){
				?>
				<div class="control control-switch switch-val-<?php echo $ON_OFF; ?>">
					<label class="switch">
					  <input class="device-toggle" data-device-id="<?php echo $deviceID; ?>" data-room-id="<?php echo $roomID; ?>" name="SWITCH_SCHED" value="1" type="checkbox" <?php echo ($ON_OFF == 1) ? "checked" : ""; ?>>
					  <div class="slider round"></div>
					</label>
				</div>
				<?php
			}
			
			function renderSlider( $roomID, $deviceID, $VALUE ){
				?>
				<div class="control control-slider">
					<div class="scene-slider" data-device-id="all"></div>
					<input class="value-slider" data-device-id="<?php echo $deviceID; ?>" data-room-id="<?php echo $roomID; ?>" name="DIM_SCENE" type="hidden" value="<?php echo $VALUE ?>" />
				</div>
				<?php
			}
		
			function renderDevice($d, $rtoggled, $rid){
				global $devices;
				global $scene;
				echo '<div class="roomDeviceContainer" data-room-id="'.$rid.'" data-device-id="'.$d['did'].'" data-device-name="'.h($d['name']).'">';
					echo '<p>'.h($d['name']).'</p>';
					
					$SWITCH_ON = 0;
					$LEVEL = 0;
					$toggled = 0;
					
					if(  isset( $scene['device'][0]["id"] ) ){
						foreach(  $scene['device'] as $sd ){
							if( $d["did"] == $sd["id"] ){
								if( isset( $sd["cmd"]["0"] ) ){
									$SWITCH_ON = $sd["cmd"]["0"]["value"];
									if( ! is_array( $sd["cmd"]["1"]["value"] ) ){
																				$LEVEL = $sd["cmd"]["1"]["value"];
									}else{
																			}
								}else{
																	}
							}else{
															}
						}
					}else{
						//check
						if($d["did"] == $scene['device']["id"] ){
							
							if( isset( $scene['device']["0"] ) ){
								$SWITCH_ON = $scene['device']["0"]["value"];
																$LEVEL = $scene['device']["cmd"]["1"]["value"];
							}else{
																$SWITCH_ON = $scene['device']['cmd']['0']["value"];
								$LEVEL = $scene['device']["cmd"]['1']["value"];
															}
							
						}else{
													}
					}

					if( in_array( $d['did'], $devices ) ){
						$toggled = 1;
						echo '<div class="EnabledOrNot">Enabled: <input class="deviceToggle" type="checkbox" name="device[]" value="'.$d["did"].'" checked /></div>';
					}else{
						echo '<div class="EnabledOrNot">Enabled: <input class="deviceToggle" type="checkbox" name="device[]" value="'.$d["did"].'"/></div>';
					}

					echo '<div class="device-controls controls-toggled-'.$toggled.'">';
						renderSwitch($rid, $d["did"], $SWITCH_ON );
						renderSlider( $rid, $d["did"], $LEVEL );
					echo '</div>';
	
				echo '</div>';
			}
			
			function renderRoom($r, $toggled){
				global $scene;
				echo '<div class="roomSceneContainer" data-room-name="'.h($r["name"]).'" data-room-id="'.$r["rid"].'">';
				echo '<h2 class="room-name room-color-'.$r['colorid'].'">'.h($r["name"]).'</h2>';
				$SWITCH_ON = 0;
				$LEVEL = 0;
				
				if(  isset( $scene['device'][0]["id"] ) ){
					foreach(  $scene['device'] as $sr ){
						if( $r["rid"] == $sr["id"] ){
							$SWITCH_ON = $sr["cmd"]["0"]["value"];
							$LEVEL =  $sr["cmd"]["1"]["value"];
						}
					}
				}else{
					if($r["rid"] == $scene['device']["id"] ){
						$SWITCH_ON = $scene['device']["cmd"]["0"]["value"];
						$LEVEL = $scene['device']["cmd"]["1"]["value"];
					}
				}
				
				if( $toggled == 1 ){
					echo '<div class="EnabledOrNot">Enabled: <input class="roomToggle" type="checkbox" name="room[]" value="'.$r["rid"].'" checked /></div>';
				}else{
					echo '<div class="EnabledOrNot">Enabled: <input class="roomToggle" type="checkbox" name="room[]" value="'.$r["rid"].'"/></div>';
				}
				
				echo '<div class="room-controls controls-toggled-'.$toggled.'">';
				
				renderSwitch($r["rid"], $r["rid"], $SWITCH_ON );
				renderSlider( $r["rid"], $r["rid"], $LEVEL );
				
				echo '</div>';
			}
			
			?>
			
			<h1>Scene Schedule</h1>
			
			<?php
			$SceneSchedule = 0;
			$days = "0,1,2,3,4,5,6";
			$startTime = -1;
			$stopTime = -1;
		
			if( $scene == -1 ){ //scene id
				//new scene
			}else{
				if( isset($scene["every"]) && $scene["every"] != "" ){
					$days = $scene["every"];
					$startTime = ( isset($scene["starttime"]) && $scene["starttime"] != "" ) ? $scene["starttime"] : '-1'; //could be sunrise / sunset
					$stopTime = ( isset($scene["stoptime"]) && $scene["stoptime"] != "" ) ? $scene["stoptime"] : '-1';
					$SceneSchedule = 1;
				}else{
					//no Data
				}
			}
						?>
			<div id="scheduleContainer">
				Use Schedule:
				<div class="sched-switch sched-val-<?php echo $SceneSchedule; ?>">
					<label class="switch">
					  <input class="device-toggle" name="USE_SCHED" value="1" type="checkbox" <?php echo ($SceneSchedule == 1) ? "checked" : ""; ?>>
					  <div class="slider round"></div>
					</label>
				</div>
				
				<div id="schedule" class="scheduleEnabled scheduleEnabled-<?php echo $SceneSchedule; ?>">
					<p><b>Days:</b></p>
					<p>
						<label for="sunday">Sunday 		<input type="checkbox" id="sunday" value="0" name="every" <?php echo strpos($days, "0") !== false ? " checked" : ""  ?>/></label><br />
						<label for="monday">Monday 		<input type="checkbox" id="monday" value="1" name="every" <?php echo strpos($days, "1") !== false ? " checked" : ""  ?>/></label><br /> 
						<label for="tuesday">Tuesday 	<input type="checkbox" id="tuesday" value="2" name="every" <?php echo strpos($days, "2") !== false ? " checked" : ""  ?>/></label><br /> 
						<label for="wednesday">Wednesday<input type="checkbox" id="wednesday" value="3" name="every" <?php echo strpos($days, "3") !== false ? " checked" : ""  ?>/></label><br /> 
						<label for="thursday">Thursday 	<input type="checkbox" id="thursday" value="4" name="every" <?php echo strpos($days, "4") !== false ? " checked" : ""  ?>/></label><br /> 
						<label for="friday">Friday 		<input type="checkbox" id="friday" value="5" name="every" <?php echo strpos($days, "5") !== false ? " checked" : ""  ?>/></label> <br />
						<label for="saturday">Saturday 	<input type="checkbox" id="saturday" value="6" name="every" <?php echo strpos($days, "6") !== false ? " checked" : ""  ?>/></label> 
						
					</p>
					<p><b>Start Time:</b></p>
					<div class="timeContainer">
					<select id="startHour" name="startHour">
						<option value="-1" <?php if($startTime == '-1' ){ echo ' selected'; }?>>No Start Time</option>
						<?php for($x = 0; $x < 24; $x ++){
							if( $startTime != '-1' && $startTime != 'sunrise' && $startTime != 'sunset'  ){
								echo '<option value="'.$x.'" '.( date('G', strtotime( $startTime ) ) == $x ? " selected" : "").'>'.date("g a",strtotime($x.":00")).'</option>';
							}else{
								echo '<option value="'.$x.'">'.date("g a",strtotime($x.":00")).'</option>';
							}
						}
						?>
						<option value="sunrise" <?php if($startTime == "sunrise"){ echo ' selected'; }?>>Sun Rise</option>
						<option value="sunset" <?php if($startTime == "sunset"){ echo ' selected'; }?>>Sun Set</option>
					</select>
					<select id="startMin" name="startMin" <?php if($startTime == "sunrise" || $startTime == "sunset" || $startTime == -1 ){ echo 'disabled="disabled"'; } ?>>
						<?php 
						for( $x = 0; $x < 60; $x++){
							if( $startTime != '-1' && $startTime != 'sunrise' && $startTime != 'sunset'  ){
								echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'" '.( date('i', strtotime( $startTime ) ) == $x ? " selected" : "").'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
							}else{
								echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'">'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
							}
						}
						?>
					</select>
					</div>
					<p><b>Stop Time:</b></p>

					<div class="timeContainer">
						
						<select id="stopHour" name="stoptHour">
						<option value="-1" <?php if($stopTime == '-1' ){ echo ' selected'; }?>>No Stop Time</option>
							<?php for($x = 0; $x < 24; $x ++){
								if( $stopTime != '-1' && $stopTime != 'sunrise' && $stopTime != 'sunset'  ){
									echo '<option value="'.$x.'" '.( date('G', strtotime( $stopTime ) ) == $x ? " selected" : "").'>'.date("g a",strtotime($x.":00")).'</option>';
								}else{
									echo '<option value="'.$x.'">'.date("g a",strtotime($x.":00")).'</option>';
								}
							}
							?>
							<option value="sunrise" <?php if($stopTime == "sunrise"){ echo ' selected'; }?>>Sun Rise</option>
							<option value="sunset" <?php if($stopTime == "sunset"){ echo ' selected'; }?>>Sun Set</option>
						</select>
						
						<select id="stopMin" name="stopMin" <?php if($stopTime == "sunrise" || $stopTime == "sunset" || $stopTime == -1 ){ echo 'disabled="disabled"'; } ?>>
							<?php 
							for( $x = 0; $x < 60; $x++){
								if( $stopTime != '-1' && $stopTime != 'sunrise' && $stopTime != 'sunset'  ){
									echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'" '.( date('i' , strtotime( $stopTime )) == $x ? " selected" : "").'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
								}else{
									echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'">'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
								}
							}
							?>
						</select>
					</div>
				
				</div>
			
			</div>
			<?php
		
			$DATA = getRoomCarouselData();

			if( $DATA !== null && sizeof($DATA) > 0 ){
				echo '<hr />';
				echo '<h1>Scene Settings</h1>';
			
				foreach($DATA as $room){
					$toggled = 0;

					//check if roomID is in scene...
					if( in_array( $room['rid'], $devices ) ){
						$toggled = 1;
						renderRoom($room, 1);
					}else{
						renderRoom($room, 0);
					}
					
					echo '<div class="room-devices room-devices-toggled-'.$toggled.'">';
						$roomDevices = extractDevicesFromRoom($room);
					foreach($roomDevices as $dev){
						renderDevice( $dev, $toggled, $room['rid'] );
					}
					
					echo '</div>';
					echo '</div>'; //end of render room div
				}
			}
		?>
		<button id="save2">Save</button>
		</div>				
		
		<?php
	}
	echo '</div>';
pageFooter();
?>
