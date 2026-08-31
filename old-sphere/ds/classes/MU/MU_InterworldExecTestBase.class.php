<?php

/**
 * Базовый класс для работе менеджеров 
 *
 */
class MU_InterworldExecTestBase extends MU_InterworldBase
{
	protected $worldData;
	
	public function __construct($worldData)
	{
		assert(is_array($worldData) && count($worldData) > 0);
		$this->worldData = $worldData;
	}
}

?>