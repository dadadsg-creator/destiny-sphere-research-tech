<?php

class GK_HTTPRequest
{
	private $options = array(
		'method' => 'GET', 
		'header' => '', 
		'user_agent' => '', 
		'content' => '', 
		'proxy' => '', 
		'request_fulluri' => '', 
		'max_redirects' => ''
	);
	
	private $data = array();
	
	public function get($url)
	{
		$content = http_build_query($this->data);
		$this->options['content'] = $content;		
		
		$opts = array('http' => $this->options);
		
		$context  = stream_context_create($opts);		
		
		$result = file_get_contents($url, false, $context);
				
		return $result;
	}
	
	public function setOption($name, $value)
	{
		if(key_exists($name, $this->options))
		{
			$this->options[$name] = $value;
		}
	}
	
	public function addHeader($header)
	{
		$this->options['header'] .= "$header\r\n";
	}
	
	public function setVar($name, $value)
	{
		$this->data[$name] = $value;
	}
}

?>