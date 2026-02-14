//check API path

function setDeviceLightState($device, isOn){
	if(isOn){
		$device.addClass('light-on');
		var v = $device.find('.device-slider').slider("value");
		$device.css('background-color', 'rgba(255, 200, 50,' + (v / 100) + ')');
	}else{
		$device.removeClass('light-on');
		$device.css('background-color', 'transparent');
	}
}

$(function(){
	$('.room-slider').slider({
		range: "min",
		min: 0,
		max: 100,
		value: 50,
		create: function( event, ui ){
			$(this).slider("option", "value", $(this).attr('data-value') );
			var val = $(this).slider("value");
			$(this).after('<span class="slider-value">' + val + '%</span>');
		},
		slide: function( event, ui ){
			$(this).next('.slider-value').text(ui.value + '%');
		},
		stop: function(event, ui) {
			$(this).next('.slider-value').text(ui.value + '%');
			var room = $(this).parent().parent();
			$.get("api.php?fx=dim&type=room&uid=" + $(this).attr('data-room-id') + "&val=" + ui.value, function( data ) {
				$(room).find('.device-slider').each(function(){
					$(this).slider('value', ui.value );
					$(this).next('.slider-value').text(ui.value + '%');
				});
			});
			}
	});

	$('.device-slider').slider({
		range: "min",
		min: 0,
		max: 100,
		create: function( event, ui ){
			$(this).slider("option", "value", $(this).attr('data-value') );
			var val = $(this).slider("value");
			$(this).after('<span class="slider-value">' + val + '%</span>');
			if( $(this).parent().hasClass('light-on') ){
				$(this).parent().css('background-color', 'rgba(255, 200, 50,' + ( $(this).attr('data-value') / 100 ) + ')');
			}
		},
		stop: function(event, ui) {
			$(this).next('.slider-value').text(ui.value + '%');
			$.get("api.php?fx=dim&type=device&uid=" + $(this).attr('data-device-id') + "&val=" + ui.value, function( data ) {
			});
			},
		slide: function( event, ui ) {
			$(this).next('.slider-value').text(ui.value + '%');
			if( $(this).parent().hasClass('light-on') ){
				$(this).parent().css('background-color', 'rgba(255, 200, 50,' + ( ui.value / 100 ) + ')');
			}
		}
	});

	$('.house-slider').slider({
		range: "min",
		min: 0,
		max: 100,
		value: 50,
		create: function( event, ui ){
			var val = $(this).slider("value");
			$(this).after('<span class="slider-value">' + val + '%</span>');
		},
		slide: function( event, ui ){
			$(this).next('.slider-value').text(ui.value + '%');
		},
		stop: function(event, ui) {
			$(this).next('.slider-value').text(ui.value + '%');
			$.get("api.php?fx=dim&type=all&uid=" + $(this).attr('data-device-id') + "&val=" + ui.value, function( data ) {
			});
			}
	});

	$('button.onOffToggleButton').click(function(event){
		var roomID = $(this).attr('data-room-id');
		var room = $(this).parent().parent();
		var val = 0;
		if( $(this).hasClass('buttonOn') ){
			val = 1;
		}

		$.get( "api.php?fx=toggle&type=room&uid=" + roomID + "&val=" + val, function( data ) {
			$(room).find('.room-devices .device').each(function(){
				setDeviceLightState($(this), val == 1);
			});
		});
	});

	$('button.onOffDeviceToggleButton').click(function(event){
		var DID = $(this).attr('data-device-id');
		var val = 0;
		var light = $(this);
		if( $(this).hasClass('buttonOn') ){
			val = 1;
		}

		$.get( "api.php?fx=toggle&type=device&uid=" + DID + "&val=" + val, function( data ) {
			setDeviceLightState($(light).parents('.device'), val == 1);
		});
	});

	$('button.onOffHouseToggleButton').click(function(event){
		var DID = $(this).attr('data-device-id');
		var val = 0;
		if( $(this).hasClass('buttonOn') ){
			val = 1;
		}

		$.get( "api.php?fx=toggle&type=all&uid=ALL&val=" + val, function( data ) {
			$('.room-devices .device').each(function(){
				setDeviceLightState($(this), val == 1);
			});
		});
	});

	$('.runScene').click(function(event){
		var sid = $(this).attr('data-scene-id');
		var mode = $(this).attr('data-scene-mode');

		$.get( "api.php?fx=scene&type=" + mode + "&uid=" + sid, function( data ) {
		});
	});


	$('#arrayDump').click(function(){
		$(this).toggleClass('toggled');
	});
});
