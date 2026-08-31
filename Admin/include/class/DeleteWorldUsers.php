<?php

set_time_limit(0);

// Active assert and make it quiet
assert_options( ASSERT_ACTIVE, 		1);
assert_options( ASSERT_WARNING, 	1);
assert_options( ASSERT_BAIL, 		1);
assert_options( ASSERT_QUIET_EVAL, 	1);

require_once "DropUserInstance.class.php";

/**
* Class for Delete all userinstance from the World
*/
class DeleteWorldUsers {

	/** DropUserInstance object*/
	var $drop = '';

	/** DB object */
	var $common = '';

	/** World ID */
	var $id_world = 0;
	
	/** DB ID */
	var $dbId = 0;	

	/** Array of UserInstaces */
	var $ui_a = array();
	

	
	
	function DeleteWorldUsers () {

		$this->drop = new DropUserInstance;
		
		$this->common = & $this->drop->common;
		
		$this->selectAction();
	}
	
	
	function selectAction ( ){

		if( isset( $_POST['action'] ) && $_POST['action'] == 1 && $this->extractWorldParams( $_POST['id_world'] ) ) {
			
			$this->makeAction();

		}else{
		
			$this->showWorlds();
		}
	}
	
	function extractWorldParams ( $world_str ){
		list($dbId, $id_world) =  explode( '_', trim($world_str) );
		$this->setDbId( $dbId );
		$this->setWorldId( $id_world );
		return true;
	}
	
	function makeAction(){
		
		$sql = sprintf( "
				SELECT
					id_userinstance
				FROM
					ds_userinstance
				WHERE
					id_world = %d
					AND id_userinstance > 0
				",
				$this->id_world
			);
				
		$res = $this->common->getQueryArray ( $sql );
		
		// Проверка на наличие результата		
		assert( empty($this->common->errormsg) );
		if( $res == "" ){
			$this->add2Log('No one UserInstace Found');
			return false;
		}		
		
		foreach( $res as $key => $val ){
				
			//echo "this->drop->drop ( $this->dbId, ".$val['id_userinstance']." );<br/>";
			echo $val['id_userinstance'];
			$this->drop->drop ( $this->dbId, $val['id_userinstance'] );
		}
	}
	
	function showWorlds (){
		
		$form = "<form method='POST'>";
		
		$sql = sprintf( "
				SELECT
					worldname,
					id_world
				FROM
					ds_world
				");

		$res = $this->common->getMultySql ( $sql );

		// Проверка на наличие результата		
		assert(!$this->common->errormsg);
		if( $res == false ){
			$this->add2Log('Worlds Not Found');
			return false;
		}		
		
		$form .= 	"<select name='id_world'>";
		foreach( $res as $key => $val ){
			$form .=	"<option value='".$val['dbId']."_".$val['id_world']."'>".$val['worldname']."</option>";
		}
		$form .= 	"</select>".
				"<input type='hidden' name='action' value='1'/>".
				"<input type='Submit' name='Submit' value='ok'/>".
				"</form>";
		echo $form;
	}
	
/**********************************************************************************
*				Log Functions			 	  	  *
**********************************************************************************/
	/*
	**  Установка имени лог-файла и открытие его на запись
	**  Линк на ресурс храниться в глобальной переменной $LogFH
	*/
	function setLogFile ( $name ){
		
		// Фикс задержки отображения информации у IE
		echo '<!-- '; for($i=0;$i<256;$i++) echo '!!!!'; echo ' -->'; flush();		
		
		
//		closeLogFile( $GLOBALS['LogFH'] );
		
		$this->LogFile = $name.' '.date("Y-m-d").".log";
		
		// Проверяем создан ли ЛОГ-файл
		if( !file_exists( $this->LogPath.$this->LogFile ) ){
			
			$fh = fopen($this->LogPath.$this->LogFile, 'at+');
			fclose($fh);
		}
		
		
		if( !is_writable($this->LogPath.$this->LogFile) ){
				
			$this->wfrite = false;
			$this->add2Log("Function write to Log file is Disabled.");
		}
	}


	/*
	**  Добавляем Строку в лог
	**  и если передаем вторым параметром true, то и в файл тоже пишем
	*/
	function add2Log ( $str , $file=false ){
		
		if( $file && $this->fwrite )
			error_log($str, 3, $this->LogPath.$this->LogFile);
//			fwrite( $GLOBALS['LogFH'],  $str."\n" );
		
		echo $str."<br>\n";
		flush();
	}


/**********************************************************************************
*				Set Var Functions			 	  *
**********************************************************************************/
	/** 
	 * Устанавливаем значение $this->common 
	 * Параметры: 
	 * $common - объект класса DS_Common
	 */
	function setCommon( &$common ){
		assert(is_a($common, "DS_Common"));
		$this->common = &$common;
		return true;
	}//END OF METHOD setCommon($common)


	/** 
	 * Устанавливаем значение ID базы 
	 * Параметры: 
	 * $id - целочисленное ID базы равный или более 0
	 */
	function setDbId($id){
		settype($id, 'integer');
		$this->dbId = $id;
		assert( $this->dbId >= 0 );
		$this->common->setDbId( $this->dbId );
		return true;
	}//END OF METHOD setDbId($id)


	/** 
	 * Устанавливаем значение ID сферы 
	 * Параметры: 
	 * $id - целочисленное ID сферы равный или более 0
	 */
	function setWorldId( $id ){
		settype($id, 'integer');
		$this->id_world = $id;
		assert( $this->id_world >= 0 );
		return true;
	}//END OF METHOD setWorldId($id)
	
	

	/** 
	 * Устанавливаем значение $this->ui_a 
	 * Параметры: 
	 * $array - массив с юзер-инстансами текущего мира
	 */
	function setUserInstanceArray( $array ){
		assert( is_array( $array ) );
		assert( count( $array ) > 0 );
		$this->ui_a = $array;
		return true;
	}//END OF METHOD setUserInstanceArray( $array )
}

$dwu = new DeleteWorldUsers;
?>