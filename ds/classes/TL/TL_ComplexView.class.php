<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Формирование XML вида комплекса
 *
 */
class TL_ComplexView
{
	static public function Form(TI_ComplexView $in)
	{
		$in->validate();
		
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><combcomplex />";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();
		
		$root->set_attribute("number", $in->complexNumber);
		$root->set_attribute("ring", $in->ringNumber);
		$root->set_attribute("ringname", $in->ringName);
		$root->set_attribute("gate", $in->openGate);
		
		$skipAttributes = array('id_userinstance', 'id_alliance');
		
		foreach ($in->combs as $k => $comb)
		{
			$ch = $root->new_child("comb");
			foreach ($comb as $param => $value)
			{
				if(in_array($param, $skipAttributes))
				{
					continue;
				}
				$ch->set_attribute($param, $value);
			}
		}
		
		$ch = $root->new_child("rings");
		foreach($in->rings as $k => $ring) 
		{
			$ch2 = $ch->new_child("ring");
			$ch2->set_attribute("id", $ring['ringname']);
			$ch2->set_attribute("ln", urlencode($ring['localizedname']));
		}
		
		return $doc->dump_mem();
	}
}

?>