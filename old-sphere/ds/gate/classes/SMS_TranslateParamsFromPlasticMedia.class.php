<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2009/08/12 12:13:10 $';

require_once($_SERVER['DOCUMENT_ROOT'].'/ds/gate/classes/SMS_TranslatorInterface.class.php');

/**
 * Класс для перевод праметров от Plastic Media
 *
 */
class SMS_TranslateParamsFromPlasticMedia
{
	public $error = '';
	
	private $in_data;
		
	public function __construct()
	{
	}
	
	public function Translate(array $params)
	{		
		$this->in_data = $params;
		
/*
	account номер абонента(в федеральном формате с кодом страны) 	-> 'phone'
	service_number короткий номер (integer) 					 	-> 'shortcode'
	company номер оператора						 					-> 'group'
	transaction номер транзакции(integer) 							-> 'trans_id'
	message сообщение абонента вместе с префиксом в кодировке UTF8  -> 'body'
*/
		$data = array();

		if(!key_exists('phone', $params))
		{
			$this->error = 'NO_ACCOUNT';
			return false;
		}
		
		if(!key_exists('isnn', $params))
		{
			$this->error = 'NO_SERVICE_NUMBER';
			return false;
		}
		
		if(!key_exists('op', $params))
		{
			$this->error = 'NO_COMPANY';
			return false;
		}
		
		if(!key_exists('sms_id', $params))
		{
			$this->error = 'NO_TRANSACTION_ID';
			return false;
		}
		
		if(!key_exists('text', $params))
		{
			$this->error = 'NO_MESSAGE';
			return false;
		}
		
		// Новый параметр
		if(key_exists('PAID', $params))
		{
			$data['PAID'] = $params['PAID'];
		} else {
			$data['PAID'] = 'YES';
		}
		
		// перевод параметров
		$data['phone'] 		= $params['phone'];
		$data['shortcode'] 	= $params['isnn'];
		$data['group'] 		= $params['op'];
		$data['trans_id'] 	= $params['sms_id'];
		$data['body'] 		= iconv("UTF-8", "windows-1251", $params['text']);
		if(key_exists('emulator', $params))
		{
			$data['emulator'] = $params['emulator'];
		}

		return $data;
	}
	
	public function getTestMode()
	{
		return false;
	}
	
	public function getPartnerCode()
	{
		return 11;
	}
	
	public function fetchErrorCode()
	{
		return $this->error;
	}
}

?>