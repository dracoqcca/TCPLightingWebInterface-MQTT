<?php
include "include.php";
pageHeader("IFTTT Query Builder");
?>
<style>
p{margin: 10px 0; }
.response{ cursor: pointer; }
.response:hover{ color: #f00; }
</style>
<div class="container" style="padding: 20px; background-color: #fff; border: 1px solid #000;">
<p>Use this to assist with creation of the IFTTT web-hooks quickly</p>
<p>This builder is aimed at setting up a Google Assistant Hook which then calls a webhook with the API string. Follow the <a target="_blank" href="https://github.com/sktaylortrash/TCPLightingWebInterface-MQTT/wiki/IFTTT-Integration">guide</a> in the wiki and feel free to post a question in the issues</p>

<p>It is recommended that you use SSL if you're able. This will work on non-standard ports, (ports other than 80/443) but you will need to setup port-forwarding and setup your webserver to listen on those ports. Some <a target="_blank" href="https://github.com/sktaylortrash/TCPLightingWebInterface-MQTT/wiki/Installation">documentation</a> is provided on how to set everything up on a <a target="_blank" href="https://github.com/sktaylortrash/TCPLightingWebInterface-MQTT/wiki/Installation-for-Apache-on-Raspbian">Raspberry Pi / Linux Environment</a>.</p> 



<?php 
if( ALLOW_EXTERNAL_API_ACCESS == 1 ){
?>

<script>
var allowExternalAPI = "<?php echo ALLOW_EXTERNAL_API_ACCESS; ?>";
var externalAddr = "<?php echo EXTERNAL_DDNS_URL; ?>";
var externalPort = "<?php echo EXTERNAL_PORT; ?>";
var externalPass = "<?php echo EXTERNAL_API_PASSWORD; ?>";
var requireExtPass = "<?php echo REQUIRE_EXTERNAL_API_PASSWORD; ?>";

$(function(){
	$('#sceneLights').hide();
	$('#roomLights').hide();
	$('#builder').change(function(){
		var id = $(this).find('option:selected').attr('data-device-id');
		var type = $(this).find('option:selected').attr('data-device-type');
		var roomName = "";
		var preface = $('#preface').val();
		if( preface.length > 0 ){ preface = ' ' + preface + ' '; }
		
		$('#appletImage').html('');
		
		if( type == "room" || type == "light"){
			$('#sceneLights').hide();
			$('#roomLights').show();
			
			roomName = $(this).find('option:selected').attr('data-room-name');
			$('#appletImage').html('<img src="css/images/IFTTTAppletPhrase.png"/>');
			
			if( type == "room"  ){
				$('#command_on_1').html(roomName + preface + " lights ON");
				$('#command_on_2').html("Switch " + roomName + preface + " lights ON");
				$('#command_on_3').html("Turn " + roomName + preface + " lights on");
				$('#command_on_response').html("Turning " + roomName + preface + " lights ON");
				$('#command_on_url').html( externalAddr + ( (externalPort != 80 || externalPort != 443) ?  ':' + externalPort : '' ) + '/api.php?fx=toggle&type=room&uid=' + id + '&val=1' + ( requireExtPass == 1 ? '&password=' + externalPass : '')  );
				
				$('#command_off_1').html(roomName + preface + " lights off");
				$('#command_off_2').html("Switch " + roomName + preface +" Lights off");
				$('#command_off_3').html("Turn " + roomName + preface + " lights off");
				$('#command_off_response').html("Turning off " + roomName + " lights");
				$('#command_off_url').html( externalAddr + ( (externalPort != 80 || externalPort != 443) ?  ':' + externalPort : '' ) + '/api.php?fx=toggle&type=room&uid=' + id + '&val=0' + ( requireExtPass == 1 ? '&password=' + externalPass : '') );
				
				$('#command_dim_1').html(roomName + preface + " lights to # % brightness");
				$('#command_dim_2').html("Dim " + roomName + preface + " lights to # %");
				$('#command_dim_3').html("Set " + roomName + preface + " lights to # %");
				$('#command_dim_response').html("Adjusting " + roomName + " lights");
				$('#command_dim_url').html( externalAddr + ( (externalPort != 80 || externalPort != 443) ?  ':' + externalPort : '' ) + '/api.php?fx=dim&type=room&uid=' + id + '&val={{NumberField}}' + ( requireExtPass == 1 ? '&password=' + externalPass : '') );
				
				$('#command_brighten_1').html("Increase " +  roomName +  preface + " lights brightness by # %");
				$('#command_brighten_2').html("Brighten " + roomName + preface + " lights by # %");
				$('#command_brighten_3').html("Up " + roomName + preface + " lights brightness # %");
				$('#command_brighten_response').html("Brightening " + roomName + " lights");
				$('#command_brighten_url').html( externalAddr + ( (externalPort != 80 || externalPort != 443) ?  ':' + externalPort : '' ) + '/api.php?fx=brightenby&type=room&uid=' + id + '&val={{NumberField}}' + ( requireExtPass == 1 ? '&password=' + externalPass : '') );
				
				$('#command_dimby_1').html("Decrease " +  roomName + preface + " lights brightness by # %");
				$('#command_dimby_2').html("Darken " + roomName + preface +" lights by # %");
				$('#command_dimby_3').html("Reduce " + roomName + preface + " lights brightness by # %");
				$('#command_dimby_response').html("Dimming " + roomName + " lights");
				$('#command_dimby_url').html( externalAddr + ( (externalPort != 80 || externalPort != 443) ?  ':' + externalPort : '' ) + '/api.php?fx=dimby&type=room&uid=' + id + '&val={{NumberField}}' + ( requireExtPass == 1 ? '&password=' + externalPass : '') );
			}
			
			if( type == "light" ){
				roomName = $(this).find('option:selected').attr('data-device-name');
				
				$('#command_on_1').html( roomName + preface + " light ON" );
				$('#command_on_2').html("Switch " + roomName + preface + " light ON");
				$('#command_on_3').html("Turn " + roomName + preface + " light on");
				$('#command_on_response').html("Turning " + roomName + " light ON");
				$('#command_on_url').html( externalAddr + ( (externalPort != 80 || externalPort != 443) ?  ':' + externalPort : '' ) + '/api.php?fx=toggle&type=device&uid=' + id + '&val=1' + ( requireExtPass == 1 ? '&password=' + externalPass : '')  );
				
				$('#command_off_1').html( roomName + preface + " light off" );
				$('#command_off_2').html("Switch " + roomName + preface + " light off");
				$('#command_off_3').html("Turn " + roomName + preface + " light off");
				$('#command_off_response').html("Turning off " + roomName + " light");
				$('#command_off_url').html( externalAddr + ( (externalPort != 80 || externalPort != 443) ?  ':' + externalPort : '' ) + '/api.php?fx=toggle&type=device&uid=' + id + '&val=0' + ( requireExtPass == 1 ? '&password=' + externalPass : '') );
				
				$('#command_dim_1').html( roomName +  preface + " light to # % brightness" );
				$('#command_dim_2').html("Dim " + roomName + preface + " light to # %");
				$('#command_dim_3').html("Set " + roomName + preface + " light brightness to # %");
				$('#command_dim_response').html("Adjusting " + roomName + " brightness");
				$('#command_dim_url').html( externalAddr + ( (externalPort != 80 || externalPort != 443) ?  ':' + externalPort : '' ) + '/api.php?fx=dim&type=device&uid=' + id + '&val={{NumberField}}' + ( requireExtPass == 1 ? '&password=' + externalPass : '') );
				
				
				$('#command_brighten_1').html("Increase " +  roomName +  preface + " light brightness by # %");
				$('#command_brighten_2').html("Brighten " + roomName + preface + " light by # %");
				$('#command_brighten_3').html("Up " + roomName + preface + " light brightness to # %");
				$('#command_brighten_response').html("Brightening " + roomName + " light");
				$('#command_brighten_url').html( externalAddr + ( (externalPort != 80 || externalPort != 443) ?  ':' + externalPort : '' ) + '/api.php?fx=brightenby&type=device&uid=' + id + '&val={{NumberField}}' + ( requireExtPass == 1 ? '&password=' + externalPass : '') );
				
				$('#command_dimby_1').html("Decrease " +  roomName + preface + " light brightness by # %");
				$('#command_dimby_2').html("Darken " + roomName + preface +" light by # %");
				$('#command_dimby_3').html("Reduce " + roomName + preface + " light brightness by # %");
				$('#command_dimby_response').html("Dimming " + roomName + " light");
				$('#command_dimby_url').html( externalAddr + ( (externalPort != 80 || externalPort != 443) ?  ':' + externalPort : '' ) + '/api.php?fx=dimby&type=device&uid=' + id + '&val={{NumberField}}' + ( requireExtPass == 1 ? '&password=' + externalPass : '') );
			}
			
			
			
		}
		
		if( type == "scene" ){
			$('#sceneLights').show();
			$('#roomLights').hide();
			var sceneName = $(this).find('option:selected').attr('data-device-name');
						$('#appletImage').html('<img src="css/images/IFTTTAppletPhrase.png"/>');
			
			$('#scene_command_on_1').html("Activate " + sceneName + preface +" scene" );
			$('#scene_command_on_2').html("Turn " + sceneName + preface +  " scene ON");
			$('#scene_command_on_3').html(sceneName + preface + " scene on");
			$('#scene_command_on_response').html("Turning " + sceneName + preface + " devices ON");
			
			$('#scene_command_on_url').html( externalAddr + ( externalPort != 80 ?  ':' + externalPort : '' ) + '/api.php?fx=scene&type=1&uid=' + id + '' +( requireExtPass == 1 ? '&password=' + externalPass : '')  );
			
			$('#scene_command_off_1').html("Deactivate " +  sceneName + preface + " scene");
			$('#scene_command_off_2').html("Turn " + sceneName + preface + " scene off");
			$('#scene_command_off_3').html(sceneName  + preface + " scene off");
			$('#scene_command_off_response').html("Turning " + sceneName + preface + " devices off");
			
			$('#scene_command_off_url').html( externalAddr + ( externalPort != 80 ?  ':' + externalPort : '' ) + '/api.php?fx=scene&type=0&uid=' + id + "" + ( requireExtPass == 1 ? '&password=' + externalPass : '') );
			
			$('#command_run_1').html("Activate " +  sceneName + preface +" scene");
			$('#command_run_2').html("Turn " + sceneName + preface + " scene on");
			$('#command_run_3').html(sceneName + " scene on");
			$('#command_run_response').html("Running " + preface + sceneName + " scene");
			
			$('#command_run_url').html( externalAddr + ( externalPort != 80 ?  ':' + externalPort : '' ) + '/api.php?fx=scene&type=run&uid=' + id + "" + ( requireExtPass == 1 ? '&password=' + externalPass : '') );
			
		}

	});
	
	$('.response').click(function(event){
		event.preventDefault();
		 var $temp = $("<input>");
		$("body").append($temp);
		$temp.val( $(this).text() ).select();
		document.execCommand("copy");
		$temp.remove();

	});
	
});
</script>
<p>Click on the <b>bolded</b> suggested responses to copy them to your clipboard</p>
<p>Preface TCP Light commands with: <input type="text" id="preface" value="TCP" placeholder="TCP" /> (Can be helpful if you have multiple lighting systems with similar naming conventions.</p>
<p>
<select id="builder">
	<option>Select a Device | Room | Scene</option>
<?php
	$DATA = getRoomCarouselData();
	$DEVICES = array();
	if( $DATA !== null && sizeof($DATA) > 0 ){
		echo '<optgroup label="Rooms">';
		foreach($DATA as $room){
			echo '<option data-device-type="room" data-device-id="'.$room["rid"].'" data-room-name="'.$room["name"].'">'    .  $room["name"] .    '</option>';
			$roomDevices = extractDevicesFromRoom($room);
			foreach($roomDevices as $dev){
				$DEVICES[] = '<option data-device-name="'.$dev["name"].'" data-device-type="light" data-device-id="'.$dev["did"].'" data-room-name="'.$room["name"].'">'. $dev["name"] ."</option>";
			}
		}
		echo '</optgroup>';
	}
	
	echo '<optgroup label="Devices">';
	foreach($DEVICES as $device){
		echo $device;
	}
	echo '</optgroup>';
	
	$CMD = buildGipCommand("SceneGetListDetails", "<bigicon>1</bigicon>");
	$result = getCurlReturn($CMD);
	$array = xmlToArray($result);
	$scenes = $array["scene"];
	if( is_array($scenes) ){
		echo '<optgroup label="Scenes">';
		for($x = 0; $x < sizeof($scenes); $x++){
			echo '<option  data-device-type="scene" data-device-id="'.$scenes[$x]["sid"] .'" data-device-name="'.$scenes[$x]["name"] .'">'.$scenes[$x]["name"] . " - " . $scenes[$x]["sid"] . "</option>";
		}
		echo '</optgroup>';
	}
	
?>


</select>
</p>
<br />
<style>
#instructions p{ margin: 10px 0; }
#select{ margin-bottom: 10px; }
.response{ font-weight: bold; font-style: italic; font-size: 16px; }

</style>
<div id="instructions">
	<div id="appletImage" style="float: left; margin-right: 20px; overflow: hidden;">
	
    </div>
    <div class="fields">
    	<div id="roomLights">
            <h1>On Commands</h1>
            <p>What do you want to say: <span class="response" id="command_on_1"></span></p>
            <p>Whats another way to say it? (Optional): <span class="response" id="command_on_2"></span></p>
            <p>And another way?(optional): <span class="response" id="command_on_3"></span></p>
            <p>What do you want the Assistant to say in Response: <span class="response" id="command_on_response"></span></p>
            <p>URL: <span class="response" id="command_on_url"></span></p>
            <p>Method: Get</p>
            
            <h1>Off Commands</h1>
            <p>What do you want to say: <span class="response" id="command_off_1"></span></p>
            <p>Whats another way to say it? (Optional): <span class="response" id="command_off_2"></span></p>
            <p>And another way?(optional): <span class="response" id="command_off_3"></span></p>
            <p>What do you want the Assistant to say in Response: <span class="response" id="command_off_response"></span></p>
            <p>URL: <span class="response" id="command_off_url"></span></p>
            <p>Method: Get</p>
            
            <h1>Dim To Command (Say a phrase with a number)</h1>
            <p>What do you want to say: <span class="response" id="command_dim_1"></span></p>
            <p>Whats another way to say it? (Optional): <span class="response" id="command_dim_2"></span></p>
            <p>And another way?(optional): <span class="response" id="command_dim_3"></span></p>
            <p>What do you want the Assistant to say in Response: <span class="response" id="command_dim_response"></span></p>
            <p>URL: <span class="response" id="command_dim_url"></span></p>
            <p>Method: Get</p>
			
			<h1>Brighten By (Say a phrase with a number)</h1>
            <p>What do you want to say: <span class="response" id="command_brighten_1"></span></p>
            <p>Whats another way to say it? (Optional): <span class="response" id="command_brighten_2"></span></p>
            <p>And another way?(optional): <span class="response" id="command_brighten_3"></span></p>
            <p>What do you want the Assistant to say in Response: <span class="response" id="command_brighten_response"></span></p>
            <p>URL: <span class="response" id="command_brighten_url"></span></p>
            <p>Method: Get</p>
			
			<h1>Dim By (Darken By) (Say a phrase with a number)</h1>
            <p>What do you want to say: <span class="response" id="command_dimby_1"></span></p>
            <p>Whats another way to say it? (Optional): <span class="response" id="command_dimby_2"></span></p>
            <p>And another way?(optional): <span class="response" id="command_dimby_3"></span></p>
            <p>What do you want the Assistant to say in Response: <span class="response" id="command_dimby_response"></span></p>
            <p>URL: <span class="response" id="command_dimby_url"></span></p>
            <p>Method: Get</p>
			
			
			<!-- Brighten By, Dim By -->
			
    	</div>
        
        <div id="sceneLights">
            <h1>Run Command</h1>
            <p>What do you want to say: <span class="response" id="command_run_1"></span></p>
            <p>Whats another way to say it? (Optional): <span class="response" id="command_run_2"></span></p>
            <p>And another way?(optional): <span class="response" id="command_run_3"></span></p>
            <p>What do you want the Assistant to say in Response: <span class="response" id="command_run_response"></span></p>
            <p>URL: <span class="response" id="command_run_url"></span></p>
            <p>Method: Get</p>
            
            <h1>Off Command</h1>
            <p>What do you want to say: <span class="response" id="scene_command_off_1"></span></p>
            <p>Whats another way to say it? (Optional): <span class="response" id="scene_command_off_2"></span></p>
            <p>And another way?(optional): <span class="response" id="scene_command_off_3"></span></p>
            <p>What do you want the Assistant to say in Response: <span class="response" id="scene_command_off_response"></span></p>
            <p>URL: <span class="response" id="scene_command_off_url"></span></p>
            <p>Method: Get</p>
            
            <h1>All On Command</h1>
            <p>What do you want to say: <span class="response" id="scene_command_on_1"></span></p>
            <p>Whats another way to say it? (Optional): <span class="response" id="scene_command_on_2"></span></p>
            <p>And another way?(optional): <span class="response" id="scene_command_on_3"></span></p>
            <p>What do you want the Assistant to say in Response: <span class="response" id="scene_command_on_response"></span></p>
            <p>URL: <span class="response" id="scene_command_on_url"></span></p>
            <p>Method: Get</p>
        </div>
    </div>
	<div class="clear"></div>
</div>



<?php
}else{
	echo '<p>To use IFTTT you must enable the external API access</p>';
}
?>


</div>



<?php
pageFooter();
?>
