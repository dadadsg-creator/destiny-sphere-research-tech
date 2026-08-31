<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 *  ласс дл€ пересмчета армии в стазис ”—“ј–≈Ћ!!! ЅќЋ≈≈ Ќ≈ »—ѕќЋ№«”≈“№—я!!!!
 */
class GL_ArmyPower extends GameBase{
			
	function GL_ArmyPower() {
		$this->GameBase();				
	}//END OF CONSTRUCTOR
	
	
	/**
	* @return јрми€ пеерсчитанна€ в стазис
	* @param ID армии $armyId
	* @desc ѕеерсчитывает армию в стазис
	*/ 
	function countArmy($armyID) 
	{				
		assert (is_int($armyID) && $armyID > 0 );		
		return DE_Army::CreateFromID($armyID)->getArmyPower();
	}//ENF OF METHOD
	
	
	
	/**
	* @return ѕачка юнитов пеерсчитанна€
	* @param ID юнита $unitID
	* @param колличество юнитов $amount
	* @desc ѕеерсчет юнита одноготипа некого колва в юниты.
	*/
	function countUnits ( $unitID, $amount ) {		
		
		settype ( $unitID, 'integer' );
		assert ( $unitID > 0 );				
		assert ( !empty($amount) );

		$ap = DS_Army::getUnitPower($unitID, $amount);
		settype ($ap, 'integer');
		
		if ( $ap < 0 ){
			ds_error ( 'ERROR! GL_ArmyPower->countUnits()::Uncorrect result value for army '.$armyId );
		};
		
		return $ap;
	}//ENF OF METHOD
	
	function invariant ($file, $line){
				
		assert ( !empty ( $file ) );
		assert ( !empty ( $line ) );
		assert ( $line > 0 );			
				
	}//END OF METHOD 
	
}//END OF CLASS

?>