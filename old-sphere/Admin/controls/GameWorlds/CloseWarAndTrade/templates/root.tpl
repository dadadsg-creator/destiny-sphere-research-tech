{if $action eq 'view'}
  <h1>Worlds</h1>
  <div><a href="?action=add&worldid=0" class="add">add a new period to all worlds</a></div>
  
  {if isset($error_msg)}<br /><div class="mess_error">{$error_msg}</div>{/if}
	{foreach from=$worlds item=world}
		<br />
		<h1>{$world.worldname} #{$world.id}</h1>
		<div><a href="?action=add&worldid={$world.id}&dbid={$world.db_id}" class="add">add a new period to this world</a></div>
		{if $world.peaceperiods_count > 0}
		<h3 style="font-family: Arial, sans; font-size: 1.1em">Peace periods</h3>
		{foreach from=$world.peaceperiods item=p}
			<div style="font-family: Arial,sans">
			<form action="?" method="POST">
			<input type="hidden" name="period_id" value="{$p.id}" />
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="type" value="peace" />
			<input type="hidden" name="world_id" value="{$world.id}" />
			<input type="hidden" name="db_id" value="{$world.db_id}">
			<div><div style="font-family: Helvetica,Arial,sans; float: left; font-size: 0.7em; width: 30px; padding: 1px 10px">id: {$p.id}</div>Starts:&nbsp;<input type="text" name="start_date" value="{$p.start_date}" />&nbsp;&nbsp;ends:&nbsp;<input type="text" name="end_date" value="{$p.end_date}" />&nbsp;<input type="submit" value="Update" />&nbsp;<a href="?action=delete&dbid={$world.db_id}&worldid={$world.id}&periodid={$p.id}&type=peace" class="alert" {literal}onclick="if(!confirm('Really delete?')) { return false; }"{/literal}>delete</a></div>
			</form>
			</div>
		{/foreach}
		{/if}
		
		{if $world.notradeperiods_count > 0}
		<h3 style="font-family: Arial, sans; font-size: 1.1em">No-trade periods</h3>
		{foreach from=$world.notradeperiods item=p}
			<div style="font-family: Arial,sans">
			<form action="?" method="POST">
			<input type="hidden" name="period_id" value="{$p.id}" />
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="type" value="notrade" />
			<input type="hidden" name="world_id" value="{$world.id}" />
			<input type="hidden" name="db_id" value="{$world.db_id}">
			<div><div style="font-family: Helvetica,Arial,sans; float: left; font-size: 0.7em; width: 30px; padding: 1px 10px">id: {$p.id}</div>Starts:&nbsp;<input type="text" name="start_date" value="{$p.start_date}" />&nbsp;&nbsp;ends:&nbsp;<input type="text" name="end_date" value="{$p.end_date}" />&nbsp;<input type="submit" value="Update" />&nbsp;<a href="?action=delete&dbid={$world.db_id}&worldid={$world.id}&periodid={$p.id}&type=notrade" class="alert" {literal}onclick="if(!confirm('Really delete?')) { return false; }"{/literal}>delete</a></div>
			</form>
			</div>
		{/foreach}
		{/if}
	{/foreach}
{elseif $action eq 'add'}
  {if ($world_id === false || $db_id === false) && $world_id !== 0}
  	<div class="mess_error">Missing required parameters. Aborted</div>
  	<div>World id: {$world_id}, DB id: {$db_id}</div>
  	<div><a href="?">Back</a></div>
  {else}
  	{if $world_id === 0}
	<h1>Add a new period to all worlds</h1>
  	{else}
	<h1>Add a new period to the world &ldquo;{$world_name}&rdquo;</h1>
  	{/if}
	<form action="?" method="POST">
		<input type="hidden" name="action" value="save" />
		<input type="hidden" name="world_id" value="{$world_id}" />
		<input type="hidden" name="db_id" value="{$db_id}" />
		<div><input type="checkbox" name="type[]" id="peaceperiod" value="peace" checked="checked" /><label for="peaceperiod">Peace period</label>&nbsp;<input type="checkbox" name="type[]" id="notradeperiod" value="notrade" checked="checked" /><label for="notradeperiod">No-trade period</label></div>
		<div>Start:&nbsp;<input type="text" name="start_date" value="{$now}" />&nbsp;&nbsp;end:&nbsp;<input type="text" name="end_date" value="{$now}" />&nbsp;<input type="submit" value="Add" /></div>
	</form>
  {/if}
{elseif $action eq 'save'}
	{if isset($error_msg)}
	<div class="mess_error">Error: {$error_msg}</div>
	{else}
	<div class="mess_info">Period successfully added</div>
	{/if}
	<div><a href="?">Back</a></div>
`{elseif $action eq 'delete'}
	{if isset($error)}
		<div class="mess_error">Period was not deleted, an error occurred.</div>
		<div><a href="?">Back</a></div>
	{else}
		<div class="mess_info">Period was deleted successfully</div>
		<div><a href="?">Back</a></div>
	{/if}
{/if}
	
