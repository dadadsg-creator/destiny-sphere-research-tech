<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс собирающий точки построения трассы расчета апкипа
 *
 */
class DE_LiveUpkeepPoints extends BS_Base
{
	/**
	 * Атрибут реализации синглетона
	 *
	 * @var unknown_type
	 */
	static private $instance;
	
	/**
	 * Точки трассы
	 *
	 * @var array
	 */
	private $points = array();
	
	/**
	 * Вспомогательный массив с индексами точек трассы
	 *
	 * @var array
	 */
	private $pointsKeys = array();
	
	public function __construct()
	{
		parent::__construct();
		
		$this->_invariant('construct');
	}

	static public function GetInstance()
	{
		if(!(self::$instance instanceof DE_LiveUpkeepPoints))
		{
			self::$instance = new DE_LiveUpkeepPoints();
		}
		
		return self::$instance;
	}
	
	public function addPoint(DE_Comb $comb, GB_Date $date)
	{
		$this->_invariant();

		$liveUpkeepValue = GL_LiveUpkeep::CountComb($comb);
		
		// Такое извращение нужно на случай, если у нас есть ивенты 
		// которые влияют на значение апкипа на соте и которые не были обработаны
		// по внешнему рефрешу, который был перед полным 

		if(key_exists($comb->getID(), $this->pointsKeys))
		{
			sort($this->pointsKeys[$comb->getID()]);			
		}

		if(key_exists($comb->getID(), $this->pointsKeys) && (count($this->pointsKeys[$comb->getID()]) > 0) && ($date->value <= $this->pointsKeys[$comb->getID()][0]))
		{

			$key = $this->pointsKeys[$comb->getID()][0];		
			$this->points[$comb->getID()][$key]['UPKEEP_VALUE'] = $liveUpkeepValue;
		} else {
			$key = $date->value;
	
			$this->pointsKeys[$comb->getID()][] = $key;

			$this->points[$comb->getID()][$key] = array(
				'DATE' => $date,
				'UPKEEP_VALUE' => $liveUpkeepValue				
			);
		}
		
		$this->pointsKeys[$comb->getID()] = array_unique($this->pointsKeys[$comb->getID()]);
			
		assert(count($this->points[$comb->getID()]) == count($this->pointsKeys[$comb->getID()]));
	}
	
	public function flushPointsForComb(DE_Comb $comb)
	{
		$this->_invariant();
		
		$out = array();
		
		if(key_exists($comb->getID(), $this->points))
		{
			$out = $this->points[$comb->getID()];	
			
			unset($this->points[$comb->getID()]);
			unset($this->pointsKeys[$comb->getID()]);			
		}
		
		return $out;
	}
	
	protected function _invariant()	
	{		
		assert((self::$instance instanceof DE_LiveUpkeepPoints) || is_null(self::$instance));
		assert(is_array($this->points));
	}
	
	function __destruct()
	{
		parent::__destruct();
	}		
}
?>