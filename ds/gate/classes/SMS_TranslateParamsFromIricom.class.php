<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/ds/gate/classes/SMS_TranslatorInterface.class.php');

class SMS_TranslateParamsFromIricom implements SMS_TranslatorInterface
{
	public $error = '';
	
	private $in_data;
	
	public function __construct()
	{
	}
	
	public function Translate($params)
	{
		assert(is_array($params));
		
		$this->in_data = $params;
/*
	account ? номер абонента(в федеральном формате с кодом страны) -> 'phone'
	service_number ? короткий номер (integer) -> 'shortcode'
	company ? Номер оператора(полный список см ниже) -> 'group'
	transaction ? Номер транзакции(integer) -> 'trans_id'
	message ? Сообщение абонента вместе с префиксом в кодировке UTF8 -> 'body'
*/
		$data = array();
		
		if(!key_exists('account', $params))
		{
			$this->error = 'NO_ACCOUNT';
			return false;
		}
		
		if(!key_exists('service_number', $params))
		{
			$this->error = 'NO_SERVICE_NUMBER';
			return false;
		}
		
		if(!key_exists('company', $params))
		{
			$this->error = 'NO_COMPANY';
			return false;
		}
		
		if(!key_exists('transaction', $params))
		{
			$this->error = 'NO_TRANSACTION_ID';
			return false;
		}
		
		if(!key_exists('message', $params))
		{
			$this->error = 'NO_MESSAGE';
			return false;
		}
		
		// перевод параметров
		$data['phone'] 		= $params['account'];
		$data['shortcode'] 	= $params['service_number'];
		$data['group'] 		= $params['company'];
		$data['trans_id'] 	= $params['transaction'];
		$data['body'] 		= $params['message'];
		if(key_exists('emulator', $params))
		{
			$data['emulator'] = $params['emulator'];
		}

		return $data;
	}
	
	public function getPartnerCode()
	{
		return 1;
	}
	
	public function FetchErrorCode()
	{
		return $this->error;
	}
}

?>