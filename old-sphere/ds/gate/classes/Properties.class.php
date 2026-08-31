<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2008/03/25 08:06:43 $';

class Properties
{
	var $properties;
	var $path;
	var $isModify = false;

	function Properties($file)
	{
		$this->path = $_SERVER['DOCUMENT_ROOT'].'/'.constant('INI_STORE_PATH').'/'.$file.'.log';

		$this->_load($file);
	}

	function _load($file)
	{
		assert(!empty($file));

		if (file_exists($this->path))
		{
			$this->properties = parse_ini_file($this->path, true);
		} else {
			$this->properties = array();
		}

	}


	function _save()
	{
		$this->write_ini_file($this->path, $this->properties);
	}

	function have($section, $element = null)
	{
		assert(!empty($section));

		if (!is_null($element))
		{
			if (key_exists($section, $this->properties) && key_exists((string)$element, $this->properties[$section]))
			{
				return true;
			}
			return false;
		}

		if (key_exists($section, $this->properties))
		{
			return true;
		}
		return false;
	}

	function get($section, $element = null)
	{
		assert(!empty($section));

		if (strtoupper((string)$section) == 'SETTINGS')
		{
			return false;
		}

		if (!key_exists($section, $this->properties))
		{
			return;
		}

		if (is_null($element))
		{
			return $this->properties[$section];
		} else {
			if (key_exists($element, $this->properties[$section]))
			{
				return $this->properties[$section][$element];
			} else {

				return;
			}

		}

		return false;
	}

	function getAllSections()
	{
		$array = array_keys($this->properties);
		$array = array_flip ($array);
		unset($array['SETTINGS']);
		$array = array_flip ($array);
		return $array;
	}

	function set($section, $element, $value)
	{
		assert(!empty($section));
		assert(!empty($element));
		assert(!empty($value));

		if (strtoupper($section) == 'SETTINGS')
		{
			return false;
		}
		$this->properties[(string)$section][(string)$element] = $value;

		$this->isModify = true;
		$this->properties['SETTINGS']['lastModify'] = time();

		$this->_save();
	}

	function delete($section, $element = null)
	{
		assert(!empty($section));

		if (!key_exists($section, $this->properties))
		{
			return true;
		}

		if (is_null($element) || empty($element))
		{
			unset($this->properties[$section]);
		} else {
			unset($this->properties[$section][$element]);
			if (sizeof($this->properties[$section]) <= 0)
			{
				unset($this->properties[$section]);
			}
		}

		$this->isModify = true;
		$this->properties['SETTINGS']['lastModify'] = time();

		$this->_save();

		return true;
	}

	function write_ini_file($path, $assoc_array)
	{
		$content = '';
		$sections = '';

		if (file_exists($path))
		{
			assert(is_writable($path));
		}



		foreach ($assoc_array as $key => $item)
		{
			if (is_array($item))
			{
				$sections .= "\n[{$key}]\n";
				foreach ($item as $key2 => $item2)
				{
					assert(!is_array($item2));

					if (is_numeric($item2) || is_bool($item2))
					$sections .= "{$key2} = {$item2}\n";
					else
					$sections .= "{$key2} = \"{$item2}\"\n";
				}
			}
			else
			{
				if(is_numeric($item) || is_bool($item))
				$content .= "{$key} = {$item}\n";
				else
				$content .= "{$key} = \"{$item}\"\n";
			}
		}

		$content .= $sections;

		if (!$handle = fopen($path, 'w'))
		{
			return false;
		}

		if (!fwrite($handle, $content))
		{
			return false;
		}

		fclose($handle);
		return true;
	}
}




?>