<?
class File
{
	var $fp = false;
	var $url;
	var $stat;
	var $filesize;
	var $content;
	var $array;
	var $csv;
	var $mode;
	var $parseFunction = false;
	var $ftToWrite;
	var $write = false;

	function File($url, $mode)
	{
		assert(!empty($url));
		$this->url = $url;

		if ($mode)
		{
			switch ($mode) {
				case 'r':
				$this->mode = "r";
				break;
				case 'w':
				$this->mode = "a+";
				break;
				case 'a':
				$this->mode = "a+";
				break;
				default:
				$this->mode = "a+";
				break;
			}
		}

		if (file_exists($this->url))
		{

			$this->_getFileSize($url);
			$this->_getStat($url);
		} else {
			$this->mode = "a+";
		}
	}

	function lock()
	{
		$this->_openFile();

		flock($this->fp, LOCK_EX);
	}

	function unlock()
	{
		$this->_openFile();

		flock($this->fp, LOCK_UN);
	}

	function deleteFile()
	{
		$this->closeFile();
		unlink($this->url);
	}


	function writeIn($str)
	{
		$this->_openFile();

		if (is_writable($this->url))
		{
			return fwrite($this->fp, $str);
		}

	}

	function _openFile()
	{
		if (!$this->fp)
		{
			if ($handle = fopen($this->url,$this->mode) )
			{
				$this->fp = $handle;
				$this->_getFileSize();
			} else {
				$this->fp = false;
			}
		}
	}

	function closeFile()
	{

		if (is_resource($this->fp))
		{
			$this->unlock();

			fclose($this->fp);
			$this->fp = false;
		}


	}

	function getFileSize()
	{
		$this->_getFileSize();
		return $this->filesize;
	}

	function getStat()
	{
		return $this->_getStat($url);
	}

	function _getStat($url)
	{
		$this->stat = stat($this->url);
	}

	function _getFileSize()
	{
		$this->filesize = filesize($this->url);
	}

	function getInString()
	{
		$this->_openFile();
		if (!$this->content)
		{
			if ($this->filesize > 0)
			{
				$this->content = fread($this->fp, $this->filesize);
			} else {
				$this->content = '';
			}

		}

		$this->closeFile();

		if (is_array($this->content))
		{
			return implode('',$this->content);
		} else {
			return $this->content;
		}


	}

	function setParseFunction($functionName)
	{
		$this->parseFunction = $functionName;
	}

	function setFpToWrite($fp)
	{
		$this->ftToWrite = $fp;
	}

	function getFp()
	{
		$this->_openFile();
		return $this->fp;
	}

	function setAutoWrite()
	{
		$this->write = true;
	}

	function parseEachRows()
	{
		$this->_openFile();
		$i=1;
		while (!feof($this->fp))
		{
			$buffer = fgets($this->fp, 8192);
			if ($this->parseFunction)
			{
				$buffer = call_user_func($this->parseFunction, $buffer);
			}


			if ($this->write)
			{
				if ($this->ftToWrite)
				{
					fwrite($this->ftToWrite, $buffer);
				}
			}
			if ($i == 1000)
			{
				echo ' ';
				$i=1;
			}
		}
	}

	function getInArray()
	{
		$this->_openFile();
		if (!$this->array)
		{
			$this->array = file($this->url);
		}
		$this->closeFile();
		return $this->array;
	}

	function getInCSV()
	{

		$this->_openFile();

		if (!$this->csv)
		{

			while (($data = fgetcsv($this->fp, 50000, ";")) !== FALSE)
			{

				$this->csv[] = $data;
			}
		}
		$this->closeFile();
		return $this->csv;

	}
}

?>