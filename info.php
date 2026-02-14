<?php
/*
 *
 * TCP Ligthing Web UI Info Script - By Brendon Irwin
 *
 */

include "include.php";

$CMD = buildGipCommand("RoomGetList", "");
$result = getCurlReturn($CMD);
$array = xmlToArray($result);
$ROOM_COLOURS = array();



if ( isset( $array["room"]["rid"] ) ){ $array["room"] = array(   $array["room"]); }

foreach( $array['room'] as $room ){
	$ROOM_COLOURS[ $room["colorid"] ]["name"] =  $room["name"];
	$ROOM_COLOURS[ $room["colorid"] ]["hex"] =  $room["color"];
	$ROOM_COLOURS[ $room["colorid"] ]["colorid"] =  $room["colorid"];
	$ROOM_COLOURS[ $room["colorid"] ]["image"] =  $room["img"];
	$ROOM_COLOURS[ $room["colorid"] ]["room"] =  $room["rid"];
}

if (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET'){
		if(isset($_GET['delete']) && $_GET['delete'] != "" ){
		/*
		 if (this.full != null && this.full.length() > 0 && this.full.equalsIgnoreCase("1")) {
            fullDelete = "<full>1</full>";
        }
        dataString = String.format("<gip><version>1</version><token>%s</token><did>%s</did>%s</gip>", new Object[]{xmlEscape(this.token), xmlEscape(this.did), fullDelete});
        this.postData.add(new BasicNameValuePair("cmd", "DeviceDelete"));
        this.postData.add(new BasicNameValuePair("data", dataString));
		*/

		$did = $_REQUEST['delete'];
		$fullDelete =  $_REQUEST['fullDelete'];

		$CMD = buildGipCommand("DeviceDelete", "<did>".$did."</did>".($fullDelete == 1 ? '<full>1</full>' : ''));
		$result = getCurlReturn($CMD);
		$array = xmlToArray($result);

		echo json_encode( array("delete" => $did, "fullDelete" => $fullDelete, "Result" => $array) );

	}
}


if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST'){

	if(isset($_POST['did']) && $_POST['did'] != "" ){
		$did = $_POST['did'];
		$name = $_POST['name'];
		$color = $_POST['color'];
		$remote = $_POST['remote'];
		$imdata = "";

		if( isset($_FILES["image"]) && $_FILES["image"]["tmp_name"] != "" ){
			$imageFileType = pathinfo( basename($_FILES["image"]["name"]) ,PATHINFO_EXTENSION);
			$check = getimagesize($_FILES["image"]["tmp_name"]);
			if($check !== false) {
				if ($_FILES["image"]["size"] > 500000) {
					echo "Sorry, your file is too large.";
				}else{
					if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
						echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
					}else{
						$imdata = base64_encode( file_get_contents($_FILES["image"]["tmp_name"] ) );
						echo '<p><img src="data:image/png;base64,'.$imdata.'" /></p>';
					}
				}
			}
		}

		$CMD = buildGipCommand("DeviceSetInfo", "<did>".$did."</did><name>".$name."</name>".($color != "" ? "<color>".$color."</color>" : "").($imdata != "" ? "<image>".$imdata."</image>" : "").($remote != "" ? "<other><rcgroup>".$remote."</rcgroup></other>" : ""));

		$result = getCurlReturn($CMD);
		$array = xmlToArray($result);

		header("Location: info.php?did=".$did);
	}

	if(isset($_POST['rid']) && $_POST['rid'] != "" ){
		$rid = $_POST['rid'];
		$name =  $_POST['name'];
		$color = $_POST['color'];

		$CMD = buildGipCommand("RoomSetInfo", "<rid>".$rid."</rid><name>".$name."</name>".($color != "" ? "<colorid>".$color."</colorid>" : ""));
		$result = getCurlReturn($CMD);
		$array = xmlToArray($result);

		header("Location: info.php?rid=".$rid);
	}
}


