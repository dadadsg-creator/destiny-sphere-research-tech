<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}



$sql = "SELECT id_artefacttype AS id, text AS name FROM ds_artefacttype AS a
LEFT JOIN ds_localizedtextlanguage AS l ON l.id_localizedtext=a.id_localizedtypename
AND id_language=3";
$arttypes = $common->getResultArray('', $sql);

$sql = "SELECT id_artefactscope, text, scopename FROM ds_artefactscope AS s
LEFT JOIN ds_localizedtextlanguage AS l ON l.id_localizedtext=s.id_localizedscope
AND id_language=3";
$scopes =  $common->getResultArray('', $sql);

?>

<style type="text/css">
#main, #main td{color: #FFF; font-family:Arial;}
legend{color:#F90; font-weight: bold;}
fieldset{margin: 1em 0;}
</style>

<div id="main">

<form action="index.php">
	<fieldset>
	<legend>Тип артефакта:</legend>
	
	<p>Существующий тип:
	<select name="existed_type_id">
	<option value="">-------</option>
	<?php foreach($arttypes as $type): ?>
	<option value="<?php echo $type['id'] ?>"><?php echo $type['name'] ?></option>
	<?php endforeach ?>
	</select> 
	</p>
	
	<p>
		Имя-идентификатор типа: <input size="40" type="text" name="type_name" />
	</p>
	<p>Локализация типа:</p>
	<table>
		<tr><td>Русский:</td><td><input size="50" type="text" name="type_loc3" /></tr>
		<tr><td>Английский:</td><td><input size="50" type="text" name="type_loc1" /></tr>
		<tr><td>Немецкий:</td><td><input size="50" type="text" name="type_loc2" /></tr>
	</table>
	</fieldset>

	<fieldset>
	<legend>Название артефакта:</legend>
	<p>
		Имя-идентификатор: <input size="40" type="text" name="name" />
	</p>
	<p>Локализация:</p>
	<table>
		<tr><td>Русский:</td><td><input size="50" type="text" name="loc3" /></tr>
		<tr><td>Английский:</td><td><input size="50" type="text" name="loc1" /></tr>
		<tr><td>Немецкий:</td><td><input size="50" type="text" name="loc2" /></tr>
	</table>
	</fieldset>
	
	
	<fieldset>
		<legend>Параметры:</legend>
	
	</fieldset>
	
</form>


</div>