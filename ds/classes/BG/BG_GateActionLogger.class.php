<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2008/02/20 09:03:50 $';
/**
 * $Author: 40min $
 * $Date: 2008/02/20 09:03:50 $
 * $Revision: 1.6 $
 *
 *
 */
class BG_GateActionLogger
{

	private $actionName = '';
	private $options = array();
	private $ip = '';
	private $id;
	private $key;


	public function __construct($actionName, $options, $ip, $key)
	{
		$this->actionName = $actionName;
		$this->options = $options;
		$this->ip = $ip;
		$this->key = $key;

		$this->_insertFirstStep();
	}

	public function SetGoodEnd($answer)
	{
		return;
		$common = DS_Common::CreateForCurentDB();
		$common->setDbId(0);

		assert(!empty($answer));
		assert(empty($common->errormsg));

		$sql = "UPDATE ds_billing_gate_actions
				SET
					have_error = false,
					gate_ansver = '".$answer."'
				WHERE
					id_action = ".$this->id."";
		$res = $common->executeQuery($sql);
		assert(empty($common->errormsg));
	}

	public function SetBadEnd($errorText)
	{
		return;
		$common = DS_Common::CreateForCurentDB();
		$common->setDbId(0);

		assert(!empty($errorText));

		$sql = "UPDATE ds_billing_gate_actions
				SET error_text = '".$errorText."'
				WHERE
					id_action = ".$this->id."";
		$res = $common->executeQuery($sql);
		assert(empty($common->errormsg));
	}

	private function _insertFirstStep()
	{
		return;

		$common = DS_Common::CreateForCurentDB();
		$common->setDbId(0);

		$sql = "select nextval('ds_billing_gate_actions_id_action_seq') as id";
		$res = $common->getResultArray ( "", $sql, "", "", "" );
		assert(empty($common->errormsg) && count($res) == 1);

		$id = (int)$res[0]['id'];
		assert($id > 0);

		$this->id = $id;

		$sql = "INSERT INTO ds_billing_gate_actions
				(id_action, action_name, ip)
				VALUES
				($id, '".$this->actionName."', '".$this->ip."')";
		$res = $common->getResultArray ( "", $sql, "", "", "" );
		assert(empty($common->errormsg));

		foreach ($this->options as $optionName => $value)
		{
			$sql = "INSERT INTO ds_billing_gate_action_options
				(id_action, option_name, option_value, option_value2)
				VALUES
				($id, '".$optionName."', '', '".$value."')";
			$res = $common->getResultArray ( "", $sql, "", "", "" );

			assert(empty($common->errormsg));
		}

	}


}

?>