<?php
/**
 * Класс фактически затыка так как нет времени сделать нормлаьную объектную модель.
 * Извлекает сведения о здание по его ID 
 *
 */
class ME_CombViewGetAtachedBuildingData 
{
	static public function Get($attachedBuildingID)
	{
		assert(is_int($attachedBuildingID) && $attachedBuildingID > 0);
		
		$data = DS_Building::GetAttachedBuildingByID($attachedBuildingID);
		
		$out['COMB_ID'] = (int)$data['id_comb'];
		assert($out['COMB_ID'] > 0);
		
		$out['CELL_ID'] = (int)$data['id_cell'];
		assert($out['CELL_ID'] > 0);
		
		$out['CELL_TYPE_ID'] = (int)$data['id_celltype'];
		assert($out['CELL_ID'] > 0);
		
		return $out;
	}
}

?>