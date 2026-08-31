<?php
/**
 * Клас формирует протокол для пераедчи вида соты
 *
 */
class TL_CombView
{
	static public function Form(TI_CombView $in)
	{
		$in->validate(); 
		
		$out = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<refresh location=\"".$in->cellTypeName."\" id_comb=\"".$in->combMark."\" time=\"".$in->turnTime."\" gate=\"".$in->gateStatus."\">";
		
		if($in->cells)
		{
			$out .= "<sota>".$in->cells."</sota>"; 
		}
		
		if($in->buildings)
		{
			$out .= "<build>".$in->buildings."</build>";
		}
		
		$out .= "</refresh>";
		
		return $out;
	}
}

?>