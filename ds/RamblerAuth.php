<?php
/**
 * Странный скрипт, не правда ли? А все из-за Рамблер ИД
 */
if(key_exists('rsid', $_GET))
{
	if(preg_match('/^[0-9a-f]+$/', $_GET['rsid']))
	{
		echo $_GET['rsid'];
	}
}
?>