if( isset($_REQUEST['did']) && $_REQUEST['did'] != "" ){
	$did = $_REQUEST['did'];
	pageHeader("TCP Lighting Controller - Device Controller - Device: ".$did);
	?>

	<?php
	echo '<div class="roomContainer" style="background-color: #fff; padding: 20px; border: 1px solid #000;">';

	$CMD = buildGipCommand("DeviceGetInfo", "<did>".$did."</did><fields>name,power,product,class,image,control,realtype,other,status</fields>");

	$result = getCurlReturn($CMD);
	$array = xmlToArray($result);

	?>
	<script>
		$(function(){
			$('#deleteDevice').click(function(){
				var did = $(this).attr('data-deviceID');
				var c = confirm("Are you sure you wish to delete the device?");
				if(c){
					var fd = 0;

					$.get( "info.php?delete=" + did + "&fullDelete=" + fd, function( data ) {
						window.location = "index.php";
					});
				}
			});
		});
	</script>
	<form method="post" action="info.php" enctype="multipart/form-data">
		<fieldset>
			<legend>Update Device</legend>
		<?php
		if( isset( $array["image"] ) && ! is_array( $array["image"]) ){
			echo '<p><img src="data:image/png;base64,'.$array["image"].'" /></p>';
		}
		?>
		<label for="name">Name: <input name="name" id="name" value="<?php echo $array["name"]; ?>" /></label><br />
		<label for="did">Device ID: <?php echo $did; ?></label><br />
		<label for="image">Image: <input type="file" name="image" id="image"></label><br />
		<label for="color">Color:
			<?php renderColorSelect($array["colorid"], $ROOM_COLOURS, true); ?>
		</label><br />
		<?php
		$color = $array["colorid"];
		if( isset( $ROOM_COLOURS[$color] ) ){
		?>
			<label for="room">Room:
			<?php echo '<a href="info.php?rid='.$ROOM_COLOURS[$color]['room'].'">'.$ROOM_COLOURS[$color]['name'].'</a>'; ?>
			</label>
		<?php
		}
		?>
		<br />
		<label for="remote">Assigned Remote Control Button: <br />
		<?php
			if( isset($array['other']['rcgroup']) &&  $array['other']['rcgroup'] != "" ){
				$rcID = $array['other']['rcgroup'];
			}else{
				$rcID = "";
			}
		?>
			<select name="remote">
				<option value="">Not Specified / No remote</option>
				<option value="1" <?php echo ($rcID == 1 ? " Selected" : ""  ); ?>>Button 1</option>
				<option value="2" <?php echo ($rcID == 2 ? " Selected" : ""  ); ?>>Button 2</option>
				<option value="3" <?php echo ($rcID == 3 ? " Selected" : ""  ); ?>>Button 3</option>
				<option value="4" <?php echo ($rcID == 4 ? " Selected" : ""  ); ?>>Button 4</option>
			</select><br />
			<img id="remote" style="display: none;" src="<?php echo LOCAL_URL; ?>/css/images/remote.png" />
		</label><br />

		<input type="hidden" name="did" value="<?php echo $did; ?>" /><br />
		<input type="submit" value="SAVE" />
		</fieldset>
	</form>
	<?php
	echo '<div class="devices">';
		echo '<div class="room-devices">';
			$device = $array;
			$device['did'] = $did;
			renderDeviceCard($device);
		echo '</div>';

		?>
			<button id="deleteDevice" data-deviceID="<?php echo $device['did'];  ?>">Delete Device</button>
		<?php
	echo '</div>';
	?>
	</div>
	<div id="arrayDump" class="roomContainer" style="padding: 20px;">
	<?php
	pa( $array );
	?>
	</div>
	<?php
	pageFooter();
}

if( isset($_REQUEST['rid']) && $_REQUEST['rid'] != "" ){
	$rid = $_REQUEST['rid'];
	pageHeader("TCP Lighting Controller - Room Controller - Room: ".$rid);

	$CMD = buildGipCommand("RoomGetInfoAll", "<rid>".$rid."</rid><fields>name,power,product,class,image,imageurl,control,other</fields>");

	$result = getCurlReturn($CMD);
	$room = xmlToArray($result);

	echo '<div class="roomContainer" style="background-color: #fff; padding: 20px; border: 1px solid #000; margin-top: 20px;">';
	?>
	<form method="post" action="info.php" enctype="multipart/form-data">
		<fieldset>
			<legend>Update Room</legend>
			<?php
			echo '<h3>&lsquo;'.$room["name"].'&rsquo; - Room ID: '.$rid.'</h3>';
			?>
			<input type="hidden" name="rid" value="<?php echo $rid; ?>" />

			<p><label for="name">Name: <input name="name" id="name" value="<?php echo $room["name"]; ?>" /></label></p>

			<p><label for="color">Color:
			<?php renderColorSelect($room["colorid"], $ROOM_COLOURS); ?>
			<?php
				if( isset($ROOM_COLOURS[ $room["colorid"] ]) ){
					echo '<div style="display: inline-block; background-color: #'.$ROOM_COLOURS[ $room["colorid"] ]["hex"].'; padding: 5px;">'.$ROOM_COLOURS[ $room["colorid"] ]["hex"].'</div>';
				}
			?>
		</label></p>

		<input type="submit" value="UPDATE" />
		</fieldset>
		</form>
			<?php
			echo '<div class="roomContainer" data-room-id="'. $rid.'" style="margin-top:20px;">';
			$DEVICES = extractDevicesFromRoom($room);
			$deviceCount = sizeof($DEVICES);

			if( sizeof($DEVICES) > 0 ){
				echo '<div class="devices">';
					echo '<p>Room Devices:</p>';
					echo '<div class="room-devices">';
					$roomBrightness = 0;
					$roomDevices = 0;
					foreach($DEVICES as $device){
						renderDeviceCard($device, array('showInfo' => true));
						$roomBrightness += (isset($device['level']) ? $device['level'] : 100);
						$roomDevices++;
					}
					echo '</div>';

				echo '</div>';

			}else{
				echo 'No devices?';
			}

			echo '<div class="room-controls">';
				echo 'Room Brightness: <div class="room-slider" data-value="'.($roomBrightness/$roomDevices).'" data-room-id="'. $room["rid"].'"></div>';
				echo 'Room <button data-room-id="'. $room["rid"].'" class="onOffToggleButton buttonOn">On</button> | <button data-room-id="'. $room["rid"].'" class="onOffToggleButton buttonOff">Off</button>';
			echo '</div>';
		echo '</div>';
	echo '</div>';

	?>
	<div id="arrayDump" class="roomContainer" style="padding: 20px;">
	<?php
	pa( $room );
	?>
	</div>
	<?php

	pageFooter();
}
?>
