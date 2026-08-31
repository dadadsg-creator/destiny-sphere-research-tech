<?php /* Smarty version 2.6.14, created on 2007-06-05 15:06:58
         compiled from root.tpl */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-type" content="text/html; encoding=UTF-8" />
<title>Interface Localization</title>
<?php echo '<style type="text/css">
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
	#sort_block a { margin: 1px 10px; font-size: 0.8em }

#search_block { /*float: right;*/ margin: 10px; padding: 10px }
	#search_block form label { margin: 1px 6px }

.alert { color: rgb(255,63,63) }
</style>'; ?>

</head>

<body>
<div id="search_and_sort">
  <div id="sort_block"><a href="?sort=name">Sort by interface name</a><a href="?sort=id">Sort by interface ID</a></div>

  <div id="search_block">
    <table>
      <tr>
      <td align="right"><label for="input">Search by interface name:</label></td>
      <td><form action="" method="GET"><input type="hidden" name="action" value="search" /><input type="text" name="search" id="input"<?php if (isset ( $this->_tpl_vars['text'] ) && $this->_tpl_vars['text'] != ''): ?> value="<?php echo $this->_tpl_vars['text']; ?>
"<?php endif; ?> /></form></td>
      </tr>

      <tr>
      <td align="right"><label for="text_pattern">Search by text: </label></td>
      <td><form action="" method="GET"><input type="hidden" name="action" value="search_by_text" /><input type="text" name="search" id="text_pattern"<?php if (isset ( $this->_tpl_vars['text'] ) && $this->_tpl_vars['text'] != ''): ?> value="<?php echo $this->_tpl_vars['text']; ?>
"<?php endif; ?> /><select name="lang"><option value="E"<?php if (isset ( $this->_tpl_vars['lang'] ) && $this->_tpl_vars['lang'] == 'E'): ?> selected="selected"<?php endif; ?>>EN</option><option value="G"<?php if (isset ( $this->_tpl_vars['lang'] ) && $this->_tpl_vars['lang'] == 'G'): ?> selected="selected"<?php endif; ?>>GE</option><option value="R"<?php if (isset ( $this->_tpl_vars['lang'] ) && $this->_tpl_vars['lang'] == 'R'): ?> selected="selected"<?php endif; ?>>RU</option></select></form></td>
      </tr>

      <tr>
      <td align="right"><label for="id_pattern">Search by ID: </label></td>
      <td><form action="" method="GET"><input type="hidden" name="action" value="search_by_id" /><input type="text" id="id_pattern" name="search" /></form></td>
      </tr>
    </table>

    <br />

  </div>
</div>

<?php if ($this->_tpl_vars['action'] == 'view'): ?>
<h2><nobr>Viewing localized text</nobr></h2>
	<?php if ($this->_tpl_vars['pages_count'] > 1): ?>
	<div>
		<?php $_from = $this->_tpl_vars['pages']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['p']):
?>
			<?php if ($this->_tpl_vars['p'] == $this->_tpl_vars['page']): ?>
			<span class="pager"><?php echo $this->_tpl_vars['p']; ?>
</span>
			<?php else: ?>
			<a class="pager" href="?page=<?php echo $this->_tpl_vars['p'];  if (isset ( $this->_tpl_vars['sort'] )): ?>&sort=<?php echo $this->_tpl_vars['sort'];  endif;  if (isset ( $this->_tpl_vars['search'] )): ?>&search=<?php echo $this->_tpl_vars['search'];  endif; ?>"><?php echo $this->_tpl_vars['p']; ?>
</a>
			<?php endif; ?>
		<?php endforeach; endif; unset($_from); ?>
	</div><br />
	<?php endif; ?>

	<div class="small_create"><a href="?action=add"><nobr>create new item</nobr></a></div>

	<?php $_from = $this->_tpl_vars['localized']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['k'] => $this->_tpl_vars['item']):
?>
	<h4><?php if ($this->_tpl_vars['item']['interfacename'] != ''): ?>[<?php echo $this->_tpl_vars['item']['interfacename']; ?>
]<?php else: ?>&mdash;<?php endif; ?></h4>
	<h5 class="bright">id_localizedtext: #<?php echo $this->_tpl_vars['k']; ?>
