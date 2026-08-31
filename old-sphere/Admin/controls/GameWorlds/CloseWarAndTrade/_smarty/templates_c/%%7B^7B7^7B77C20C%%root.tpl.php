<?php /* Smarty version 2.6.10, created on 2006-10-03 13:00:47
         compiled from root.tpl */ ?>
<?php if ($this->_tpl_vars['action'] == 'view'): ?>
  <h1>Worlds</h1>
  <div><a href="?action=add&worldid=0" class="add">add a new period to all worlds</a></div>
  
  <?php if (isset ( $this->_tpl_vars['error_msg'] )): ?><br /><div class="mess_error"><?php echo $this->_tpl_vars['error_msg']; ?>
</div><?php endif; ?>
	<?php $_from = $this->_tpl_vars['worlds']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['world']):
?>
		<br />
		<h1><?php echo $this->_tpl_vars['world']['worldname']; ?>
 #<?php echo $this->_tpl_vars['world']['id']; ?>
</h1>
		<div><a href="?action=add&worldid=<?php echo $this->_tpl_vars['world']['id']; ?>
&dbid=<?php echo $this->_tpl_vars['world']['db_id']; ?>
" class="add">add a new period to this world</a></div>
		<?php if ($this->_tpl_vars['world']['peaceperiods_count'] > 0): ?>
		<h3 style="font-family: Arial, sans; font-size: 1.1em">Peace periods</h3>
		<?php $_from = $this->_tpl_vars['world']['peaceperiods']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['p']):
?>
			<div style="font-family: Arial,sans">
			<form action="?" method="POST">
			<input type="hidden" name="period_id" value="<?php echo $this->_tpl_vars['p']['id']; ?>
" />
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="type" value="peace" />
			<input type="hidden" name="world_id" value="<?php echo $this->_tpl_vars['world']['id']; ?>
" />
			<input type="hidden" name="db_id" value="<?php echo $this->_tpl_vars['world']['db_id']; ?>
">
			<div><div style="font-family: Helvetica,Arial,sans; float: left; font-size: 0.7em; width: 30px; padding: 1px 10px">id: <?php echo $this->_tpl_vars['p']['id']; ?>
</div>Starts:&nbsp;<input type="text" name="start_date" value="<?php echo $this->_tpl_vars['p']['start_date']; ?>
" />&nbsp;&nbsp;ends:&nbsp;<input type="text" name="end_date" value="<?php echo $this->_tpl_vars['p']['end_date']; ?>
" />&nbsp;<input type="submit" value="Update" />&nbsp;<a href="?action=delete&dbid=<?php echo $this->_tpl_vars['world']['db_id']; ?>
&worldid=<?php echo $this->_tpl_vars['world']['id']; ?>
&periodid=<?php echo $this->_tpl_vars['p']['id']; ?>
&type=peace" class="alert" <?php echo 'onclick="if(!confirm(\'Really delete?\')) { return false; }"'; ?>
>delete</a></div>
			</form>
			</div>
		<?php endforeach; endif; unset($_from); ?>
		<?php endif; ?>
		
		<?php if ($this->_tpl_vars['world']['notradeperiods_count'] > 0): ?>
		<h3 style="font-family: Arial, sans; font-size: 1.1em">No-trade periods</h3>
		<?php $_from = $this->_tpl_vars['world']['notradeperiods']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['p']):
?>
			<div style="font-family: Arial,sans">
			<form action="?" method="POST">
			<input type="hidden" name="period_id" value="<?php echo $this->_tpl_vars['p']['id']; ?>
" />
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="type" value="notrade" />
			<input type="hidden" name="world_id" value="<?php echo $this->_tpl_vars['world']['id']; ?>
" />
			<input type="hidden" name="db_id" value="<?php echo $this->_tpl_vars['world']['db_id']; ?>
">
			<div><div style="font-family: Helvetica,Arial,sans; float: left; font-size: 0.7em; width: 30px; padding: 1px 10px">id: <?php echo $this->_tpl_vars['p']['id']; ?>
</div>Starts:&nbsp;<input type="text" name="start_date" value="<?php echo $this->_tpl_vars['p']['start_date']; ?>
" />&nbsp;&nbsp;ends:&nbsp;<input type="text" name="end_date" value="<?php echo $this->_tpl_vars['p']['end_date']; ?>
" />&nbsp;<input type="submit" value="Update" />&nbsp;<a href="?action=delete&dbid=<?php echo $this->_tpl_vars['world']['db_id']; ?>
&worldid=<?php echo $this->_tpl_vars['world']['id']; ?>
&periodid=<?php echo $this->_tpl_vars['p']['id']; ?>
&type=notrade" class="alert" <?php echo 'onclick="if(!confirm(\'Really delete?\')) { return false; }"'; ?>
>delete</a></div>
			</form>
			</div>
		<?php endforeach; endif; unset($_from); ?>
		<?php endif; ?>
	<?php endforeach; endif; unset($_from); ?>
<?php elseif ($this->_tpl_vars['action'] == 'add'): ?>
  <?php if (( $this->_tpl_vars['world_id'] === false || $this->_tpl_vars['db_id'] === false ) && $this->_tpl_vars['world_id'] !== 0): ?>
  	<div class="mess_error">Missing required parameters. Aborted</div>
  	<div>World id: <?php echo $this->_tpl_vars['world_id']; ?>
, DB id: <?php echo $this->_tpl_vars['db_id']; ?>
</div>
  	<div><a href="?">Back</a></div>
  <?php else: ?>
  	<?php if ($this->_tpl_vars['world_id'] === 0): ?>
	<h1>Add a new period to all worlds</h1>
  	<?php else: ?>
	<h1>Add a new period to the world &ldquo;<?php echo $this->_tpl_vars['world_name']; ?>
&rdquo;</h1>
  	<?php endif; ?>
	<form action="?" method="POST">
		<input type="hidden" name="action" value="save" />
		<input type="hidden" name="world_id" value="<?php echo $this->_tpl_vars['world_id']; ?>
" />
		<input type="hidden" name="db_id" value="<?php echo $this->_tpl_vars['db_id']; ?>
" />
		<div><input type="checkbox" name="type[]" id="peaceperiod" value="peace" checked="checked" /><label for="peaceperiod">Peace period</label>&nbsp;<input type="checkbox" name="type[]" id="notradeperiod" value="notrade" checked="checked" /><label for="notradeperiod">No-trade period</label></div>
		<div>Start:&nbsp;<input type="text" name="start_date" value="<?php echo $this->_tpl_vars['now']; ?>
" />&nbsp;&nbsp;end:&nbsp;<input type="text" name="end_date" value="<?php echo $this->_tpl_vars['now']; ?>
" />&nbsp;<input type="submit" value="Add" /></div>
	</form>
  <?php endif; ?>
<?php elseif ($this->_tpl_vars['action'] == 'save'): ?>
	<?php if (isset ( $this->_tpl_vars['error_msg'] )): ?>
	<div class="mess_error">Error: <?php echo $this->_tpl_vars['error_msg']; ?>
</div>
	<?php else: ?>
	<div class="mess_info">Period successfully added</div>
	<?php endif; ?>
	<div><a href="?">Back</a></div>
`<?php elseif ($this->_tpl_vars['action'] == 'delete'): ?>
	<?php if (isset ( $this->_tpl_vars['error'] )): ?>
		<div class="mess_error">Period was not deleted, an error occurred.</div>
		<div><a href="?">Back</a></div>
	<?php else: ?>
		<div class="mess_info">Period was deleted successfully</div>
		<div><a href="?">Back</a></div>
	<?php endif; ?>
<?php endif; ?>
	