<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Просмотр комплекса
 *
 */
class TI_ComplexView
{
	public $complexNumber;
	
	public $ringNumber;
	
	public $ringName;
	
	public $openGate;
	
	public $combs = array();
	
	public $rings = array();
	
	public function __construct()
	{
							
	}
	
	public function validate()
	{
		assert(is_int($this->complexNumber) && $this->complexNumber > 0);
		assert(is_int($this->ringNumber) && $this->ringNumber > 0);
		assert(!empty($this->ringName));
		assert(is_array($this->rings));
		assert(count($this->rings) > 0);
		assert(is_array($this->combs));
		assert(count($this->combs) > 0);
	}
}

?>