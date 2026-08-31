<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2009/04/01 11:14:07 $';

require_once($_SERVER['DOCUMENT_ROOT'].'/ds/gate/classes/SMS_TranslatorInterface.class.php');

/**
 * ����� ��� ������� ��������� �� Plastic Media
 *
 */
class SMS_TranslateParamsFromRamblerPlus
{
	public $error = '';
	
	private $in_data;
		
	public function __construct()
	{
	}
	
	public function Translate(array $params)
	{		
		$paramsXPM = simplexml_load_string(stripcslashes($params['xml_data']));
	
/*
	account ����� ��������(� ����������� ������� � ����� ������) 	-> 'phone'
	service_number �������� ����� (integer) 					 	-> 'shortcode'
	company ����� ���������						 					-> 'group'
	transaction ����� ����������(integer) 							-> 'trans_id'
	message ��������� �������� ������ � ��������� � ��������� UTF8  -> 'body'
*/
	
		$data = array();
		
		if(empty($paramsXPM[0]->Digest))
		{
			$this->error = 'NO_DIGEST';
			return false;
		}
	
		if(empty($paramsXPM[0]->ExtTransactionId))
		{
			$this->error = 'NO_TRANSACTION_ID';
			return false;
		}
		
		if(empty($paramsXPM[0]->Custom))
		{
			$this->error = 'NO_MESSAGE';
			return false;
		}
		
		$data['trans_id'] = (string)$paramsXPM[0]->ExtTransactionId;
		
		// �������� � $paramsXPM[0]->Custom;
		$subject = $paramsXPM[0]->Custom; 
		
		// �������� ����� ������
		$pattern = "/msisdn=(\S+)/";
		preg_match($pattern, $subject, $matches);
		$data['phone'] = $matches[1];
		if(empty($data['phone']))
		{
			$this->error = 'NO_PHONE';
			return false;
		}
		
		
		// �������� �������� �����
		$pattern = "/isnn=(\S+)/";
		preg_match($pattern, $subject, $matches);
		$data['shortcode'] = $matches[1];
		if(empty($data['shortcode']))
		{
			$this->error = 'NO_SHORTCODE';
			return false;
		}
		
		// �������� ��������
		$pattern = "/op=(\S+)/";
		preg_match($pattern, $subject, $matches);
		$data['group'] = $matches[1];
		if(empty($data['group']))
		{
			$this->error = 'NO_GROUP';
			return false;
		}
		
		// �������� ��� ���� �����
		$pattern = "/text=\'(.+)\'/s";
		preg_match($pattern, $subject, $matches);
		$data['body'] = $matches[1];		
		if(empty($data['body']))
		{
			$this->error = 'NO_MESSAGE';
			return false;
		}
		
		$secret =  md5($paramsXPM[0]->TransactionId.':'.$paramsXPM[0]->ExtTransactionId.':'.$paramsXPM[0]->Amount.':'.$paramsXPM[0]->Custom.':'.$paramsXPM[0]->Service.':REDACTED_API_SECRET');

		// ��������� �������
		if ($paramsXPM[0]->Digest != $secret) {
			$this->error = 'WRONG_DIGEST';
			return false;
		}
		return $data;
	}
	
	public function getTestMode()
	{
		return false;
	}
	
	public function getPartnerCode()
	{
		return 10;
	}
	
	public function fetchErrorCode()
	{
		return $this->error;
	}
}

?>