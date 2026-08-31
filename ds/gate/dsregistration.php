<?
require_once('classes/File.class.php');
/*
		$log = print_r($_GET, true);
		$log .= "time=".date("Y-m-d H:i:s")."\n";
		$fp = fopen('messages.log','a+');
		fwrite($fp,$log);
		fclose($fp);
*/
//require_once('../settings.php');
//require_once('../classfactory.php');

require_once('../Main.php');

/**
 * Класс - гейт служит для аброботки входных данных от SMX.com заявок на аутентификацию
 * телефона и привязки его к определенному логину
 * @version 1.00
 *
 */
class SMSRegistration
{
	var $common;

	/* SMS Info */
	var $trans_id;
	var $group;
	var $shortcode;
	var $phone;

	/* User Info */
	var $username;
	var $password;
	var $id_user;


	/* системные переменные */
	var $log = array();

	/* Для ответа СМС-ком о статусе обработки пришедшего сообщения*/
	var $response_status = 0; //default
	var $response_text = 'Vashe soobshenie ne bilo dostavlenno! Poprobuyte zanovo!';


	function SMSRegistration()
	{
		$this->common = new DS_Common;
		$this->common->setDbId(0);

		if (!$this->checkIncomeParams($_GET))
		{
			$this->sendResponse();
		} else if (!$this->checkUsername($this->username, $this->password) )
		{
			$this->sendResponse();
		} else if (!$this->updateUser() )
		{
			$this->sendResponse();
		} else {
			$this->response_status = 0;
			$this->response_text = "Registraciya proshla uspeshno!";
			$this->sendResponse();
		}
	}

	function checkIncomeParams($data)
	{
		$this->log = print_r($data, true);

		if (sizeof($data) <= 0)
		{
			$response_text = 'Otsutstwuyut whodyaschie dannye!';
			return false;
		}

		if (!key_exists('phone', $data) || strlen($data['phone']) < 5)
		{
			$this->response_text = 'Ne ukazan telefon ili ukazan newerno!';
			return false;
		} else $this->phone = $data['phone'];

		if (!key_exists('shortcode', $data))
		{
			$this->response_text = 'Ne ukazan shortcode!';
			return false;
		} else $this->shortcode = $data['shortcode'];

		if (!key_exists('group', $data))
		{
			$this->response_text = 'Ne ukazan group!';
			return false;
		} else $this->group = $data['group'];

		if (!key_exists('trans_id', $data))
		{
			$this->trans_id = '';
		} else $this->trans_id = $data['trans_id'];

		if (!key_exists('body', $data))
		{
			$this->response_text = 'Ne ukazan body!';
			return false;
		}

		if (!$this->parseBody($data['body']) )
		{
			return false;
		}

		return true;

	}

	function getResponseStatus()
	{
		return $this->response_status;
	}

	function getResponseText()
	{
		return $this->response_text;
	}

	function sendResponse()
	{
		//var_dump(debug_backtrace());
		//echo $this->response_status."|".$this->response_text;
		$this->saveLog();
	}

	function saveLog()
	{
		$file = new File('dsregistration.log','w');
		$this->log .= "time=".date("Y-m-d H:i:s")."\n";
//		$this->log .= "response_url=".$this->response_url."\n";
		$this->log .= "response_status=".$this->response_status."\n";
		$this->log .= "response_text=".$this->response_text."\n";

		$file->writeIn($this->log);
		$file->closeFile();
	}

	function parseBody($body)
	{
		if (empty($body))
		{
			$this->response_text = 'Pustoy tekst v SMS!';
			return false;
		}

		$array = explode(" ", $body);

		foreach ($array as $key => $value)
		{
			if ($value == '')
			{
				unset($array[$key]);
			}
		}
		$array = array_values($array);

		if (sizeof($array) < 4)
		{
			$this->response_text = 'Odin iz parametrov ukazan neverno!';
			return false;
		}

		if (strtoupper($array[0]) != "ON" || strtoupper($array[1]) != "DSREG")
		{
			$this->response_text = 'Kluchevoe slovo ukazano neverno!';
			return false;
		}

		$this->username = trim($array[2]);
		$this->password = trim($array[3]);


		return true;
	}

	function checkUsername($username, $password = null)
	{
		/* Эти параметр обязателен для правильной передачи символа _ через мобильный телефон */
		$username = str_replace("§","_",$username);

		if (empty($username) && empty($password))
		{
			$this->response_text = 'Wy ne ukazali imya igroka, kotoromu budet dostawleno soobschenie';
			return false;
		}

		if (empty($username) && !empty($password))
		{
			$this->response_text = 'Wy ne ukazali swoi login!';
			return false;
		}

		/* Проверяем на существование юзверя, если есть и логин и пароль. (значит это отправитель */
		if (!empty($username) && !empty($password))
		{
			$sql = "
					SELECT
						u.id_user
					FROM
						ds_user AS u
					WHERE
						(upper(u.username) = upper('".$username."')) AND
						(u.password = md5('".$password."'))
			";
			//echo $sql."<br>";

			$res = $this->common->getResultArray("",$sql,"","","");
			if ($res === false)
			{
				$this->response_text = 'GetUserName Error!';
				return false;
			}

			if (sizeof($res)>0)
			{
				$this->id_user = (int)$res[0]['id_user'];
			} else {
				$this->response_text = 'Wy wweli swoi login ili parol` newerno!';
				return false;
			}

		}
		return true;
	}

	function updateUser()
	{
		if ($this->id_user <= 0)
		{
			$this->response_text = 'Ukazanniy polzovatel` ne nayden!';
			return false;
		}


		/* Проверяем зарегистрирован ли этот пользователь со своим мобильником .... */
		$sql = "SELECT id_user FROM ds_userhasmobile WHERE id_user = '".$this->id_user."'";
		$res = $this->common->getResultArray("",$sql,"","","");
		if ($res === false)
		{
			$this->response_text = 'GetPhoneNumber Error!';
			return false;
		}

		if (sizeof($res) <= 0)
		{
			//echo "INSERT!!!!!!!!!!";
			$sql = "INSERT INTO ds_userhasmobile (id_user, phone, mgroup, regdate) VALUES ($this->id_user, '".pg_escape_string($this->phone)."','".pg_escape_string($this->group)."', now() )";
			$res = $this->common->executeQuery($sql);
			if ($res == false)
			{
				$this->response_text = 'Proizoshla oshibka. Povtorite registraciyu zanovo!';
				return false;
			} else {
				return true;
			}
		} else {
			//echo "UPDATE!!!!!!!!!!";
			$sql = "UPDATE ds_userhasmobile SET phone = '".$this->phone."' , mgroup = '".$this->group."', regdate = now() WHERE id_user = ".$this->id_user."";
			$res = $this->common->executeQuery($sql);
			if ($res == false)
			{
				$this->response_text = 'Telefonnie nomer ne izmenen, povtorite registraciyu zanovo!';
				return false;
			} else {
				return true;
			}
		}
		return true;
	}
}



?>