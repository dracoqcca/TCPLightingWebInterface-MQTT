<?php
	include "include.php";
	pageHeader("TCP Lighting Controller");
	ob_flush();
	flush ();  
	
	function getDeviceIDs(){
		$DATA = getRoomCarouselData();
		if( $DATA === null ){ exit; }
		$DEVICE_IDS = array();
		foreach($DATA as $room){
			if( isset($room['rid']) ){
				$devices = extractDevicesFromRoom($room);
				foreach($devices as $dev){
					$DEVICE_IDS[] = $dev["did"];
				}
			}
		}
		return $DEVICE_IDS;
	}
	
	$BASE_DEVICES = getDeviceIDs();
	$NOW_DEVICES = array();
	ob_flush();
	flush (); 
	echo '<div class="roomContainer" style="padding: 20px;">';
	
		echo "<p>Current State: ".sizeof($BASE_DEVICES)." Devices.</p>";
		echo "<p>Searching for new devices...</p>";
		$CMD = "cmd=GatewayInclusionStart&data=<gip><version>1</version><token>".TOKEN."</token></gip>";
		$result = getCurlReturn($CMD);
		
		$array = xmlToArray($result);
		
		
		echo "<p>";
		for( $x = 0; $x < 10; $x ++ ){
		
			$CMD = "cmd=GatewayInclusionProgressGet&data=<gip><version>1</version><token>".TOKEN."</token></gip>";
			$result = getCurlReturn($CMD);
			
			$array = xmlToArray($result);
			echo ".";
			ob_flush();
			flush (); 
			sleep (1);
			
		}
		echo "</p>";
		
			ob_flush();
			flush (); 
			
			$CMD = "cmd=GatewayInclusionStop&data=<gip><version>1</version><token>".TOKEN."</token></gip>";
			$result = getCurlReturn($CMD);
			$array = xmlToArray($result);
			
			
			sleep (5);
			
			$NOW_DEVICES = getDeviceIDs();
			
			if( sizeof($NOW_DEVICES) > sizeof($BASE_DEVICES) ){

				echo "<p><b>FOUND ". ( $BASE_COUNT - sizeof($BASE_DEVICES) ). " Devices</p>";
			
				pa( array_diff($BASE_DEVICES, $NOW_DEVICES) );
			}
			
			echo "<p>Final Result: ".sizeof($NOW_DEVICES).". You may need to run this more than once to discover the bulbs. They will be auto-added to your system. </p>";
			//pa( $NOW_DEVICES);
	
	
		echo '</div>';

	pageFooter();

?>

   