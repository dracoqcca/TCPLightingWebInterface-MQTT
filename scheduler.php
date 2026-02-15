<?php
	include "include.php";
	pageHeader("TCP Lighting Scheduler");
?>
	<script>
	<?php ob_start(); getScheduleView("__REPLACE__");$schedule = ob_get_clean(); ?>
	var addSchedule = '<?php echo preg_replace( "/\r|\n/", "",$schedule); ?>';
	</script>
	<script src="js/scheduler.js"></script>

<?php
	/*
	 *
	 * TCP Ligthing Web UI Test Script - By Brendon Irwin
	 *

	 */

	function getScheduleView($task=array()){
		if( $task == "__REPLACE__"){
			$randID = "__REPLACE__";
		}else{
			$randID = generateRandomString();
		}
		?>
		<form class="scheduledTask">
		<div class="delTask">X</div>
			<div class="taskNote">
				<p>Schedule title/note:</p>
				<textarea name="TITLE_NOTE"><?php echo (isset($task["TITLE_NOTE"])) ? $task["TITLE_NOTE"] : ""; ?></textarea>
			</div>
			<div class="daysOfWeek">
				<label for="<?php echo $randID; ?>_MON"><input id="<?php echo $randID; ?>_MON" type="checkbox" name="DAY_MON" <?php echo (isset($task["DAY_MON"]) && $task["DAY_MON"] == "on") ? " checked" : "";  ?>/> Monday</label><br />
				<label for="<?php echo $randID; ?>_TUE"><input id="<?php echo $randID; ?>_TUE" type="checkbox" name="DAY_TUE" <?php echo (isset($task["DAY_TUE"]) && $task["DAY_TUE"] == "on") ? " checked" : "";  ?>/> Tuesday</label><br />
				<label for="<?php echo $randID; ?>_WED"><input id="<?php echo $randID; ?>_WED" type="checkbox" name="DAY_WED" <?php echo (isset($task["DAY_WED"]) && $task["DAY_WED"] == "on") ? " checked" : "";  ?>/> Wednesday</label><br />
				<label for="<?php echo $randID; ?>_THU"><input id="<?php echo $randID; ?>_THU" type="checkbox" name="DAY_THU" <?php echo (isset($task["DAY_THU"]) && $task["DAY_THU"] == "on") ? " checked" : "";  ?>/> Thursday</label><br />
				<label for="<?php echo $randID; ?>_FRI"><input id="<?php echo $randID; ?>_FRI" type="checkbox" name="DAY_FRI" <?php echo (isset($task["DAY_FRI"]) && $task["DAY_FRI"] == "on") ? " checked" : "";  ?>/> Friday</label><br />
				<label for="<?php echo $randID; ?>_SAT"><input id="<?php echo $randID; ?>_SAT" type="checkbox" name="DAY_SAT" <?php echo (isset($task["DAY_SAT"]) && $task["DAY_SAT"] == "on") ? " checked" : "";  ?>/> Saturday</label><br />
				<label for="<?php echo $randID; ?>_SUN"><input id="<?php echo $randID; ?>_SUN" type="checkbox" name="DAY_SUN" <?php echo (isset($task["DAY_SUN"]) && $task["DAY_SUN"] == "on") ? " checked" : "";  ?>/> Sunday</label><br />
				<label for=""<?php echo $randID; ?>_ALL><input id="<?php echo $randID; ?>_ALL" type="checkbox" name="DAY_ALL" <?php echo (isset($task["DAY_ALL"]) && $task["DAY_ALL"] == "on") ? " checked" : "";  ?>/> Everyday</label>
			</div>
			<div class="timeOfDay">
				<label>Time:
					<select name="TIME_TYPE"><option value="FIXED" <?php echo (isset($task["TIME_TYPE"]) && $task["TIME_TYPE"] == "FIXED") ? " selected" : ""; ?>>FIXED</option><option value="SUNRISE" <?php echo (isset($task["TIME_TYPE"]) && $task["TIME_TYPE"] == "SUNRISE") ? " selected" : ""; ?>>SUNRISE</option><option value="SUNSET" <?php echo (isset($task["TIME_TYPE"]) && $task["TIME_TYPE"] == "SUNSET") ? " selected" : ""; ?>>SUNSET</option>
					</select>
				</label>
                  </div>

			<div class="timeType">

<?php /***
       *
       *    Added Sunrise-Sunset settings
       *    2017-06-09 by Andrew Tsui
       *
       */
?>

				<div class="ifSunTime">


					<label>Offset Hours: <select name="OFFSET_HOUR"><?php for($x=0; $x<=12; $x++){ echo '<option value="'.sprintf("%02d",$x).'"'.( (isset($task["OFFSET_HOUR"]) && $task["OFFSET_HOUR"] == $x) ? " selected" : "").'>'.sprintf("%02d",$x).'</option>'; }
						?>
						</select>
					</label>
					<label>Minutes:
						<select name="OFFSET_MIN">
						<?php for($x=0; $x<=59; $x++){ echo '<option value="'.sprintf("%02d",$x).'"'.( (isset($task["OFFSET_MIN"]) && $task["OFFSET_MIN"] == $x) ? " selected" : "").'>'.sprintf("%02d",$x).'</option>'; }
						?>
						</select>
					</label>
					<label>
						<select name="OFFSET_DIR"><option value="before" <?php echo (isset($task["OFFSET_DIR"]) && $task["OFFSET_DIR"] == "before") ? " selected" : ""; ?>>before</option><option value="after" <?php echo (isset($task["OFFSET_DIR"]) && $task["OFFSET_DIR"] == "after") ? " selected" : ""; ?>>after</option>
						</select>
					</label>

				</div>

				<div class="ifFixedTime">
					<label>Hour:
					<select name="HOUR">
					<?php for($x=0; $x<=23; $x++){
						if( $x == 0 ){
							echo '<option value="'.sprintf("%02d",$x).'"'.( (isset($task["HOUR"]) && $task["HOUR"] == $x) ? " selected" : "").'>12 AM - MIDNIGHT</option>';
						}else if($x == 12){
							echo '<option value="'.sprintf("%02d",$x).'"'.( (isset($task["HOUR"]) && $task["HOUR"] == $x) ? " selected" : "").'>12 PM - NOON</option>';
						}else{
							if( $x > 12 ){
								echo '<option value="'.sprintf("%02d",$x).'"'.( (isset($task["HOUR"]) && $task["HOUR"] == $x) ? " selected" : "").'>'. ($x - 12) .( $x >= 12 ? ' PM' : ' AM' ).'</option>';
								}else{
									echo '<option value="'.sprintf("%02d",$x).'"'.( (isset($task["HOUR"]) && $task["HOUR"] == $x) ? " selected" : "").'>'.$x.( $x >= 12 ? ' PM' : ' AM' ).'</option>';
							}
						}
					} ?>
					</select>
					</label>

					<label>Minute:
					<select name="MIN">
					<?php for($x=0; $x<=59; $x++){
						echo '<option value="'.sprintf("%02d",$x).'"'.( (isset($task["MIN"]) && $task["MIN"] == $x) ? " selected" : "").'>'.sprintf("%02d",$x).'</option>';
					}
					?>
					</select>
					</label>
				</div>
			</div>

			<div class="functionTrigger">
				<label>Function: <select name="FX"><option value="DIM" <?php echo (isset($task["FX"]) && $task["FX"] == "DIM") ? " selected" : ""; ?>>DIM</option><option value="SWITCH" <?php echo (isset($task["FX"]) && $task["FX"] != "DIM") ? " selected" : ""; ?>>SWITCH</option></select></label>
			</div>
			<div class="fxTo">
				<div class="ifDim">
					<div class="schedule-slider" data-device-id="all"></div>
					<input name="DIM_SCHED" type="hidden" value="<?php echo (isset($task["FX"]) && $task["FX"] == "DIM") ? $task["DIM_SCHED"] : ""; ?>" />
				</div>
				<div class="ifSwitch">
					<label class="switch">
					  <input name="SWITCH_SCHED" value="1" type="checkbox" <?php echo (isset($task["FX"]) && $task["FX"] != "DIM" && $task["SWITCH_SCHED"] == 1) ? "checked" : ""; ?>>
					  <div class="slider round"></div>
					</label>
				</div>
			</div>
			<div class="deviceList">
			<table>
				<tr>
					<td>Available</td><td>&nbsp;</td><td>Selected</td>
				</tr>
				<tr>
					<td>
						<select size="9" class="available" name="DEVICE_AVAILABLE" multiple>
						<?php
							$DATA = getRoomCarouselData();
							if( $DATA !== null && sizeof($DATA) > 0 ){
								foreach($DATA as $room){
									echo '<optgroup label="Room - '.h($room["name"]).'">';
									$roomDevices = extractDevicesFromRoom($room);
									foreach($roomDevices as $dev){
										if( isset($task) && isset($task["devices"]) && is_array($task["devices"]) && in_array($dev['did'], $task["devices"]) ){
											$selected.= '<option value="'.$dev['did'].'" selected>'.h($dev['prodtype']).' - '.h($dev['name']).'</option>';
										}else{
											echo '<option value="'.$dev['did'].'">'.h($dev['prodtype']).' - '.h($dev['name']).'</option>';
										}
									}
									echo '</optgroup>';
								}
							}
						?>
						</select>
					</td>
					<td>
						<input type="Button" value="Add >>" class="btnAdd"><br />
						<br />
						<input type="Button" value="<< Remove" class="btnRemove">
					</td>
					<td>
						<select size="9" class="selected" name="DEVICE_SELECTED" multiple>
						<?php
							if( isset($selected) && $selected != ""){
								echo $selected;
							}
						?>
						</select>
					</td>
				</tr>
			</table>
                  </div>
		</form>


<?php
}
?>
	<div class="container" style="background-color: #fff; padding: 20px;">
		<h1>Device Scheduler <span style="font-size: 10px;">an alternative to using <a href="scenescreatedit.php?SID=-1">scenes</a></span></h1>
		<p><button id="runOnce">Run Now</button> <button id="poll">Poll continuously</button></p>
		<p>Note, polling continuously will work if the tab this script is running on has focus. Consider setting up a batch file to run the schedule. See documentation and runschedule.bat</p>
		<p>Tasks can be dragged and dropped to re-order. Just click save after.</p>
		<?php
			$sun_info = date_sun_info(time(), LATITUDE, LONGITUDE);
			echo "Sunrise Today: " . date("H:i", $sun_info['sunrise']) . "    ";
			echo "Sunset Today:  " . date("H:i", $sun_info['sunset'])  . "<br />";
		?>
	</div>


<div id="events" class="container">
	<?php
		if( file_exists("schedule.sched") ){
			$array = file_get_contents("schedule.sched");
			$tasks = json_decode($array, true);
			if(!is_array($tasks)){ $tasks = unserialize($array); }
			ob_start();
			foreach($tasks as $task){
				getScheduleView($task);
			}
			ob_end_flush();
		}else{
			echo $schedule;
		}
	?>
 </div>
 <div class="container">
 <button id="save">Save</button>
 <button id="add">Add Task</button>
 </div>

<?php
  pageFooter();
?>