</h5>
	<form action="?<?php if (isset ( $this->_tpl_vars['sort'] )): ?>sort=<?php echo $this->_tpl_vars['sort'];  endif; ?>" method="POST">
		<input type="hidden" name="id" value="<?php echo $this->_tpl_vars['k']; ?>
" />
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page" value="<?php echo $this->_tpl_vars['page']; ?>
" />

  	  <?php $_from = $this->_tpl_vars['item']['text']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['lang'] => $this->_tpl_vars['text']):
?>
  	  <div><span class="lang">[<?php echo $this->_tpl_vars['lang']; ?>
]</span><input class="text" type="text" name="lt[<?php echo $this->_tpl_vars['lang']; ?>
]" value="<?php echo $this->_tpl_vars['text']; ?>
" /></div>
	  <?php endforeach; endif; unset($_from); ?>
	  <div class="submit"><input type="submit" value="Update" /> <a href="?id=<?php echo $this->_tpl_vars['k']; ?>
&action=delete&page=<?php echo $this->_tpl_vars['page']; ?>
" onClick="<?php echo 'if(!confirm(\'Really delete?\')) { return false }'; ?>
" class="alert small_delete">delete item</a></div>
	</form>
	<?php endforeach; endif; unset($_from); ?>

	<?php if ($this->_tpl_vars['pages_count'] > 1): ?>
	<div>
		<?php $_from = $this->_tpl_vars['pages']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['p']):
?>
			<?php if ($this->_tpl_vars['p'] == $this->_tpl_vars['page']): ?>
			<span class="pager"><?php echo $this->_tpl_vars['p']; ?>
</span>
			<?php else: ?>
			<a class="pager" href="?page=<?php echo $this->_tpl_vars['p'];  if (isset ( $this->_tpl_vars['sort'] )): ?>&sort=<?php echo $this->_tpl_vars['sort'];  endif;  if (isset ( $this->_tpl_vars['search'] )): ?>&search=<?php echo $this->_tpl_vars['search'];  endif; ?>"><?php echo $this->_tpl_vars['p']; ?>
</a>
			<?php endif; ?>
		<?php endforeach; endif; unset($_from); ?>
	</div>
	<?php endif; ?>
<?php elseif ($this->_tpl_vars['action'] == 'add'): ?>
	<form action="?" method="POST">
		<input type="hidden" name="action" value="save" />
		<input type="hidden" name="page" value="<?php echo $this->_tpl_vars['page']; ?>
" />
		<label for="interfacename">Interface name</label> <input type="text" name="interfacename" id="interfacename" class="big_input" />

	  <br />
	  <br />
	  <br />
  	  <?php $_from = $this->_tpl_vars['languages']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['lang'] => $this->_tpl_vars['text']):
?>
  	  <div><span class="lang">[<?php echo $this->_tpl_vars['lang']; ?>
]</span><input class="text" type="text" name="lt[<?php echo $this->_tpl_vars['lang']; ?>
]" /></div>
	  <?php endforeach; endif; unset($_from); ?>
	  <div class="submit"><input type="submit" value="Add" /></div>
	</form>
<?php elseif ($this->_tpl_vars['action'] == 'save'): ?>
	<?php if ($this->_tpl_vars['save_result']): ?>
		<div>Saved successfully!</div>
	<?php else: ?>
		<div class="alert">Failed to save new item <?php if (isset ( $this->_tpl_vars['error_message'] )): ?>(<?php echo $this->_tpl_vars['error_message']; ?>
)<?php endif; ?>!</div>
	<?php endif; ?>

	<div><a href="?page=<?php echo $this->_tpl_vars['pages_count']; ?>
">Back</a></div>
<?php elseif ($this->_tpl_vars['action'] == 'delete'): ?>
	<?php if ($this->_tpl_vars['delete_result']): ?>
		<div>Deleted successfully!</div>
	<?php else: ?>
		<div class="alert">Failed to delete item!</div>
	<?php endif; ?>

	<div><a href="?page=<?php echo $this->_tpl_vars['page']; ?>
">Back</a></div>
<?php else: ?>
<h2>Default</h2>
<?php endif; ?>
</body>
</html>