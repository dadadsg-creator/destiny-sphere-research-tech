<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/07 08:33:12 $';
/**
 * Класс для работы с БД
 *
 */
class Search
{
	private $common;
	
	public function __construct(DS_Common $common)
	{
		$this->common = $common;	
	}
	
	
	/**
	 * Мессаги которые по дате отправки надо отправить уже и коорые еще не были отправленны
	 *
	 */
	public function getMessageForSendToThisTime()
	{
		$sql = "SELECT *
				FROM ds_mail_message
				WHERE 
					datetosend <= '".SU_DebugTools::GetInternalDate()."'
					AND
					sendiscomplit = false";				
		$res = $this->common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	/**
	 * Извлекаем сведения о мессаге конкретной из БД
	 *
	 * @param integer $messageID
	 * @return array
	 */
	public function getMessageForSendByID($messageID)
	{
		assert(is_int($messageID) && $messageID > 0);
		
		$sql = "SELECT *
				FROM ds_mail_message
				WHERE id_message = ".$messageID;
		$res = $this->common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	/**
	 * Помечаем что начали рассылать мессагу
	 *
	 * @param integer $messageID	 
	 */
	public function markStartMessageSend($messageID)
	{
		assert(is_int($messageID) && $messageID > 0);
		
		$sql = "UPDATE ds_mail_message
				SET startdate = '".SU_DebugTools::GetInternalDate()."'
				WHERE id_message = ".$messageID;
		$res = $this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));		
	}
	
	/**
	 * Помечаем окончание рассылки
	 *
	 * @param integer $messageID	 
	 */
	public function markEndMessageSend($messageID)
	{
		assert(is_int($messageID) && $messageID > 0);
		
		$sql = "UPDATE ds_mail_message
				SET 
					enddate = '".SU_DebugTools::GetInternalDate()."', 
					sendiscomplit = true
				WHERE id_message = ".$messageID;
		$res = $this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	/**
	 * Извекаем получателей для мессаги
	 *
	 * @param integer $messageID
	 * @return array
	 */
	public function getRecepietsForMessage($messageID)
	{
		assert(is_int($messageID) && $messageID > 0);
		
		$sql = "SELECT 
					* 
				FROM 
					ds_mail_recepient AS r
					INNER JOIN ds_user AS u ON (u.id_user = r.id_user)
				WHERE 
					r.id_message = ".$messageID."
					AND
					r.senddate IS NULL
					AND
					NOT EXISTS 
					(
						SELECT *
						FROM ds_mail_disabled_mails as t
						WHERE t.id_user = u.id_user
					)";
		$res = $this->common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	
	/**
	 * Помечаем результат отправки сообщения пользователю
	 *
	 * @param integer $messageID
	 * @return array
	 */
	public function markSendResulForRecepient($recepientID, $result)
	{
		assert(is_int($recepientID) && $recepientID > 0);
		assert(is_bool($result));
		
		if($result)
		{
			$result = 'true';
		}else{
			$result = 'false';
		}
		
		$sql = "UPDATE ds_mail_recepient
				SET 
					senddate = '".SU_DebugTools::GetInternalDate()."', 
					result = $result
				WHERE id_recepient = ".$recepientID;
		$res = $this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	/**
	 * ВСтавка сообщения для рассылки
	 *
	 * @param string $subject
	 * @param string $body
	 * @param string $headers
	 * @param string $datetosend
	 * @return integer
	 */
	public function insertMessage($subject, $body, $headers, $datetosend = null)
	{
		assert(!empty($subject));
		assert(!empty($body));
		//assert(!empty($headers));
		
		if(is_null($datetosend))
		{
			$date = SU_DebugTools::GetInternalDate();
		}else{
			$date = $datetosend;
		}

		$sql = "select nextval('ds_mail_message_id_message_seq') as id";
		$res = $this->common->getResultArray ( "", $sql, "", "", "" );	
		assert(empty($this->common->errormsg) && count($res) == 1);					
		
		$id = (int)$res[0]['id'];
		assert($id > 0);		
		
		$sql = "INSERT INTO ds_mail_message
				(id_message, subject, body, headers, datetosend)				
				VALUES
				(".$id.", '".$subject."', '".$body."', '".$headers."', '".$date."')";
		$res = $this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		return $id;
	}
	
	public function insertRecipient($id_message, $id_user, $email)
	{

		assert((int)$id_message > 0);
		assert((int)$id_user > 0);
		assert(!empty($email));			

		$sql = "INSERT INTO ds_mail_recepient
				(id_message, id_user, email)				
				VALUES
				(".$id_message.", ".$id_user.",'".$email."')";
		$res = $this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));		
	}
	
	public function getUserByMailMD($emailMD)
	{				
		assert(!empty($emailMD));		
		
		$sql = "SELECT 
					* 
				FROM 
					ds_user
				WHERE md5(email) = '".$emailMD."'";
		$res = $this->common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty($this->common->errormsg));

		return $res[0];	
	}
	
	public function disableUser($id_user, $email)
	{		
		assert((int)$id_user > 0);
		assert(!empty($email));		
		
		$sql = "SELECT 
					* 
				FROM 
					ds_mail_disabled_mails
				WHERE id_user = ".$id_user;
		$res = $this->common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty($this->common->errormsg));
		
		if(count($res) > 0)
		{
			return;
		}
		
		$now = GB_TypeFactory::CreateBase('GB_Date', time());
		
		$sql = "INSERT INTO ds_mail_disabled_mails
				(id_user, email, adddate)				
				VALUES
				(".$id_user.",'".$email."', '".$now->getPgFormat()."')";
		$res = $this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));				
	}
}

?>