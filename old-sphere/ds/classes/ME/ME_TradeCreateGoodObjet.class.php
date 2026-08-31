<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_TradeCreateGoodObjet
{
	static public function Exec($tradeCategoryName, $articleID)
	{
		assert(!empty($tradeCategoryName));
		assert(is_int($articleID) && $articleID > 0);
						
		$out = null;
		
		switch ($tradeCategoryName)
		{
			case 'RESOURCE':
				$out = DE_Resource::CreateFromID($articleID);
				break;
			case 'ARTEFACT':
				$out = DE_Artefact::CreateFromArtefactID($articleID);
				break;
			case 'UNIT':
				$out = DE_Unit::CreateFromID($articleID);
				break;
			case 'MONEY':
				$out = DE_Unit::CreateFromID($articleID);
				break;
			default:
				ds_error("Unknown category name = ".$tradeCategoryName);
		}
		
		return $out;
	}
}



?>