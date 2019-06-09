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