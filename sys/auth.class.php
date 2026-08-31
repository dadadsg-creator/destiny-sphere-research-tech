<?php
/**
 * Класс GlobalClassAuth
 * 
 *
 * @author Ivan Sologub <fiva@meta.ua>
 * @package common
 */

//Константа паспортного адреса
define("META_PASSPORT_URL","http://passport.meta.ua/");

class GlobalClassCrypt{
	/**#@+
		* ### Private properties ###
		* Внутренние параметры системы
		*
		*
		* @access public
		*/
	/**
		* параметры для работы с ситемой шифрования. 
		* Используются в конструкторе
		*
		*/

	var $sPublicKey="";
	var $sEncMethod="tripledes";
	var $sEncMode="cfb";
	var $rTd=false;
	var $sIv="";
	/**
		* Constructor
		* Проверяет возможности криптования mcrypt и правильность введенных данных
		* 
		* @access public
		* @param string $sPublicKey Ключ проекта, выданный МетаПаспортом
		* @param string $sEncMethod метод криптования аутентификационной строки
		* @param string $sEncMode	режим криптования аутентификационной строки
	 */
	
	function GlobalClassCrypt($sPublicKey,$sEncMethod="tripledes",$sEncMode="cfb"){
				//Проверяем, установлена ли библиотека libmcrypt
		if(!function_exists("mcrypt_list_algorithms")){
			$this->_triggerError("Please setup libmcrypt http://mcrypt.hellug.gr/lib/index.html");
			return;
		}
		$aAlgorithms = mcrypt_list_algorithms();
		//Проверяем правильность введенного алгоритма криптования
		if(!in_array($sEncMethod,$aAlgorithms)){
			$this->_triggerError("Unknown encryption algoritm $sEncMethod");
			return;
		}
		$this->sEncMethod=$sEncMethod;

		$aModes=mcrypt_list_modes();
		//Проверяем правильность введенного режима криптования
		if(!in_array($sEncMode,$aModes)){
			$this->_triggerError("Unknown encryption mode $sEncMode");
			return;
		}
		$this->sEncMode=$sEncMode;

		//Проверка длины ключа шифрования
		$iKeySize=mcrypt_get_key_size($sEncMethod,$sEncMode);
		if($iKeySize!=strlen($sPublicKey)){
			$this->_triggerError("Wrong public key length. Public key length must contain $iKeySize symbols");
			return;
		}


		$this->sPublicKey=$sPublicKey;
		$this->rTd = mcrypt_module_open ($this->sEncMethod, '',$this->sEncMode, '');
		$iIvSize=mcrypt_enc_get_iv_size ($this->rTd);
		$this->sIv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($this->rTd), MCRYPT_RAND);

	}
	/**
		* Destructor
		* Закрывает открытый модуль шифрования
		* 
		* @access public
		* @param null
	 */

	function __destruct(){
		if(isset($this->rTd)){
			mcrypt_module_close ($this->rTd);
			unset($this->rTd);
		}
	}
	/**#@+
		* ### Private Methods ###
		*
		* @access public
		*/
	/**
		* Функция шифрования аутентификационной строки
		*
		*
		* @access private
		* @param string $sPlainText текст для шифрования
		*
		*/	
	function _encrypt($sPlainText){

		$bNeedClose=false;
		if(!isset($this->rTd)){
			$this->rTd = mcrypt_module_open ($this->sEncMethod, '',$this->sEncMode, '');
			$bNeedClose=true;
		}
		if (mcrypt_generic_init ($this->rTd, $this->sPublicKey, $this->sIv) != -1){
			$sEncText=base64_encode(mcrypt_generic($this->rTd,$this->sIv.$sPlainText));
			mcrypt_generic_deinit ($this->rTd);
			if($bNeedClose){
				mcrypt_module_close ($this->rTd);
				unset($this->rTd);
			}
			return $sEncText;
		}else{
			$this->_triggerError("Couldn't initialize mcrypt generic for encryption");
			return false;
		}
	}

	/**
		* Функция расшифровки аутентификационной строки
		*
		*
		* @access private
		* @param string $sEncText текст для шифрования
		*
		*/	
	function _decrypt($sEncText){

		$bNeedClose=false;
		if(!isset($this->rTd)){
			$this->rTd = mcrypt_module_open ($this->sEncMethod, '',$this->sEncMode, '');
			$bNeedClose=true;
		}

		if (mcrypt_generic_init ($this->rTd, $this->sPublicKey, $this->sIv) != -1){
		  $sPlainText=substr(mdecrypt_generic($this->rTd,base64_decode($sEncText)),strlen($this->sIv));
			mcrypt_generic_deinit ($this->rTd);
			if($bNeedClose){
				mcrypt_module_close ($this->rTd);
				unset($this->rTd);
			}
			return $sPlainText;
		}else{
			$this->_triggerError("Couldn't initialize mcrypt generic for decryption");
			return false;
		}
	}
	/**
	 * Trigger errors
	 *
	 * Generates E_USER_WARNING with given message. 
	 *
	 * @param string $sMessage A summary error message
	 */
	function _triggerError($sMessage) {
		$sMessage .= "\n";
		trigger_error($sMessage, E_USER_WARNING);
	}
	/**#@-*/

}

