<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-type" content="text/html; encoding=UTF-8" />
<title>Interface Localization</title>
{literal}<style type="text/css">
body { font-family: Helvetica, sans; background-color: rgb(241, 245, 255) }
h1, h2, h3, h4, h5, h6 { margin: 3px; }
.small_create { clear: both; width: 100%; font-size: 12px; text-align: center; margin: 10px 0px }
.small_delete { font-size: 10px; margin-left: 40px }
.bright { color: rgb(207, 207, 227) }
span.lang { font-size: 0.7em; padding: 3px; float: left; font-family: monospace }
input { border: 1px solid rgb(128,128,128); font-size: 0.8em; margin: 1px; padding: 1px; background-color: rgb(255, 255, 255) }
select { border: 1px solid rgb(128,128,128); font-size: 0.8em; margin: 1px; padding: 1px; background-color: rgb(255, 255, 255) }
input.text { width: 600px; margin-left: 10px }
input.big_input { font-size: 1.1em }
div.submit { text-align: center; width: 600px; padding: 10px }

.pager { padding: 4px }
a.pager {  }
span.pager { border: 1px solid rgb(128,128,128) }

#search_and_sort { float: right }
#sort_block { /*float: right;*/ margin: 10px; padding: 10px; background-color: rgb(235, 235, 250); border: 1px dashed rgb(200, 200, 227) }
	#sort_block a { margin: 1px 10px; }

#search_block { /*float: right;*/ margin: 10px; padding: 10px }
	#search_block form label { margin: 1px 6px }

.alert { color: rgb(255,63,63) }
</style>{/literal}
</head>

<body>
<div id="search_and_sort">
  <div id="sort_block"><a href="?sort=name">Sort by interface name</a><a href="?sort=id">Sort by interface ID</a></div>

  <div id="search_block">
    <table>
      <tr>
      <td align="right"><label for="input">Search by interface name:</label></td>
      <td><form action="" method="GET"><input type="hidden" name="action" value="search" /><input type="text" name="search" id="input"{if isset($text) && $text ne ''} value="{$text}"{/if} /></form></td>
      </tr>

      <tr>
      <td align="right"><label for="text_pattern">Search by text: </label></td>
      <td><form action="" method="GET"><input type="hidden" name="action" value="search_by_text" /><input type="text" name="search" id="text_pattern"{if isset($text) && $text ne ''} value="{$text}"{/if} /><select name="lang"><option value="E"{if isset($lang) && $lang eq 'E'} selected="selected"{/if}>EN</option><option value="G"{if isset($lang) && $lang eq 'G'} selected="selected"{/if}>GE</option><option value="R"{if isset($lang) && $lang eq 'R'} selected="selected"{/if}>RU</option></select></form></td>
      </tr>

      <tr>
      <td align="right"><label for="id_pattern">Search by ID: </label></td>
      <td><form action="" method="GET"><input type="hidden" name="action" value="search_by_id" /><input type="text" id="id_pattern" name="search" /></form></td>
      </tr>
    </table>

    <br />

  </div>
</div>

{if $action eq 'view'}
<h2><nobr>Viewing localized text</nobr></h2>
	{if $pages_count > 1}
	<div>
		{foreach from=$pages item=p}
			{if $p eq $page}
			<span class="pager">{$p}</span>
			{else}
			<a class="pager" href="?page={$p}{if isset($sort)}&sort={$sort}{/if}{if isset($search)}&search={$search}{/if}">{$p}</a>
			{/if}
		{/foreach}
	</div><br />
	{/if}

	<div class="small_create"><a href="?action=add"><nobr>create new item</nobr></a></div>

	{foreach from=$localized item=item key=k}
	<h4>{if $item.interfacename ne ''}[{$item.interfacename}]{else}&mdash;{/if}</h4>
	<h5 class="bright">id_localizedtext: #{$k}</h5>
	<form action="?{if isset($sort)}sort={$sort}{/if}" method="POST">
		<input type="hidden" name="id" value="{$k}" />
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page" value="{$page}" />

  	  {foreach from=$item.text item=text key=lang}
  	  <div><span class="lang">[{$lang}]</span><input class="text" type="text" name="lt[{$lang}]" value="{$text}" /></div>
	  {/foreach}
	  <div class="submit"><input type="submit" value="Update" /> <a href="?id={$k}&action=delete&page={$page}" onClick="{literal}if(!confirm('Really delete?')) { return false }{/literal}" class="alert small_delete">delete item</a></div>
	</form>
	{/foreach}

	{if $pages_count > 1}
	<div>
		{foreach from=$pages item=p}
			{if $p eq $page}
			<span class="pager">{$p}</span>
			{else}
			<a class="pager" href="?page={$p}{if isset($sort)}&sort={$sort}{/if}{if isset($search)}&search={$search}{/if}">{$p}</a>
			{/if}
		{/foreach}
	</div>
	{/if}
{elseif $action eq 'add'}
	<form action="?" method="POST">
		<input type="hidden" name="action" value="save" />
		<input type="hidden" name="page" value="{$page}" />
		<label for="interfacename">Interface name</label> <input type="text" name="interfacename" id="interfacename" class="big_input" />

	  <br />
	  <br />
	  <br />
  	  {foreach from=$languages item=text key=lang}
  	  <div><span class="lang">[{$lang}]</span><input class="text" type="text" name="lt[{$lang}]" /></div>
	  {/foreach}
	  <div class="submit"><input type="submit" value="Add" /></div>
	</form>
{elseif $action eq 'save'}
	{if $save_result}
		<div>Saved successfully!</div>
	{else}
		<div class="alert">Failed to save new item {if isset($error_message)}({$error_message}){/if}!</div>
	{/if}

	<div><a href="?page={$pages_count}">Back</a></div>
{elseif $action eq 'delete'}
	{if $delete_result}
		<div>Deleted successfully!</div>
	{else}
		<div class="alert">Failed to delete item!</div>
	{/if}

	<div><a href="?page={$page}">Back</a></div>
{else}
<h2>Default</h2>
{/if}
</body>
</html>