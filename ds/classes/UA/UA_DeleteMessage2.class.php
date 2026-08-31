<?php $CVS_Version = '$Revision$ ; $Date$';

// команда удаления сообщения



class UA_DeleteMessage extends UA_Action {

	public $id_message;	
	
	public $table = array('','reserv');
	
	public $forDelete;
	
	function UA_DeleteMessage () {
		UA_Action::UA_Action ();
	}

	function checkLocalParams () 
	{
		// id сообщения
		$this->id_message = $this->root->get_attribute ( "id_message" );			

		if ( empty ( $this->id_message ) ) 
		{
			$this->errormsg .= " SYSTEM ERROR UA_DeleteMessage::checkLocalParams1";
			return false;
		}
		
		$tmp = explode(',', $this->id_message);
		foreach($tmp as  $k=>$messageID)
		{
			foreach ($this->table as $key=>$postfix)
			{
				$sql = "SELECT id_message
						FROM ds_userhasmessage".$postfix."
						WHERE 
								id_userinstance = ".$this->params["uiid"]."
							AND id_message = ".$messageID;			
				$res = $this->common->getResultArray ( "", $sql, "", "", "");

				if(!$res && $key == 1) 
				{
					$this->errormsg .= " SYSTEM ERROR UA_DeleteMessage::checkAction1";
					return false;
				} elseif ($res) {					
					$this->forDelete[$messageID] = $postfix;
					break;					
				}
			} //END foreach ()			
		}
		return true;
	}

	function checkAction() 
	{		
		return true;
	}

	// выполняем команду
	function performAction () 
	{		
		foreach ( $this->forDelete as $messageID=>$postfixe) 
		{

			// снимаем сообщение с юзера
			$sql = sprintf ( "delete from ds_userhasmessage".$postfixe."
					where id_userinstance = %d
					and id_message = %d",
					$this->params["uiid"],
					$messageID );
			$res = $this->common->executeQuery ( $sql );
			assert(empty($this->common->errormsg));
					
			// проверяем висит ли оно на ком-нибудь еще
			$sql = sprintf ( "
					select id_message
					from ds_userhasmessage".$postfixe."
					where id_message = %d",
					$messageID );
			$res = $this->common->getResultArray ( "", $sql, "", "", "" );
			assert(empty($this->common->errormsg));			
			

			
			if(sizeof($res) == 0) 
			{ // если не висит - прибиваем его
			
				$sql = sprintf ( "delete from ds_message".$postfixe."
					where id_message = %d",
					$messageID );
				$res = $this->common->executeQuery($sql);
				assert(empty($this->common->errormsg));					
			}
			
		}//END OF FOREACH 
		
		//if ( $_SESSION['profi_game'] )
		//{
			$noCellData = true;
		//} else {
		//	$noCellData = false;
		//}
		
		// минимальный рефреш
		if(!$this->refresh ( 2, "", "", "", $noCellData)) 
		{
			return false;
		}

		return true;
	}
}

?>