class GlobalClassAuth extends GlobalClassCrypt{

	/**#@+
		* ### Public properties ###
		* Параметры пользователя (логин/ник, идентификатор, дата рождения,
		* необратимый хеш пароля)
		* 
		* @access public
		*/
	var $sUserName="";
	var $iUserId=0;
	var $sUserSex="";
	var $sUserBirth="";
	var $sUserPassHash="";
	/**#@+
		* ### Private properties ###
		* Внутренние параметры системы
		*
		*
		* @access public
		*/
	/**
		* параметры для работы с ситемой шифрования. 
		* Используются в конструкторе
		*
		*/
	var $sFrom="";
	var $iCookieTimeLimit=3600;

	/**
		* параметры для работы авторизационными куками
		* $bNoRedir используется для запрета редиректа при отключенных куках
		*
		*/
	var $sCookieDomain="";
	var $sPassportCookieName="lauth";
	var $bNoRedir=false;
	var $sPassportRedirect="";

	/**
		* Constructor
		* Отвечает за логин пользователя на проекте
		* 
		* @access public
		* @param string $sPublicKey Ключ проекта, выданный МетаПаспортом
		* @param string $sFrom Алиас проекта, выданный МетаПаспортом
		* @param string $sPassportCookieName Имя локальной куки для авторизации
		* @param int $iCookieTimeLimit время жизни аутентификационной сессии
		* @param string $sEncMethod метод криптования аутентификационной строки
		* @param string $sEncMode	режим криптования аутентификационной строки
	 */
	function GlobalClassAuth($sPublicKey,$sFrom,$sPassportCookieName="lauth",$iCookieTimeLimit=3600,$sEncMethod="tripledes",$sEncMode="cfb"){

		$this->sPassportCookieName=$sPassportCookieName;

		//Проверка правильности введенного времени жизни куки
		$this->iCookieTimeLimit=intval($iCookieTimeLimit);

		if($this->iCookieTimeLimit<=0){
			$this->_triggerError("Please set cookie time limit over than 0");
			return;
		}
		
		$this->GlobalClassCrypt($sPublicKey,$sEncMethod,$sEncMode);

		$this->sFrom=$sFrom;

		//Получаем данные о домене и устанавливаем параметры работы с куками
		$sServerHost=$_SERVER["HTTP_HOST"];
		$aReverseDomains=array_reverse(explode(".",$sServerHost));
		if($aReverseDomains[0]!="ua"||$aReverseDomains[1]!="meta"){
		//Этот скрипт не сможет прочитать куку .meta.ua
			$this->bNoRedir=true;
		}
		/**
			* определяем на какой домен ставить куку. Если домен состоит более чем из 4х чанков
			* и последний чанк не www ставим куку на .domain, если последний чанк www - отсекаем его
			* Если домен состоит из одного чанка, то берем его за основу
			*/
		$sLastChunk=array_pop($aReverseDomains);
		if(count($aReverseDomains)<3&&$sLastChunk!="www"){
				$this->sCookieDomain=(count($aReverseDomains)<1?"":".$sLastChunk");
		}
		while(count($aReverseDomains)>3){
			array_pop($aReverseDomains);
		}
		foreach(array_reverse($aReverseDomains) as $sChunk){
			$this->sCookieDomain.=".$sChunk";
		}
		//Получаем строку редиректа без параметра sauth
		$sWithoutSauth = preg_replace("/&?sauth=([^&])*/", "", $_SERVER['REQUEST_URI']);
		$sWithoutSauth = preg_replace("/&?noredir$/","",$sWithoutSauth);
		$sWithoutSauth = preg_replace("/\?$/","",$sWithoutSauth);
		$this->sPassportRedirect="http://".$_SERVER["HTTP_HOST"].$sWithoutSauth;
		//Если домен не .meta.ua - необходимо добавить параметр во избежание циклических редиректов
		if($this->bNoRedir&&empty($_COOKIE)){
			$aParseRedirect=parse_url($this->sPassportRedirect);
			$sAddonStr="";
			if(isset($aParseRedirect["fragment"])&&!empty($aParseRedirect["fragment"])){
				$this->sPassportRedirect=preg_replace("/\#.*$/i","",$this->sPassportRedirect);
				$sAddonStr="#".$aParseRedirect["fragment"];
			}
			$this->sPassportRedirect.=((isset($aParseRedirect["query"])&&!empty($aParseRedirect["query"]))?"&":"?")."noredir".$sAddonStr;
		}
		// устанавливает вечную куку для дальнейшего определения установлены ли у пользователя куки или нет на инородных доменах
		if($this->bNoRedir){
				setcookie("scook","1",time()+3600*24*365,"/",$this->sCookieDomain);
		}
		if (isset($_GET['sauth'])) {
		//Первым делом проверяем нет ли аутентификационной строки в GET-запросе
			if(trim($_GET['sauth'])!=""){
			//Если строка пустая - то это дефолтный юзер, ака гость
				$this->_auth($_GET['sauth']);
			}
			// Если у пользователя установлена гостевая кука на домен верхнего уровня (например на .sport.ua а он логинится на .football.sport.ua нужно почистить куки)
			if(isset($_COOKIE[$sPassportCookieName])){
				$this->_dropCookie();
				header("Location: http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
				exit();
			}
			//Записываем пользовательскую куку и редиректим на путь без sauth
			setcookie($sPassportCookieName, $this->_encrypt(serialize(array($this->iUserId,	$this->sUserPassHash,$this->sUserName,$this->sUserSex,$this->sUserBirth))."//".time()),0,"/",$this->sCookieDomain);

			if ((isset($_COOKIE['scook']) && $_COOKIE['scook']=='1')||($this->bNoRedir&&!isset($_GET["noredir"]))) {
				header("Location: ".$this->sPassportRedirect);
				exit();
			}
		} elseif (isset($_COOKIE[$sPassportCookieName])) {  
		//Если авторизационной строки нет в запросе GET проверяем ее в куке
			$this->_auth($_COOKIE[$sPassportCookieName]);
		} elseif ((isset($_COOKIE['scook']) && $_COOKIE['scook']=='1')||($this->bNoRedir&&!isset($_GET["noredir"])&&!empty($_COOKIE))) {
		//Иначе - редиректим на пасспорт
			  $this->_passportAuth();
		} 
		parent::__destruct();
		return;
	}

	/**#@+
		* ### Public Methods ###
		*
		* @access public
		*/
	/**
		* Функция разлогинивания
		*
		*
		* @access public
		* @param string $sRedirect урл, куда вернуть после логаута
		*
		*/	
	function logout($sRedirect=false){

		$sPassportLogout=META_PASSPORT_URL."logout.php?from=".$this->sFrom;

		if($sRedirect&&!isset($_GET["nometaredirect"])){
			//$this->rTd = mcrypt_module_open ($this->sEncMethod, '',$this->sEncMode, '');
			//$iIvSize=mcrypt_enc_get_iv_size ($this->rTd);
			//$this->sIv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($this->rTd), MCRYPT_RAND);
			$sPassportLogout.="&redirect=".urlencode($this->_encrypt($sRedirect));
			//mcrypt_module_close ($this->rTd);
		}

		$this->_dropCookie();

		header("Location: $sPassportLogout");

		exit();
	}

	/**
		* Функция получения параметров пользователя из аутентификационной строки
		*
		*
		* @access private
		* @param string $sAuth аутентификационная строка
		*
		*/	
	function _auth($sAuth){
		$sPlainText=$this->_decrypt($sAuth);
		if(preg_match("/^(.+?)\/\/([0-9]+)$/i",$sPlainText,$aMatch)){
			//Проверка времени жизни сесии
			if((time()-$aMatch[2] >= $this->iCookieTimeLimit)){
				// Если кука уходит по таймауту , не зачем проверять noredir
				unset($_GET["noredir"]);
				$this->_passportAuth();
			}else{
				//Получение и запись данных пользователя
				$aUser = @unserialize($aMatch[1]);
				if(is_array($aUser)){
					$this->iUserId=$aUser[0];
					$this->sUserPassHash=$aUser[1];
					$this->sUserName=$aUser[2];
					$this->sUserSex=$aUser[3];
					$this->sUserBirth=$aUser[4];
				}
			}
		}
		return;
	}
	
	/**
		* Функция редиректа пользователя на паспорт для получения авторизационной
		* строки
		*
		* @access private
		* @param string $sAuth аутентификационная строка
		*
		*/	
	function _passportAuth(){

		$this->_dropCookie();
		//Проверяем нужно ли редиректить на паспорт
		if((isset($_COOKIE['scook']) && $_COOKIE['scook']=='1')||($this->bNoRedir&&!isset($_GET["noredir"]))){
			header("Location: ".META_PASSPORT_URL."?from=".$this->sFrom."&force=1&redirect=".urlencode(preg_replace("/(?:\?|\&)noredir/i","",$this->sPassportRedirect)));
			exit;
		}
	}


	/**
		* Функция, которая сносит авторизационные куки
		*
		* @access private
		* @param null
		*
		*/	
	function _dropCookie(){
		//Сносим старую куку, при этом стараемся снести ее и на поддоменах тоже, во избежание пересечения кук
		$aTok=explode(".",$this->sCookieDomain);

		array_pop($aTok);
		array_pop($aTok);
		$iShift=0;
                setcookie($this->sPassportCookieName,"",0,"/");
		if(empty($aTok))
			setcookie($this->sPassportCookieName, "",time() - 36000,"/");
		foreach($aTok as $sTok){
			$iShift+=strlen($sTok)+1;
			setcookie($this->sPassportCookieName, "",0,"/",".".substr($this->sCookieDomain,$iShift));
		}

	}

	/**#@-*/

}

?>
