<?php
/*
 *
 * PHP includes
 *
 */
 
include "config.inc.php";


function isLocalIPAddress($IPAddress){
    if($IPAddress == '127.0.0.1'){return true;} 
    return ( !filter_var($IPAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) );
}

$REMOTE_IP = getenv('HTTP_CLIENT_IP')?: getenv('HTTP_X_FORWARDED_FOR')?: getenv('HTTP_X_FORWARDED')?: getenv('HTTP_FORWARDED_FOR')?: getenv('HTTP_FORWARDED')?: getenv('REMOTE_ADDR');

$CURRENT_PAGE_SCRIPT = basename($_SERVER["SCRIPT_FILENAME"], '.php');

function h($str){
	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function xmlEscape($str){
	return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/*Function to Print Array*/
function pa($array){
	echo '<pre>'.print_r($array,true).'</pre>';
}

function getCurlReturn($postDataString){
	$URL = SCHEME."://".LIGTHING_BRIDGE_IP.":".LIGHTING_BRIDGE_PORT."/gwr/gop.php";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataString);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	if (SCHEME == 'https') {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($ch);

	if($result === false){
		BridgeErrorLog("CURL_FAIL errno=" . curl_errno($ch) . " error=\"" . curl_error($ch) . "\" url=" . $URL);
		curl_close($ch);
		return "";
	}

	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if($httpCode >= 400){
		BridgeErrorLog("HTTP_" . $httpCode . " url=" . $URL);
	}

	return $result;
}

function xmlToArray($string){
	$xml = simplexml_load_string($string);
	$json = json_encode($xml);
	$array = json_decode($json,TRUE);
	
	return $array;
}


function buildGipCommand($cmd, $innerXml){
	return "cmd=".$cmd."&data=<gip><version>1</version><token>".TOKEN."</token>".$innerXml."</gip>";
}

function buildDeviceCommand($cmd, $did, $value, $type = null){
	$xml = "<did>".$did."</did><value>".$value."</value>";
	if($type !== null){ $xml .= "<type>".$type."</type>"; }
	return buildGipCommand($cmd, $xml);
}

function buildRoomCommand($cmd, $rid, $value, $type = null){
	$xml = "<rid>".$rid."</rid><value>".$value."</value>";
	if($type !== null){ $xml .= "<type>".$type."</type>"; }
	return buildGipCommand($cmd, $xml);
}

function clampBrightness($val){
	return max(0, min(100, (int)$val));
}

function extractSceneDevices($scene){
	if(isset($scene['device']['id'])){
		return array($scene['device']);
	}
	if(isset($scene['device']) && is_array($scene['device'])){
		return $scene['device'];
	}
	return array();
}

function extractDevicesFromRoom($room){
	$devices = array();
	if( !is_array($room["device"]) ){
		return $devices;
	}
	$device = (array)$room["device"];
	if( isset($device["did"]) ){
		$devices[] = $device;
	}else{
		for( $x = 0; $x < sizeof($device); $x++ ){
			if( isset($device[$x]) && is_array($device[$x]) && ! empty($device[$x]) ){
				$devices[] = $device[$x];
			}
		}
	}
	return $devices;
}

function getRoomCarouselData(){
	$CMD = "cmd=GWRBatch&data=<gwrcmds><gwrcmd><gcmd>RoomGetCarousel</gcmd><gdata><gip><version>1</version><token>".TOKEN."</token><fields>name,image,imageurl,control,power,product,class,realtype,status</fields></gip></gdata></gwrcmd></gwrcmds>&fmt=xml";
	$result = getCurlReturn($CMD);
	$array = xmlToArray($result);
	if( !isset($array["gwrcmd"]["gdata"]["gip"]["room"]) ){
		return null;
	}
	$DATA = $array["gwrcmd"]["gdata"]["gip"]["room"];
	if ( isset( $DATA["rid"] ) ){ $DATA = array( $DATA ); }
	return $DATA;
}

function getDevices(){
	$DATA = getRoomCarouselData();
	if( $DATA === null ){ return array(); }
	$DEVICES = array();
	foreach($DATA as $room){
		$DEVICES = array_merge($DEVICES, extractDevicesFromRoom($room));
	}
	return $DEVICES;
}

function renderColorSelect($selectedId, $ROOM_COLOURS, $showPickOption = false){
	$colors = array(
		0 => 'Black', 1 => 'Green', 2 => 'Dark Blue', 3 => 'Red',
		4 => 'Yellow', 5 => 'Purple', 6 => 'Orange', 7 => 'Light Blue', 8 => 'Pink'
	);
	echo '<select name="color">';
	if($showPickOption){ echo '<option value="">Pick Colour</option>'; }
	foreach($colors as $id => $name){
		$sel = ($selectedId == $id) ? "selected" : "";
		$roomLabel = isset($ROOM_COLOURS[$id]) ? ' ('.$ROOM_COLOURS[$id]['name'].')' : '';
		echo '<option value="'.$id.'" '.$sel.'>'.$name.$roomLabel.'</option>';
	}
	echo '</select> ';
}

function renderDeviceCard($device, $options = array()){
	$showInfo = isset($options['showInfo']) ? $options['showInfo'] : false;
	$showRcGroup = isset($options['showRcGroup']) ? $options['showRcGroup'] : false;
	$did = $device['did'];
	$offline = isset($device['offline']) && $device['offline'] == 1;
	$level = isset($device['level']) ? $device['level'] : 100;

	echo '<div class="'.($offline ? 'unplugged' : 'plugged').' device '.($device['state'] == 1 ? 'light-on' : 'light-off').' '.($device['prodtype'] == 'Light Fixture' ? 'light-fixture' : '').'" data-device-id="'.$did.'">';
		if($showRcGroup && isset($device["other"]) && isset($device["other"]["rcgroup"]) && $device["other"]["rcgroup"] != null && $device["other"]["rcgroup"] <= 4){
			echo '<div class="control-button">'.$device["other"]["rcgroup"].'</div>';
		}
		echo '<p class="device-name"><b>'.$device['name'].'</b>';
		if($showInfo){ echo ' <a href="info.php?did='.$did.'"><img src="css/images/info.png"/></a>'; }
		echo '</p>';
		echo '<p class="on-off-buttons"><button data-device-id="'.$did.'" class="onOffDeviceToggleButton buttonOn">On</button> | <button data-device-id="'.$did.'" class="onOffDeviceToggleButton buttonOff">Off</button></p>';
		echo '<div class="clear"></div>';
		echo '<p>Brightness:</p>';
		echo '<div class="device-slider" data-value="'.$level.'" data-device-id="'.$did.'"></div>';
	echo '</div>';
}

function pageHeader($title){
	global $CURRENT_PAGE_SCRIPT;
	header('X-Frame-Options: DENY');
	header('X-Content-Type-Options: nosniff');
	header('Referrer-Policy: same-origin');
	?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>TCP Control Script</title>
	<link rel="apple-touch-icon" sizes="180x180" href="css/favicons/apple-touch-icon.png">
	<link rel="icon" type="image/png" href="css/favicons/favicon-32x32.png" sizes="32x32">
	<link rel="icon" type="image/png" href="css/favicons/favicon-16x16.png" sizes="16x16">
	<link rel="manifest" href="css/favicons/manifest.json">
	<link rel="mask-icon" href="css/favicons/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="css/favicons/favicon.ico">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="apple-mobile-web-app-title" content="TCP Lighting">
	<meta name="application-name" content="TCP Lighting">
	<meta name="msapplication-config" content="css/favicons/browserconfig.xml">
	<meta name="theme-color" content="#ffffff">
	<link rel="stylesheet" href="css/jquery-ui.min.css" />
	<link rel="stylesheet" href="css/style.css" />
	<script src="js/libs.js"></script>
	<script>
		//local IP
		<?php
			if( USE_LOCAL_API_IP ){
				global $REMOTE_IP;
				if( isLocalIPAddress($REMOTE_IP) ){
					?>
					var API_IP = '<?php echo LOCAL_URL; ?>';
					<?php
				}else{
				?>
				var API_IP = '<?php echo $_SERVER['REQUEST_URI']; ?>';
				<?php
				}
			}else{
			?>
			var API_IP = '<?php echo $_SERVER['REQUEST_URI']; ?>';
			<?php
			}
		?>
	</script>
	<script src="js/scripts.js"></script>
    
	<title><?php echo $title; ?></title>
</head>
<body>
<div id="headerBar">
	<!-- check if not index -->
	<?php if( $CURRENT_PAGE_SCRIPT != "index" ){ ?>
		<a href="index.php">Home</a>
		<a href="index.php#scenes">Scenes</a>
	<?php }else{ ?>
		<a href="#">Home</a>
		<a href="#scenes">Scenes</a>
	<?php } ?>
	<a target="_blank" href="https://github.com/dracoqcca/TCPLightingWebInterface-MQTT">GitHub Link</a>
	<?php if( LOG_ACTIONS == 1 || LOG_API_CALLS == 1 || LOG_BRIDGE_ERRORS == 1){
		echo '<a href="viewLogs.php">View Logs</a>';
	}
	?>
</div>
	<?php
}

function pageFooter(){
	$settings = getSettings();
	$autoReboot = isset($settings["autoRebootEnabled"]) ? $settings["autoRebootEnabled"] : true;
?>
	<div id="toolBar"><a href="scheduler.php">Lighting Scheduler</a> | <a href="createDevice.php">Create Virtual Device</a> | <a href="setDateTime.php">Set Bridge Date Time</a> | <a href="discoverBulbs.php">Search for New Bulbs. (Beta)</a> | <a href="#" id="autoRebootToggle">Auto-Reboot: <span id="autoRebootStatus"><?php echo $autoReboot ? "ON" : "OFF"; ?></span></a></div>
	<script>
	$('#autoRebootToggle').on('click', function(e){
		e.preventDefault();
		var isOn = $('#autoRebootStatus').text() === 'ON';
		$.get('api.php', {fx:'setAutoReboot', val: isOn ? '0' : '1'}, function(resp){
			var data = typeof resp === 'string' ? JSON.parse(resp) : resp;
			$('#autoRebootStatus').text(data.autoRebootEnabled ? 'ON' : 'OFF');
		});
	});
	</script>
</body>
</html>
	<?php
}

if( ! file_exists(LOG_DIR)  ){
	mkdir(  LOG_DIR, 0755, true);
}

define("LOG_MAX_SIZE", 1 * 1024 * 1024); // 1 MB per log file
define("LOG_MAX_BACKUPS", 3);            // keep .1 .2 .3

function rotateLog($filepath){
	if( !file_exists($filepath) || filesize($filepath) < LOG_MAX_SIZE ){
		return;
	}
	for($i = LOG_MAX_BACKUPS - 1; $i >= 1; $i--){
		$older = $filepath . "." . ($i + 1);
		$newer = $filepath . "." . $i;
		if( file_exists($newer) ){
			rename($newer, $older);
		}
	}
	rename($filepath, $filepath . ".1");
	clearstatcache(true, $filepath);
}

function writeLog($filepath, $string){
	rotateLog($filepath);
	file_put_contents($filepath, date('Y-m-d H:i:s ') . $string . "\n", FILE_APPEND | LOCK_EX);
}

function APILog($string){
	if( LOG_API_CALLS == 1 ){
		writeLog(LOG_DIR . DIRECTORY_SEPARATOR . "API-Request.log", $string);
	}
}

function SCHEDLog($string){
	if( LOG_ACTIONS == 1 ){
		writeLog(LOG_DIR . DIRECTORY_SEPARATOR . "Schedule-Actioned.log", $string);
	}
}

function getSettings(){
	$file = dirname(__FILE__) . DIRECTORY_SEPARATOR . "settings.json";
	if(!file_exists($file)){ return array("autoRebootEnabled" => true); }
	$data = json_decode(file_get_contents($file), true);
	return is_array($data) ? $data : array("autoRebootEnabled" => true);
}

function saveSetting($key, $value){
	$file = dirname(__FILE__) . DIRECTORY_SEPARATOR . "settings.json";
	$settings = getSettings();
	$settings[$key] = $value;
	file_put_contents($file, json_encode($settings), LOCK_EX);
}

function BridgeErrorLog($string){
	if( LOG_BRIDGE_ERRORS == 1 ){
		writeLog(LOG_DIR . DIRECTORY_SEPARATOR . "Bridge-Errors.log", $string);
	}
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/*do some checks based on parameters above*/
if( ALLOW_EXTERNAL_API_ACCESS == 1 ){
	//allow external
}else{
	//if not allowing external access, check you're a local IP
	if( ! isLocalIPAddress($REMOTE_IP) ){
		//if not local you can only hit the API
		if(  $CURRENT_PAGE_SCRIPT != "api" ){
			echo "This application is restricted to the internal network.";
			exit;
		}
	}
}

?>
