<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 *  ласс сущности дол€ описание какого либо бонуса сост€ощиго из абсолютного значени€ и 
 * процентного
 */

class DE_Bonus extends BS_Base
{
	/**
	 * Enter description here...
	 *
	 * @var GB_Array
	 */
	private $relativeBonus;
	
	private $absoluteBonus;				
	
	private $prefix;
	
	public function __construct($prefix = '')
	{
		parent::__construct();
		$this->relativeBonus = new GB_Array('integer', 'float');
		$this->absoluteBonus = (float)0;
		
		$this->prefix = $prefix;
	}
		
	public function deltaAbsoluteBonus($value)
	{
		$this->_invariant();
		
		assert(is_float($value));
		
		$this->absoluteBonus = $this->absoluteBonus + $value;
	}
	
	public function deltaRelativeBonus($value)
	{
		$this->_invariant();		
		
		assert(is_float($value));

		$this->relativeBonus->addElement(null, $value);
	}

	public function getRelativeBonus()
	{
		$this->_invariant();
		return $this->relativeBonus;
	}
	
	public function getSummRegressivRelativeBonus()
	{
		$this->_invariant();
		
		$out = 0;
		
		$relativBonusArray = $this->getRelativeBonus();						
		$relativBonusArray->sortByValsInvers();
		$relativBonusArray->resetPointer();
		while($bonus = $relativBonusArray->getNextElement())
		{		
			$out += (1 - $out) * $bonus;	
		}	

		return $out;	
	}	
	
	public function getSummRelativeBonus()
	{
		$this->_invariant();
		
		$out = 0;
		
		return $this->relativeBonus->summVals();	
	}
	
	public function getAbsoluteBonus()
	{
		$this->_invariant();
		return $this->absoluteBonus;
	}
	
	private function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		assert($this->relativeBonus instanceof GB_Array);
		assert(is_float($this->absoluteBonus));
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
}//END OF CLASS

?>