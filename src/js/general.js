function checkAbcSyntax(id) {
	var t;
	$(document).ready(function(){
		t = $("#"+id).val();
		if ( t.length < 1 ) {
			$("#"+id).focus();
			return;
		}
		$.post("checkAbcSyntax.php",
		{
		  text:t,
		},
		function(data,status){
			//alert(data);
			$("#"+id+"Check").show();
			document.getElementById(""+id+"CheckText").innerHTML = data;
			//$(""+id+"CheckText").innerHTML = data;
		});
	  });
  };	

$(document).ready(function(){  
	$('.generateFilePreview').click(function(e) {
		e.preventDefault();  
		var el = $(this);
		var id = $(this).attr("data-id");
		var cell = $(this).closest("td").prev();
		$.ajax({
				url: "actions.php?action=generatePreview&fileid="+id,
				dataType: 'json',
				success: function(respond)
				{
					if ( respond.state == 'ok' ) {
						el.parent().html("");
						cell.html('<img src="' + respond.preview + '" height="200">');
					} else {
						toastr["error"](respond.text, respond.title);
					}
				}
		});
	});
	
	$('.deleteFileAttachment').click(function(e) {
		e.preventDefault();  
		var el = $(this);
		var id = $(this).attr("data-id");
		var row = $(this).closest("tr");
		$.ajax({
				url: "actions.php?action=deleteFileAttachment&aid="+id,
				dataType: 'json',
				success: function(respond)
				{
					if ( respond.state == 'ok' ) {
						row.hide();
						toastr["success"](respond.text, respond.title);
					} else {
						toastr["error"](respond.text, respond.title);
					}
				}
		});
	})
	
	
});