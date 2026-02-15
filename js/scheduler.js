function randomString(length, chars) {
	var result = '';
	for (var i = length; i > 0; --i) result += chars[Math.floor(Math.random() * chars.length)];
	return result;
}

$(function(){
	function runSchedule(){
		var d = new Date();
		$.get("runSchedule.php", function( data ) {
		});
	}

	$('#runOnce').click(function(){
		runSchedule();
	});

	$('#poll').click(function(){
		$('#runOnce').attr('disabled', true);
		$(this).unbind();
		$(this).addClass('running');
		setInterval(runSchedule, 30000);
	});

	function bindEvents(){

		$('select[name="TIME_TYPE"]').change(function(){
			$(this).closest('.scheduledTask').removeClass('tm-SunTime');
			$(this).closest('.scheduledTask').removeClass('tm-FixedTime');

			if( $(this).find('option:selected').val() == "FIXED" ){
				$(this).closest('.scheduledTask').addClass('tm-FixedTime');
			}else{
				$(this).closest('.scheduledTask').addClass('tm-SunTime');
			}
		});

		$('select[name="FX"]').change(function(){
			$(this).closest('.scheduledTask').removeClass('fx-Dim');
			$(this).closest('.scheduledTask').removeClass('fx-Switch');

			if( $(this).find('option:selected').val() == "DIM" ){
				$(this).closest('.scheduledTask').addClass('fx-Dim');
			}else{
				$(this).closest('.scheduledTask').addClass('fx-Switch');
			}
		});

		$('.schedule-slider').slider({
			range: "min",
			min: 0,
			max: 100,
			create: function( event, ui ){
				$(this).slider("option", "value", $(this).parent().find("input[name='DIM_SCHED']").val() );
			},
			stop: function(event, ui) {
				$(this).parent().find('input[name="DIM_SCHED"]').val( ui.value );
			},
			slide: function( event, ui ) {
				$(this).parent().find('input[name="DIM_SCHED"]').val( ui.value );
			}
		});

		$('select[name="TIME_TYPE"]').change();

		$('select[name="FX"]').change();

		$('.btnAdd, .btnRemove').click(function(){

			var available =  $(this).closest('.deviceList').find('select.available')[0];
			var selected = $(this).closest('.deviceList').find('select.selected')[0];

			if( $(this).hasClass('btnAdd') ){
				SelectMoveRows(available,  selected );
			}else{
				SelectMoveRows( selected, available );
			}

		});

		$('.delTask').click(function(){ $(this).parent().remove(); });

	}

	$('#add').click(function(){
		$('#events').append( addSchedule.replace(/__REPLACE__/g, randomString(10, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') ) );
		bindEvents();
	});

	$('#save').click(function(){
		$('select[name="DEVICE_SELECTED"] option').each(function(){ $(this).attr('selected','selected'); });
		var schedules = [];

		$('.scheduledTask').each(function(){
			schedules.push( $(this).find(':input').serializeArray() );
		});

		$.ajax({
			type: 'POST',
			url: 'runSchedule.php',
			data: {'schedule': JSON.stringify(schedules)},
			success: function(msg) {
			  alert("Saved");
			}
		});


	});

	bindEvents();

	$( "#events" ).sortable({
		placeholder: "ui-state-highlight"
	});

	$( "#events" ).disableSelection();
});
