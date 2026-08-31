<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Ёксепшен дл€ класса работы с мускулем.
 *
 */
class SU_MySQLException extends Exception
{	
    public function __toString() 
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}
?>