<?php

class UC_ExecDonation
{
	static private $mailList = array(
			'maxim@destinysphere.com', 
			'dmitri@destinysphere.com',
			'netnet@destinysphere.com',
			'annita@destinysphere.com',
			);
	
	static private function _check(DE_User $user, array $data)
	{
		assert(key_exists('gc', $data));
		assert(key_exists('sc', $data));
		
		settype($data['gc'], 'integer');
		settype($data['sc'], 'integer');
		
		if($data['gc'] <= 0 && $data['sc'] <= 0)
		{
			return 'WRONG_CREDITS_COUNT';
		}
		
		if($data['gc'] <= 0)
		{
			$data['gc'] = 0;
		}
		
		if($data['sc'] <= 0)
		{
			$data['sc'] = 0;
		}
		
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$member = new MU_EntityOperationMember($user, $world);
		$account = MU_SearchOperations::GetAccountData($member);
		
		if(count($account) == 0)
		{
			return 'NO_ACCOUNT_IN_WORLD';
		}
		
		if($data['gc'] > 0 && $account['GC'] < $data['gc'])
		{
			return 'NOT_ENOUGHT_GOLD_CREDITS';
		}
		
		if($data['sc'] > 0 && $account['SC'] < $data['sc'])
		{
			return 'NOT_ENOUGHT_SILVER_CREDITS';
		}
		
		return 'OK';
	}
	
	public function Exec(DE_User $user, array $data)
	{	
		$MSG = '';
		
		// не нужно два раза делать дело
		if(key_exists('uc_donated', $_SESSION) && $_SESSION['uc_donated'] == 1)
		{
			UC_ExecUserCabinetIndex::Exec($user);
			return;
		}
		
		// если уже что-то изменилось со времени проверки, то ошибку выдаем
		$res = self::_check($user, $data);
		if($res !== 'OK')
		{
			UC_ExecUserCabinetIndex::Exec($user, $res, 'ERR');
			return;
		}
			
		//Добровольный трансфер денег на счет ДС
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberFrom = new MU_EntityOperationMember($user, $world);
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		
		$actionType = DE_MoneyActionType::CreateFromName('PVP_TRANSFER');
		
		if($data['sc'] > 0)
		{
			$summ = new MU_EntityMoneySumm((int)$data['sc'], 'SC');
			$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
			MU_ControlExecOperations::ExecOperation($operation);
		}
		
		if($data['gc'] > 0)
		{
			$summ = new MU_EntityMoneySumm((int)$data['gc'], 'GC');
			$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
			MU_ControlExecOperations::ExecOperation($operation);
		}
		
		$MSG = 'DONATION_DONE';
		
		$_SESSION['uc_donated'] = 1;

		$this->_sendMail($user, $data);	
	
		UC_ExecUserCabinetIndex::Exec($user, $MSG, 'OK');		
	}
	
	private function _sendMail(DE_User $user, array $data)
	{
		$mailSubj = 'CREDITS TRANSFER FROM PLAYER';
		$mailBody = "PLAYER TRANSFERED CREDITS TO DestinySphere\n";
		$mailBody .= "DATE: ".date("Y-d-m H:i:s")."\n";
		$mailBody .= "USER: ".$user->getName()."(".$user->getEmail().")\n";
		if($data['gc'] > 0)
		{
			$mailBody .= "GOLD: ".$data['gc']."\n";
		}
		
		if($data['sc'] > 0)
		{
			$mailBody .= "SILVER: ".$data['sc']."\n";
		}
		
		$mailBody .= "VERSION: ".constant('VERSION_NAME')."\n";

     	$header = 'MIME-Version: 1.0' . "\n" . 'Content-type: text/plain; charset=UTF-8'
    	. "\n" . "From: Destiny Sphere <noreply@destinysphere.com>\n";
     
		foreach(self::$mailList as $k => $mail)
		{
			mail($mail, $mailSubj, $mailBody, $header);
		}
	}
	
	public function ConfirmDonation(DE_User $user, array $data)
	{
		$data['MSG'] = self::_check($user, $data);
		if($data['MSG'] == 'OK')
		{
			$data['valid'] = 1;
			$_SESSION['uc_donated'] = 0;
			UC_OutUserCabinet::Out('DonateConfirm', $data);
		} else {
			UC_ExecUserCabinetIndex::Exec($user, $data['MSG']);
		}
	}
}

?>