<?php $CVS_Version = '$Revision$ ; $Date$';

class ArtTradeLogger {
		
	public $common = '';
	public $logdate = '';
	public $type = '';	
	public $id_artifactinstance = 0;
	
	public $id_saler = 0;
	public $id_customer = 0;
	public $id_salercomb = 0;
	public $id_customercomb = 0;
	
	public $activeMode = false;
	
	public $msg = '';
	
	function ArtTradeLogger ( $type, $common ){		
		assert ( !empty ( $type ) );
		$this->type = $type;
		
		assert ( !empty ( $common ) );
		assert ( is_a ( $common, 'DS_Common' ) );		
		$this->common = $common;
		
		$this->logdate = date('Y-m-d H:i:s');		
	}//END OF CONSTRACTOR
	
	function setIdSaler ( $id ) {
		settype ( $id, 'integer' );
		assert ( !empty ( $id ) );
		$this->id_saler = $id;
	}//END OF METHOD
	
	function setIdCustomer ( $id ) {
		settype ( $id, 'integer' );
		assert ( !empty ( $id ) );
		$this->id_customer = $id;
	}//END OF METHOD
	
	function setIdSalerComb ( $id ) {
		settype ( $id, 'integer' );
		assert ( !empty ( $id ) );
		$this->id_salercomb = $id;
	}//END OF METHOD
	
	/**
	* @return void
	* @param unknown $id
	* @desc Вносити описание здесь...
	*/
	function setIdCustomerComb ( $id ) {
		settype ( $id, 'integer' );
		assert ( !empty ( $id ) );
		$this->id_customercomb = $id;
	}//END OF METHOD
	
	function setIdArtifactInstance ( $id ) {
		settype ( $id, 'integer' );
		assert ( !empty ( $id ) );
		$this->id_artifactinstance = $id;
	}//END OF METHOD
	
	function setActiveMode ( $val ){
		settype ( $val, 'boolean' );		
		$this->activeMode = $val;
	}//END OF METHOD
	
	function msg ( $msg, $comment = '' ){
		$this->msg .= "\n------------- $comment ---------------------\n".
				$msg.
			   "\n---------------------------------------------------------------\n";		
	}//END OF METHOD 
	
	function log (){
		$sql = "INSERT INTO ds_tradeartloger(					
					logdate,
					id_artefactinstance,
					id_saler,
					id_salercomb,
					id_customer,
					id_customercomb,
					type,
					msg					
				) VALUES (
					'".$this->logdate."',
					".$this->id_artifactinstance.",
					".$this->id_saler.",
					".$this->id_salercomb.",
					".$this->id_customer.",
					".$this->id_customercomb.",
					'".$this->type."',
					'".addslashes( $this->msg )."'
				)";

		if ( $this->activeMode ) {			
			$res = $this->common->executeQuery ( $sql );
			assert ( empty ( $this->common->errormsg ) );
			assert ( !empty ( $res ) );
		}
		
	}//END OF METHOD log()
	
	
}


?>