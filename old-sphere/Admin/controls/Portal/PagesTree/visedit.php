<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
</head>

<!-- frames -->
<frameset  rows="185,*" >

	<frame src="null.php?<?=session_name().'='.session_id();?>" name="top" id="top" frameborder="0" scrolling="No" noresize marginwidth="0" marginheight="0" style="border: 0px; padding: 0; margin: 0;">
<!--	<frame src="/Admin/include/top_frame.php?<?=session_name().'='.session_id();?>" name="top" id="top" frameborder="0" scrolling="No" noresize marginwidth="0" marginheight="0" style="border: 0px; padding: 0; margin: 0;">
		<frameset  cols="170,*">
		<frame src="/Admin/include/left_frame.php?<?=session_name().'='.session_id();?>" name="left" id="left" frameborder="0" scrolling="No" noresize marginwidth="0" marginheight="0"> -->

	        <frame name="main" src="/easyadmin.php?p=<?=$_REQUEST['ID']?>&<?=session_name().'='.session_id();?>" marginwidth="0" marginheight="0" scrolling="auto" frameborder="0" noresize>
		</frameset>
</frameset>


</html>
