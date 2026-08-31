<?php $CVS_Version = '$Revision: 1.9 $ ; $Date: 2008/07/18 11:43:43 $';

/**
 * $Author: 40min $
 * $Date: 2008/07/18 11:43:43 $
 * $Revision: 1.9 $
 *
 *  ласс гейта дл€ получени€ информации о пользователе
 *
 */

define('secret_word_user', '5GhkdoWM94mI');

class BG_UserData extends BG_Common
{
	public function __construct($data)
	{
    parent::__construct($data);
	}

	/**
	 * ѕолучаем данные пользовател€ по логину и паролю
	 *
	 */
	public function getUserDataByLoginAndPassword()
	{
    if($this->error_status != 0)
		{
			return;
		}

		if(!array_key_exists('login',$this->incoming_info) ||
			!array_key_exists('password',$this->incoming_info))
		{
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}

		$common = DS_Common::CreateForCurentDB();
		$uid = VD_User::Validate($this->incoming_info['login'],
								$this->incoming_info['password'],
								$common);

		$email = '';
		$pass_wrong = 2;

    $from_partner = 2;
    $from_site_id = 0;
    $reg_date = '';
		if(false == $uid)
		{
			if( constant('ENABLE_RAMBLER_ID') ) {

				$rsid = GE_Login::GetRSIDByLoginAndPassword($this->incoming_info['login'], $this->incoming_info['password']);
				if($rsid !== false)
				{
          $_GET['rsid'] = $rsid;
					$ramblerLogin = new GE_RamblerLogin();

					//затыка: проверка на то, получилось ли вз€ть ramblerLogin или нет
					$rLogin = $ramblerLogin->getRamblerLogin();
					if(!empty($rLogin)){
						$user = $ramblerLogin->execLogin();

						if(is_object($user) && $user instanceof DE_User){
              //$from_partner = 6;
							$uid = $user->getID();
							$email = $user->getEmail();
							$pass_wrong = 0;
						}
						else{
							$this->error_status = 3;
							$this->outputAnswer();
							return;
						}
					}
				}
			}

			// провер€ем есть ли пользователь с присланным id (если пароль не подошЄл)
			if(empty($uid)){
				if( $uid = DS_User::GetUserIDByName($this->incoming_info['login']) ){
					$pass_wrong = 1;
				}
			}

		} else {
			$pass_wrong = 0;

      $rec = DS_User::GetRecommendedUser($uid);

      if ($rec)
      {
        $this->addToResult('rec_from_site_id', $rec['from_site_id']);
        $this->addToResult('rec_login', $rec['username']);
        $this->addToResult('rec_game_pass_hash', $rec['password']);
        $this->addToResult('rec_email', $rec['email']);
        $this->addToResult('rec_reg_date', $rec['registrationdate']);
        $this->addToResult('rec_from_partner', $rec['from_partner']);
   	  	$this->addToResult('rec_id_user',  $rec['id_user_recomend']);
      }
      else
      {
        $this->addToResult('rec_id_user',0);
      }
    }

		if(empty($uid)){
			$uid = 0;
		} elseif (empty($email)){

			$data = DS_User::getByID($uid);

      if(!$data){
				$this->error_status = 6;
				$this->outputAnswer();
				return;
			}

			$email = $data['email'];
      $reg_date = $data['registrationdate'];
			$from_partner = $data['from_partner'];
      $from_site_id = $data['from_site_id'];
      if (!$from_site_id)
        $from_site_id = 0;

      if (!$from_partner)
			  $from_partner = 2;
		}

		$this->addToResult('uid', $uid);
		$this->addToResult('reg_date', $reg_date);
    $this->addToResult('email', $email);
		$this->addToResult('from_partner', $from_partner);
    $this->addToResult('from_site_id', $from_site_id);
		$this->addToResult('pass_wrong', $pass_wrong);
		$this->outputAnswer();
		return;
	}


	/**
	 * ѕолучаем данные пользовател€ по id и хешу
	 *
	 */
	public function getUserDataByIDAndHash()
	{
		if($this->error_status != 0){
			return;
		}

		$this->incoming_info['id'] = intval($this->incoming_info['id']);

		if( empty($this->incoming_info['id']) || empty($this->incoming_info['hash']) )
		{
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}

		$data = DS_User::getByID($this->incoming_info['id']);
    //SU_DebugTools::LogAll('UserData='.var_export($data, true),'BillingGate.log');
		if(!$data){

			$this->addToResult('login',0);
			$this->addToResult('email','');
			$this->addToResult('pass_wrong', 2);
			$this->outputAnswer();
			return;
		}

    // проверка хеша
		$needHash = md5(md5($data['password'].constant('secret_word_user')));
		if( strcmp($needHash, $this->incoming_info['hash']) != 0 ){

			$this->addToResult('login',0);
			$this->addToResult('email','');
			$this->addToResult('pass_wrong', 1);
			$this->outputAnswer();
			return;
		}

		$from_partner = $data['from_partner'];
    if (!$from_partner)
		    $from_partner = 2;

    $from_site_id = $data['from_site_id'];
    if (!$from_site_id)
        $from_site_id = 0;


    $reg_date = $data['registrationdate']?$data['registrationdate']:'';
    $rec = DS_User::GetRecommendedUser($this->incoming_info['id']);

    if ($rec)
    {
      $this->addToResult('rec_id_language', $rec['id_language']);
      $this->addToResult('rec_from_site_id', $rec['from_site_id']);
      $this->addToResult('rec_login', $rec['username']);
      $this->addToResult('rec_game_pass_hash', $rec['password']);
      $this->addToResult('rec_email', $rec['email']);
      $this->addToResult('rec_reg_date', $rec['registrationdate']);
      $this->addToResult('rec_from_partner', $rec['from_partner']);
   		$this->addToResult('rec_id_user',  $rec['id_user_recomend']);
    }
    else
    {
      $this->addToResult('rec_id_user',0);
    }

    $this->addToResult('id_language', $data['id_language']);
    $this->addToResult('from_site_id', $from_site_id);
		$this->addToResult('reg_date', $reg_date);
    $this->addToResult('login',$data['username']);
		$this->addToResult('email',$data['email']);
		$this->addToResult('from_partner', $from_partner);
		$this->addToResult('pass_wrong', 0);
		$this->outputAnswer();
		return;
	}


	/**
	 * ‘ункци€ провер€ет, содержитс€ ли данный игрок в данном мире
	 * */
	public function checkPresence()
	{
		if (!key_exists('id_user', $this->incoming_info) || empty($this->incoming_info['id_user']) ||
			!key_exists('world_name_ru', $this->incoming_info) || empty($this->incoming_info['world_name_ru'])) {
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}

		$id_user = intval($this->incoming_info['id_user']);
		$world_name_ru = $this->incoming_info['world_name_ru'];

		GE_SearchStart::SetCommon(DS_Common::CreateForCurentDB());
		$worldData = GE_SearchStart::GetWorldsWhereUserIsRegistered($id_user);

		if (!is_array($worldData)) {
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}

		foreach ($worldData as $wd) {
			if (key_exists('worldname', $wd) && 0 === strcmp($world_name_ru, $wd['worldname'])) {
				$this->addToResult('user_present', 1);
				$this->outputAnswer();
				return;
			}
		}

		$this->addToResult('user_present', 0);
		$this->outputAnswer();
		return;
	}
}

?>