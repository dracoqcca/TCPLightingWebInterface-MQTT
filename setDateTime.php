<?php
include "include.php";
pageHeader("TCP Lighting Controller");

if( TOKEN != "" ){
?>
<div class="container" style="padding: 20px; background-color: #fff;">
<h3>Set Date Time</h3><br />
<?php	
	if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST'){
		
		if( isset($_POST) && isset($_POST['dateTime']) && $_POST['dateTime'] != "" ){
		$time = $_POST['dateTime'];
			$CMD = buildGipCommand("AccountSetDateTime", "<datetime>".xmlEscape($time).":00</datetime>");
			$result = getCurlReturn($CMD);
			$array = xmlToArray($result);
		}
		
	}
	
	//Get State of System Data
	$CMD = "cmd=GWRBatch&data=<gwrcmds><gwrcmd><gcmd>AccountGetExtras</gcmd><gdata><gip><version>1</version><token>".TOKEN."</token></gip></gdata></gwrcmd></gwrcmds>&fmt=xml";
	$result = getCurlReturn($CMD);

	$array = xmlToArray($result);

	$time = $array["gwrcmd"]["gdata"]["gip"]["datetime"];
	
	//Get State of System Data
	
	?>
	<script>
		$(function(){
			$('#datetimepicker').datetimepicker({
				inline:false,
				dateFormat: "yy-mm-dd",
				timeFormat: "HH:mm:ss",
				Separator: " "
			});
		});
	</script>
	<form method="post" action="setDateTime.php">
	<p>Keep in mind that the bridge uses UTC time. Based on your timezone, you should probably set the time to: <?php echo gmdate("Y-m-d H:i:s"); ?></p>
		Set Time: <input id="datetimepicker" name="dateTime" type="text" value="<?php echo $time; ?>"/>
		<input type="submit" value="Save" />
	</form>
	<?php
	echo '<br /><h3>Other Info</h3><br /><hr /><br />';
	
	
	
	$CMD = "cmd=GWRBatch&data=<gwrcmds><gwrcmd><gcmd>AccountGetExtras</gcmd><gdata><gip><version>1</version><token>".TOKEN."</token></gip></gdata></gwrcmd></gwrcmds>&fmt=xml";
	$result = getCurlReturn($CMD);
	$array = xmlToArray($result);
	$time = $array["gwrcmd"]["gdata"]["gip"]["datetime"];
	echo "<p>Bridge Time: " . $time. "</p>";
	
	
	$CMD = "cmd=GWRBatch&data=<gwrcmds><gwrcmd><gcmd>GatewayGetInfo</gcmd><gdata><gip><version>1</version><token>".TOKEN."</token></gip></gdata></gwrcmd></gwrcmds>&fmt=xml";
	$result = getCurlReturn($CMD);
	$array = xmlToArray($result);
	$array =  $array["gwrcmd"]["gdata"]["gip"]["gateway"];
	
	
}
?>
</div>
<?php
pageFooter();
?>
