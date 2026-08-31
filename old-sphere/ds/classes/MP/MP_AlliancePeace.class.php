<?php $CVS_Version = '$Revision$ ; $Date$';

class MP_AlliancePeace extends MP_CombBase
{				
	public function __construct($combData)
	{
		parent::__construct($combData);
	}	
	
	public function patch()
	{
		$al_list = DS_PeaceAlliance::getAlliancePeaceList();
		//удаляем устаревшие перемирия и возвращаем бабло
    foreach ($al_list as $al)
    {  
   	   if (($al['status']>0) && $al['psec'] && (time() > $al['psec']))
		   {
	        $alPeace = new UA_AlliancePeace();  
	   	    $leader1 = DS_Alliance::GetAllianceLeader($al['id_alliance_1']);
		   	  $leader2 = DS_Alliance::GetAllianceLeader($al['id_alliance_2']);

			   	$comb1 = DE_CombAttributes::CreateFromCombID(intval($al['id_comb_1']));
          $alPeace->changeRes($comb1, $al, 1, $leader1['id_userinstance'],  +1);
 		      $comb2 = DE_CombAttributes::CreateFromCombID(intval($al['id_comb_2']));
          $alPeace->changeRes($comb2, $al, 2, $leader2['id_userinstance'],  +1);
			   	
          DS_PeaceAlliance::delAlliancePeace($al['id_peace']);  //удалим нафиг из списка
			 }
    } 	 
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
		
}

?>