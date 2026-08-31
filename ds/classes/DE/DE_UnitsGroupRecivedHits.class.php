<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_UnitsGroupRecivedHits 
{
	private $recivedHits = array();	
	
	public function addHit(DE_UnitsGroupHitRecived $recivedHit)
	{
		$this->recivedHits[] = $recivedHit;			
	}
				
}

?>

