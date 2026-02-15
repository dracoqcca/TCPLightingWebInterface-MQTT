$(function(){
	//run scene, scene on, scene off function in main js file.
	$('.scene-slider').slider({
		range: "min",
		min: 0,
		max: 100,
		create: function( event, ui ){
			$(this).slider("option", "value", $(this).parent().find("input[name='DIM_SCENE']").val() );
		},
		stop: function(event, ui) {
			$(this).parent().find('input[name="DIM_SCENE"]').val( ui.value );
		},
		slide: function( event, ui ) {
			$(this).parent().find('input[name="DIM_SCENE"]').val( ui.value );
		}
	});

	$('.roomToggle').change(function(){
		if( $(this).prop('checked') == true){
			$(this).closest('.roomSceneContainer').find('.room-controls').removeClass('controls-toggled-0').addClass('controls-toggled-1');
			$(this).closest('.roomSceneContainer').find('.room-devices').removeClass('room-devices-toggled-0').addClass('room-devices-toggled-1');
		}else{
			$(this).closest('.roomSceneContainer').find('.room-controls').removeClass('controls-toggled-1').addClass('controls-toggled-0');
			$(this).closest('.roomSceneContainer').find('.room-devices').removeClass('room-devices-toggled-1').addClass('room-devices-toggled-0');
			$(this).closest('.roomSceneContainer').find('.deviceToggle').prop('checked', false);
		}
	});

	$('.deviceToggle').change(function(){
		if( $(this).prop('checked') == true){
			$(this).closest('.roomDeviceContainer').find('.device-controls').removeClass('controls-toggled-0').addClass('controls-toggled-1');
		}else{
			$(this).closest('.roomDeviceContainer').find('.device-controls').removeClass('controls-toggled-1').addClass('controls-toggled-0');
		}
	});

	$('.control-switch').click(function(event){
		event.preventDefault();
		if( $(this).hasClass('switch-val-1') ){
			$(this).removeClass('switch-val-1').addClass('switch-val-0');
			$(this).find('input[type="checkbox"]').prop('checked', false);
			$(this).find('input[type="checkbox"]').attr('value', 0 );
		}else{
			$(this).removeClass('switch-val-0').addClass('switch-val-1');
			$(this).find('input[type="checkbox"]').prop('checked', true);
			$(this).find('input[type="checkbox"]').attr('value',1);
		}
	});


	$('#saveScene').click(function(event){
		event.preventDefault();


		$('.switch-val-0 .switch input.device-toggle').each(function(){
			$(this).attr('value', 0);
		});

		var rooms = [];
		var devices = [];


		$('.roomSceneContainer').each(function(){

			var rid = $(this).attr('data-room-id');
			var rname = $(this).attr('data-room-name');
			var ron = "";
			var rval = "";


			if( $(this).find(' > .EnabledOrNot > input.roomToggle').prop('checked') == true ){

				if( $(this).find('.control-switch input.device-toggle').prop('checked') == true ){
					ron = 1;
					rval = $(this).find('.control-slider input.value-slider').attr('value');

				}else{
					ron = 0;
					rval = 0;
				}

				rooms.push({ rid: rid, name: rname, toggled : ron, value: rval, renabled: 1  });

			}else{
				$(this).find('.roomDeviceContainer').each(function(){
					var did = $(this).attr('data-device-id');
					var don = "";
					var dval = "";


					var dname = $(this).find(' > p').html();
					if( $(this).find('.EnabledOrNot > input.deviceToggle').prop('checked') == true ){
						if( $(this).find('.control-switch input.device-toggle').attr('value') == 1 ){
							don = 1;

							dval = $(this).find('.control-slider input.value-slider').attr('value');
						}else{
							don = 0;
							dval = 0;
						}

						devices.push( { did: did, name: dname, toggled: don, value: dval, enabled: 1 } );
					}
				});
			}

		});

		var sID = $('#sceneID').val();
		var sName = $('#SceneName').val();
		var icon = $('#sceneIcon option:selected').attr('value');
		var enabled = $('input[name="sceneActive"]:checked').attr('value');
		var schedule = 0;

		if( $('.sched-switch input').prop("checked") ){
			var startTime = ( $('#startHour').val() == "sunrise" || $('#startHour').val() == "sunset" ) ? $('#startHour').val() : $('#startHour').val() + ':' + $('#startMin').val();
			var stopTime = ( $('#stopHour').val() == "sunrise" || $('#stopHour').val() == "sunset" ) ? $('#stopHour').val() : $('#stopHour').val() + ':' + $('#stopMin').val();

			if( startTime == stopTime && startTime != -1 ){
				window.alert("Scheduled start time cannot be the same as stop time.");
				return;
			}

			var every = "";

			$('input[name="every"]').each(function(){
				if( $(this).prop("checked") ){
					every = every + $(this).val() + ",";
				}
			});

			if( every.length > 1){
				every = every.slice(0, -1);
			}

			if( every == "" ){
				window.alert("If you wish to use the scene schedule, you must specify atleast one day.");
				return;
			}

			schedule = {
				'starttime' : startTime,
				'stoptime' : stopTime,
				'every' : every
			};


		}

		var scene = {action : "save", sceneID: sID, name: sName, icon: icon,  rooms: rooms, devices: devices, active: enabled, schedule : schedule };

		$.post( "scenescreatedit.php",  scene ).done( function( data ){
			var json =  jQuery.parseJSON( data );
			if( json.resp.rc == 200 ){
				window.alert("Scene Saved");
				$('#scene-id-' + sID ).removeClass("scene-disabled scene-enabled");
				if( enabled == 1 ){
					$('#scene-id-' + sID ).addClass("scene-enabled");
				}else{
					$('#scene-id-' + sID ).addClass("scene-disabled");
				}
			}

			if( sID != json.resp.sid && sID == -1){
				window.location = "scenescreatedit.php?SID=" + json.resp.sid;

			}

		});

	});

	$('#save2').click(function(event){
		event.preventDefault();
		$('#saveScene').click();
	});


	$('#deleteScene').click(function(event){
		event.preventDefault();
		var SID = $('#sceneID').attr('value');
		var SName = $('#SceneName').attr('value');

		var c = confirm("Are you sure you wish to delete the scene: '" + SName + "' Scene ID: " + SID + "?" );

		if ( c == true ){
			$.post( "scenescreatedit.php",  { action: 'delete', sceneID: SID } ).done( function( data ){
				var resp =  jQuery.parseJSON( data );
				if( resp.resp.rc == 200 ){
					window.alert("Scene Deleted OK!");
					window.location = "index.php#scenes";
				}else{
					window.alert("There may have been an error in deleting the scene.");
				}
			});
		}
	});

	$('#sceneDisabled').click(function(event){
		event.preventDefault();
		var SID = $('#sceneID').attr('value');
		$.post( "scenescreatedit.php",  { action: 'disable', sceneID: SID } ).done( function( data ){
			var resp =  jQuery.parseJSON( data );
			if( resp.resp.rc == 200 ){
				window.alert("Scene Disabled!");
			}else{
				window.alert("There may have been an error in disabling the scene.");
			}
		});
	});

	$('#sceneIcon').change(function(event){
		event.preventDefault();
		var icon = $('#sceneIcon option:selected').attr('value');
		$('#icon').attr('src', 'css/images/scene/' + icon );
	});

	$('#startHour, #stopHour').change(function(){
		if( $(this).val() == "sunrise" || $(this).val() == "sunset" || $(this).val() == "-1" ){
			$(this).parent().find("select").not($(this)).attr("disabled","disabled");
		}else{
			$(this).parent().find("select").not($(this)).removeAttr("disabled","disabled");
		}
	});


	$('.sched-switch input').change(function(){
		var toggled = $(this).prop("checked");
		if( toggled ){
			$('#schedule').removeClass('scheduleEnabled-0').addClass('scheduleEnabled-1');
		}else{
			$('#schedule').removeClass('scheduleEnabled-1').addClass('scheduleEnabled-0');
		}
	});
});
