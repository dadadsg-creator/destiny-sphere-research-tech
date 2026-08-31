<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/DropInstance.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

/**

* Class for deleting userinctance

*/

class DropUserInstance {

	

	/** DB object */

	var $common = '';

	

	/** DropInstance object*/

	var $drop = '';

	

	function DropUserInstance (){

		$this->common = &ClassFactory::getInstance ( "DS_Common" );

		if(!$this->common->connect()){

			$this->error("Error: Cant connect to DB!");

		};

		

		$this->drop = new DropInstance;

		$this->drop->setOutput (true);

	}

	

	/** Function for error handling */

	function error ($msg){

		die('Error: DropUserInstance: '.$msg);

	}

	

	/** Main method */

	function drop ($dbId, $uiid = 0, $name = ''){

		

		$res = '';

		

		$this->invariant ();

		

		settype($dbId, 'integer');

		settype($uiid,'integer');

		$this->assert ($uiid || $name, 'Not seted one of parameters $uiid or $name');

		

		$this->common->setDbId($dbId);
		$this->drop->setCommon ($this->common);

		if ( $uiid ) {

			$sql = "SELECT 

						id_userinstance 

					FROM 

						ds_userinstance 

					WHERE 

						id_userinstance = ".$uiid;

		} elseif ( $name ) {

			$sql = "SELECT 

						id_userinstance 

					FROM 

						ds_user u, 

						ds_userinstance ui 

					WHERE 

						u.id_user = ui.id_user and 

						username = '".$name."'";

		}

					

		$res = $this->common->getQueryArray ( $sql );

		$this->assert( empty( $this->common->errormsg) , 'DropUser::dropUserInstance999');

		

		foreach ( $res as $k=>$v ) {

			$this->assert( ( $v['id_userinstance'] > 0 ) , 'DropUser::dropUserInstance1000' );

			sleep(1);

			echo "Deleting user instance with ID = " . $v['id_userinstance'] . "<br>";

			flush();

			$worldData = $this->drop->getWorldsData($uiid);
			$this->assert(is_array($worldData),'DropUser::Statistic1');
			$this->assert((sizeof($worldData)!=0),'DropUser::Statistic2');

			$premium = $this->drop->checkUserOnPremium($uiid);		

			$this->common->executeQuery ( "begin;" );

			$this->common->executeQuery ( "set STATEMENT_TIMEOUT to 0;" );			

			$this->drop->drop ($v['id_userinstance']);			
			$this->common->executeQuery ( "commit" );
			
			//Удаляем сведения из мастера об инстансе
			$this->common->setDbId(0);
			$this->drop->setCommon ($this->common);
			$sql = "DELETE FROM ds_userinstancecollection 
					WHERE 
						id_userinstance = ".$v['id_userinstance']." AND
						dbname = '".$this->common->dbWorldAccess[$dbId]['server']."'";
			$this->common->executeQuery($sql);			
			$this->assert( empty( $this->common->errormsg) , 'DropUser::dropUserOnMaster');
			
			$this->drop->incrementDelInstanceCounter($dbId, $worldData['worldname'], $worldData['id_world'],$premium);			
			$this->common->setDbId($dbId);
			$this->drop->setCommon ($this->common);
			
		}//END OF foreach()

		

	}//END OF METHOD

	

	/** Testing input params */

	function assert ($val, $code){

		if(!$val){

			die('Error: DropUserInstance->assert: '.$code);

		}

	}//END OF METHOD

	

	/** Test of object global status */

	function invariant (){

		if (!$this->common){

			die ('Error: DropUserInstance->invariant: Dont set attribute $this->common');

		} 

		

		if (strtolower(get_class($this->common)) != 'ds_common'){

			die ('Error: DropUserInstance->invariant: Uncorrect type of $this->common - '.get_class($this->common));

		}

		

		foreach ($this->common->dbWorldAccess as $k=>$v){

			if(!$v['conn']){

				die('Error: DropUserInstance->invariant: DS_Common cant connect to DB with dbId = '.$k);

			}

		}

		

		if (!$this->drop){

			die ('Error: DropUserInstance->invariant: Dont set attribute $this->drop');

		} 

		

		if (strtolower(get_class($this->drop)) != 'dropinstance'){

			die ('Error: DropUserInstance->invariant: Uncorrect type of $this->drop - '.get_class($this->drop));

		}

	}//END OF METHOD

	

}//END OF CLASS



?>