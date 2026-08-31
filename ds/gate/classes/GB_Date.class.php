<?php

class GB_Date
{
	var $value;

	function GB_Date($value)
	{
		$this->value = $value;
		
		if(ereg('now()', $value))
		{
			$this->value = time();
		}

		if(gettype($value) == 'string')
		{
			if(strpos($value, '-') && strpos($value, "."))
			{
				$value = substr($value, 0, strpos($value, "."));
			}else{
				$value = str_replace('.', '-', $value);
			}

			$this->value = strtotime($value);
		}
		
		assert((is_double($this->value) || is_int($this->value)) && $this->value > 0);
	}

	function getPgFormat()
	{
		return strftime('%Y-%m-%d %H:%M:%S', $this->value);
	}

	function getFormatedDate($delta = false)
	{
		if ($delta)
		{
			return strftime('%d.%m', $this->value + ($delta * 60 * 60));
		}else{
			return strftime('%d.%m', $this->value);
		}

	}

	function getFormatedTime($delta = false)
	{
		if ($delta)
		{
			return strftime('%H:%M', $this->value + ($delta * 60 * 60));
		}else{
			return strftime('%H:%M', $this->value);
		}
	}

	function getFormatedFull($delta = false)
	{
		if ($delta)
		{
			return strftime('%d.%m %H:%M', $this->value + ($delta * 60 * 60));
		}else{
			return strftime('%d.%m %H:%M', $this->value);
		}
	}

	function getSystemMessageFormat()
	{
		return "[:".$this->value.":]";
	}
}

?>