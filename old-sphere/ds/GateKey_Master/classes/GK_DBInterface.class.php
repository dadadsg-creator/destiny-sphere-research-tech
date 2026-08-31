<?php

// Интерфейс для работы с базой данных
interface GK_DBInterface
{
	public function __construct();
	
	public function executeQuery($sql);
	
	public function getResultArray($sql);
	
	public function getLastError();
}

?>