<?php $CVS_Version = '$Revision$ ; $Date$';

class GB_Date extends GB_DataType
{
    static protected $className = 'GB_Date';

    protected function _validateValue(&$value)
    {   
        parent::_validateValue($value);                     

        if(strpos($value, 'now()') !== FALSE)
        {
            $value = time();
        }       

        if(gettype($value) == 'string')
        {

            if(strpos($value, '-') && strpos($value, "."))
            {
                $value = substr($value, 0, strpos($value, "."));
            }else{
                $value = str_replace('.', '-', $value);
            }

            $value = strtotime($value);
        }
            
        assert((is_double($value) || is_int($value)));
    }

    public function getPgFormat($delta = false)
    {   
        if($delta)
        {
            return strftime('%Y-%m-%d %H:%M:%S', $this->value + ($delta * 60 * 60));
        } else {
            return strftime('%Y-%m-%d %H:%M:%S', $this->value);
        }
    }

    public function getFormatedDate($delta = false)
    {
        if ($delta)
        {
            return strftime('%d.%m', $this->value + ($delta * 60 * 60));
        }else{
            return strftime('%d.%m', $this->value);
        }
        
    }

    public function getFormatedTime($delta = false)
    {
        if ($delta)
        {
            return strftime('%H:%M', $this->value + ($delta * 60 * 60));
        }else{
            return strftime('%H:%M', $this->value);
        }       
    }

    public function getFormatedFull($delta = false)
    {
        if ($delta)
        {
            return strftime('%d.%m %H:%M', $this->value + ($delta * 60 * 60));
        }else{
            return strftime('%d.%m %H:%M', $this->value);
        }       
    }
    
    public function getTimeStamp($delta = false)
    {
        if ($delta)
        {
            return (int)($this->value + ($delta * 60 * 60));
        }else{
            return (int)($this->value);
        }       
    }
    
    public function getSystemMessageFormat()
    {
        return "[:".$this->value.":]";
    }
    
    public function getGreenwichFormatedFull($delta = false)
    {
        if ($delta)
        {
            return strftime('%d.%m %H:%M', $this->value + ($delta * 60 * 60));
        }else{
            return strftime('%d.%m %H:%M', $this->value);
        }       
    }
}

?>