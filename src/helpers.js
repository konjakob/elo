   <script language="javascript">
      function changeUser(id){
	var t4;
	var t5;
	$(document).ready(function(){
		t4 = $("#t_name_c").val();
		alert(t4);
		if ( t4.length < 1 ) {
			$("#t_name_c").css('background-color','#F96');
			$("#t_name_c").focus();
			return;
		}	
		t5 = $("#t_email_c").val();
		if ( t5.length < 1 ) {
			$("#t_email_c").css('background-color','#F96');
			$("#t_email_c").focus();
			return;
		}	
		$.post("actions.php",
		{	  
		  userid:id,
		  t_name:t4,
		  t_email:t5,
		  action:'changeUser',
		  t_pass:$("#t_pass_c").val()
		},
		function(data,status){
			$("#changeUser").html(data);
		});
	  });
  };
  </script>