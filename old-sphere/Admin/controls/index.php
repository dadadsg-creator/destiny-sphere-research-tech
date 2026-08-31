<?php
foreach($_POST as $key=>$val) $$key = $val;

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/MenuThrow.php";
?>