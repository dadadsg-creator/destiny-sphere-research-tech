<?php $CVS_Version = '$Revision$ ; $Date$';
class SU_HTTPGetContent
{
	var $method; // HEAD, GET, POST
	var $url;
	var $headercollection = array();
	var $postVars = array();
	var $getVars = array();
	var $tracecookies = array();
	var $contentLength = 0;
	var $topheader;
	var $headers;
	var $body;
	var $host;
	var $responseHeader;
	var $responseBody;
	var $cookies;
	var $response;
	var $resetKey;
	var $resetValue;
	var $port;

	function GetContent()
	{
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $method
	 *
	 * POST GET HEAD
	 */
	function setMethod($method)
	{
		assert(!empty($method));

		$this->method = $method;
	}

	function setUrl($url)
	{
		$this->url = $url;
	}

	function _formQueryFromArray($type = 'GET', $full = true)
	{
		$return = '';
		$array = array();
		switch ($type)
		{
			case 'GET':
				foreach ($this->getVars as $key => $value)
				{
					$array[] = $key."=".urlencode($value);
				}
				break;
			case 'POST':
				foreach ($this->postVars as $key => $value)
				{
					$array[] = $key."=".urlencode($value);
				}
				break;

			default:
				assert(false);
				break;
		}

		if (count($array) >0 )
		{
			$return = implode('&', $array);
		}



		if ($full == true)
		{
			return empty($return) ? $return : '?'.$return;
		} else {
			return $return;
		}
	}

	function _formArrayFromQuery($type = 'GET', $str)
	{

		if (empty($str))
		{
			return;
		}


		$array = array();
		$array = explode('&', $str);

		if (count($array) <= 0)
		{
			return;
		}

		foreach ($array as $key => $value)
		{
			$a = explode('=', $value);
			switch ($type)
			{
				case 'GET':
					$this->getVars[$a[0]] = @$a[1];
					break;
				case 'POST':
					$this->postVars[$a[0]] = $a[1];
					break;

				default:
					assert(false);
					break;
			}
		}
	}

	function _baseHeaders()
	{
		$this->setHeader('User-Agent', 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)');
		$this->setHeader('Accept', "image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, */*");
		$this->setHeader('Accept-Language', 'ru');
		$this->setHeader('Keep-Alive', '300');
		$this->setHeader('Proxy-Connection', 'keep-alive');
		$this->setHeader('Content-Type', 'application/x-www-form-urlencoded');
	}

	function _initHeaders()
	{
		$this->_baseHeaders();
		$array = parse_url($this->url);

		if (!key_exists('path', $array) || empty($array['path']))
		{
			$array['path'] = '/';
		}

		if (!key_exists('scheme',$array))
		{
			$array['scheme'] = 'http';
		}

		if (!key_exists('query', $array) || empty($array['query']))
		{
			$array['query'] = '';
		}

		$this->_formArrayFromQuery('GET', $array['query']);
		if ($this->method == 'GET')
		{
			$str = $this->_formQueryFromArray('POST', false);
			$this->_formArrayFromQuery('GET', $str);
		}




		$this->topheader = $this->method.' '.$array['path'].$this->_formQueryFromArray('GET',true).' '.strtoupper($array['scheme']).'/1.0'."\n";
		$this->topheader .= "Host: ".$array['host']."\n";
		$this->host = $array['host'];
		if (key_exists('port', $array))
		{
			$this->port = $array['port'];
		} else {
			$this->port = false;
		}

		unset($array);




		$body = false;
		if ($this->method == 'POST')
		{
			$body = $this->_formQueryFromArray('POST', false);
			//$this->contentLength = strlen($this->body);
			$this->contentLength = strlen($body);
			$this->setHeader('Content-Length', $this->contentLength);
		}

		if ($this->tracecookies == true)
		{
			if (isset($_SESSION['getContent_cookies']) && count($_SESSION['getContent_cookies']) > 0)
			{
				$this->cookies = $_SESSION['getContent_cookies'];
			}
		}
		if (count($this->cookies) > 0)
		{
			$cookies = "";
			foreach ($this->cookies as $key => $value)
			{
				$cookies .= " ".$key."=".$value.";";
			}

			$this->setHeader("Cookie", $cookies);
		}


		$headers = '';
		foreach ($this->headercollection as $key=>$value)
		{
			$headers .= $key.': '.$value."\n";

		}

		/* Формируем саму шапку запроса*/
		$this->headers = $this->topheader;
		$this->headers .= $headers."\n";

		if($body !== false)
		{
			$this->headers .= $body."\n";
		}

	}

	function setHeader($name,$value)
	{
		//assert( (!empty($name) && !empty($value)) ||  (!empty($name) && is_bool($value)));
		$this->headercollection[ $name ] = $value;
	}

	function setPostVar($name, $value)
	{
		if (empty($name)) return;
		$this->postVars[$name] = $value;
	}

	function setGetVar($name, $value)
	{
		if (empty($name)) return;
		$this->getVars[$name] = $value;
	}

	function showHeaders()
	{
		$this->_initHeaders();
		echo "<xmp>";
		echo $this->headers;
		echo "</xmp>";
	}

	function GetHeaders()
	{
		return $this->headers;
	}
	
	function showResponse($convert_from = false)
	{
		//$this->_initHeaders();
		echo "<xmp>";
		if ($convert_from !== false)
		{
			echo $str = iconv($convert_from, 'cp1251', $this->response);
		} else {
			echo $this->response;
		}
		echo "</xmp>";
	}

	function showResponseHeaders()
	{
		//$this->_initHeaders();
		echo "<xmp>";
		var_dump($this->responseHeader);
		echo "</xmp>";
	}

	function Start()
	{
		$this->_initHeaders();
		$this->_getContent();
		$this->getVars = array();
		$this->postVars = array();
		$this->headercollection = array();
	}

	function GetResponseHeaders()
	{
		//assert(!empty($this->responseHeader));
		return $this->responseHeader;
	}
	function GetResponseContent()
	{
		//assert(!empty($this->responseBody));
		return $this->responseBody;
	}

	function TraceCookies($boolean = true)
	{
		assert(is_bool($boolean));
		if ($boolean == false)
		{
			$this->tracecookies = false;
		} else {
			$this->tracecookies = true;
		}
	}

	function setReferer($value)
	{
		//assert($value);

		$this->setHeader('Referer',$value);
	}

	function _getContent()
	{
		$Result = "";
		//  Open the connection to the host
		if (!$this->port) $this->port = 80;

    $socket = fsockopen($this->host, $this->port, $errno, $errstr);
    SU_DebugTools::LogAll("_getContent port=".var_export($this->port, true), 'daniil.log');

    SU_DebugTools::LogAll("_getContent socket=".var_export($socket, true), 'daniil.log');
    SU_DebugTools::LogAll("headers=".var_export($this->headers, true), 'daniil.log');

    if (!$socket) {
			$Result["errno"] = $errno;
			$Result["errstr"] = $errstr;
      SU_DebugTools::LogAll("fsock error: errno=$errno, errstr=$errstr", 'daniil.log');
			return $Result;
		}
		
		fputs($socket, $this->headers);
		while (!feof($socket))
		{
			$Result .= fgets($socket, 128);
		}
		$pos = strpos($Result, "\r\n\r\n", 0);

    SU_DebugTools::LogAll("Result=$Result", 'daniil.log');
    
    fclose($socket);
		$this->responseHeader = trim(substr($Result, 0, $pos) );
		$this->responseBody = trim(substr($Result, $pos+4, strlen($Result)));
		$this->response = $Result;

		$this->_parseCookies();
		$this->reset();
	}

	function _parseCookies()
	{
		$value = "/Set-Cookie: (.*)=([a-zA-Z0-9]+)[;|[[:space:]]]?/im";

		if (preg_match_all($value, $this->responseHeader, $regs, PREG_SET_ORDER))
		{
			foreach ($regs as $v)
			{
				$this->cookies[$v[1]] = $v[2];
			}
			if ($this->tracecookies == true)
			{
				$_SESSION['getContent_cookies'] = $this->cookies;
			}

		}
	}

	function setReset($value,$key)
	{
		//assert(!empty($key));
		//assert(!empty($value));
		$this->resetKey = $key;
		$this->resetValue = $value;
	}
	function reset()
	{
		if (isset($this->resetValue) && eregi($this->resetValue, $this->response) )
		{
			unset($_SESSION[$this->resetKey]);
		}

	}

}

?>