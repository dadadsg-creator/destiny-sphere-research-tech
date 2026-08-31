<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Гейт анализатора.
 * Размер кольца
 *
 */
class LG_RingSize extends LG_Base
{	
	/**
	 * номер кольца
	 *
	 * @var int
	 */
	static private $ring;
	
	/**
	 * Получить размер кольца в мире
	 *
	 * @param string(3) $world
	 * @param int $ring
	 */
	static public function Get($world, $ring)
	{
		assert(is_int($ring) && $ring > 0 && $ring < 5);
		assert(ereg("[A-Z]{1,3}", $world));
		
		self::$world = $world;
		
		self::$ring = $ring;
		
		self::_getCommon();
		
		$size = self::_getRingSize();
		
		echo $size;
	}
	
	/**
	 * Выборка кол-ва комплексов на кольце
	 *
	 * @return int
	 */
	static private function _getRingSize()
	{
		$sql = "
			SELECT
				count(cc.id_combcomplex) as count
			FROM
				ds_combcomplex AS cc
				INNER JOIN ds_ring AS r USING (id_ring)
			WHERE
				r.ringnumber = ".self::$ring."
				AND
				r.id_world = ".self::$worldID."
			";
		$res = self::$common->getResultArray("log_gate_ring_size_world".self::$worldID.'_ring'.self::$ring, $sql, "", "", "GLOBAL_CACHE");
		assert(empty(self::$common->errormsg));
		
		return (int)$res[0]['count'];
	}
}

?>