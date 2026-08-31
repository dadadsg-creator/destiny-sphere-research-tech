<?php

/**
 * $Author: 40min
 * $Date: 2007/10/29
 * 
 * Класс гейта для выборки списка рефералов игрока по его логину
 *
 */
class BG_UserReferals extends BG_Common 
{
	public function __construct($data)
	{
		parent::__construct($data);		
	}
	
	/**
	 * Получаем список рефералов в виде строки user_id, разделённых символом :
	 *
	 */
	public function proceed()
	{		
		if($this->error_status != 0)
			return;
			
		if(key_exists('get_all', $this->incoming_info)) 
		{
			if(!key_exists('date_time', $this->incoming_info)){
				$this->error_status = 2;
				$this->outputAnswer(); 
				return;
			}
			
			if(!preg_match("/^[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2} [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}$/", $this->incoming_info['date_time'])){
				
				$this->error_status = 3;
				$this->outputAnswer(); 
				return;
			}
			$this->proceedAll($this->incoming_info['date_time']);
			return ;
		}
		
		if(!key_exists('login', $this->incoming_info)) 
		{
			$this->error_status = 1;
			$this->outputAnswer(); 
			return;
		}		
		elseif (!VD_UserName::Validate($this->incoming_info['login']))
		{
			$this->error_status = 4;
			$this->outputAnswer(); 
			return;
		}
		
		$refs = DS_User::GetReferalsByLogin($this->incoming_info['login']);
		$refs_arr = array();
		foreach ($refs as $val)
			$refs_arr[] = $val['id_user'];
					
		$this->addToResult('ids', join(':', $refs_arr));
		$this->outputAnswer(); 
		return;
	}
	
	private function proceedAll($dateTime)
	{
		$refs = DS_User::GetReferalsAll($dateTime);
		if($refs === false){
			$this->error_status = 5;
			$this->outputAnswer(); 
			return;
		}
		elseif (!$refs ){
			$this->addToResult('ids', '');
			$this->outputAnswer(); 
			return;
		}
		
		$refs_arr = array();
		foreach ($refs as $val)
			$refs_arr[$val['id_user_recomend']][] = $val['id_user'];
			
		$result = '';
		foreach ($refs_arr as $k=>$v)
			$result .= $k.'#'.join('|',$v).'@';
					
		$this->addToResult('ids', $result);
		$this->outputAnswer(); 
		return;
	}
}

?>