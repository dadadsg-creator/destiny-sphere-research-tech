<?php
if(!defined('PAID_ARTEFACTS')) exit;
?>
<style type="text/css">
#main_container {
	margin: 10px;
	font-family: Arial;
	font-size: 12px;
	color:white;
}
</style>

<div id='main_container'>

<form action="" method="post">
<input type="hidden" name='action' value='switch_world'>
<p>
World:
	<select name='db_world'>
		<?php foreach ($worlds as $world):?>
		<option value="<?php echo $world['dbid']."_".$world['id_world']?>" <?php if($world['dbid'] == $dbId && $world['id_world'] == $world_id):?>selected='selected'<?php endif?>><?php echo $world['worldname']?></option>
		<?php endforeach?>
	</select>
	
	<input type="submit" value='Select world'>
</p>
</form>


</div>
