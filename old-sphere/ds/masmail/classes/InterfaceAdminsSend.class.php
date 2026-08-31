<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/07 08:45:57 $';

require_once('./classes/InterfaceBase.class.php');

class InterfaceAdminsSend extends InterfaceBase 
{
	
	protected function _getParticipants($common, $langID, $data)
	{
		assert((int)$langID > 0);
		
		$sql = "SELECT u.*
				FROM ds_user as u
				WHERE 
					u.admin = true
					AND
					u.id_language = ".(int)$langID."
					AND 
					not exists
					(
						SELECT id_user
						FROM ds_mail_disabled_mails
						WHERE id_user = u.id_user
					)";
		$res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty($common->errormsg));
		
		return $res;
	}

	function getName()
	{
		return "Рассылка админиcтраторам";
	}
		
	function getOver()
	{
		return "Это тестовая рассылка админиcтраторам. Так сказать тестовый первенец системы.";
	}
}


?>