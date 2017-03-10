// radio checked - hotspot plan
$(document).ready(function () {
    $('input[type=radio]').change(function(){

	if ($('#Time_Limit').is(':checked')) {
        $('#DataLimit').hide();
		$('#TimeLimit').show();
    }
	if ($('#Data_Limit').is(':checked')) {
        $('#TimeLimit').hide();
		$('#DataLimit').show();
    }
	if ($('#Both_Limit').is(':checked')) {
        $('#TimeLimit').show();
		$('#DataLimit').show();
    }
	
	if ($('#Unlimited').is(':checked')) {
        $('#Type').hide();
		$('#TimeLimit').hide();
		$('#DataLimit').hide();
    } else {
        $('#Type').show();
    }

	if ($('#Hotspot').is(':checked')) {
        $('#p').hide();
		$('#h').show();
    } 
	if ($('#PPPOE').is(':checked')) {
        $('#p').show();
		$('#h').hide();
    }
	
    });
});
$("#Hotspot").prop("checked", true).change();


//auto load pool - pppoe plan
var htmlobjek;
$(document).ready(function(){
  $("#routers").change(function(){
    var routers = $("#routers").val();
    $.ajax({
        url: "index.php?_route=autoload/pool",
        data: "routers="+routers,
        cache: false,
        success: function(msg){
            $("#pool_name").html(msg);
        }
    });
  });
});

//auto load plans data - recharge user
$(function() {
    $('input[type=radio]').change(function(){
		if ($('#Hot').is(':checked')) {
				$.ajax({
					type: "POST",
					dataType: "html",
					url: "index.php?_route=autoload/server",
					success: function(msg){
						$("#server").html(msg);                                                     
					}
				});
				
				$("#server").change(getAjaxAlamat);
				function getAjaxAlamat(){
					var server = $("#server").val();
					$.ajax({
						type: "POST",
						dataType: "html",
						url: "index.php?_route=autoload/plan",
						data: "jenis=Hotspot&server="+server,
						success: function(msg){
							$("#plan").html(msg);
						}
					});
				};

		}else{
				$.ajax({
					type: "POST",
					dataType: "html",
					url: "index.php?_route=autoload/server",
					success: function(msg){
						$("#server").html(msg);
					}
				});
				$("#server").change(function(){
					var server = $("#server").val();
					$.ajax({
						type: "POST",
						dataType: "html",
						url: "index.php?_route=autoload/plan",
						data: "jenis=PPPOE&server="+server,
						success: function(msg){
							$("#plan").html(msg);
						}
					});
				});
		}
    });
});