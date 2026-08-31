<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_GlobalAlliance extends DE_Base 
{	
	//private $id;
	
	private $name;
	
	//private $tag;
	
	private $shortName;
	
	private $password;
	
	private $leader;
	
	private $joinPayment;
	
	private $confirmation;
	
	private $creationDate;
	
	public function __construct($id, $data = null)
	{
		assert(is_int($id) && $id > 0);
		assert(is_null($data) || is_array($data));
		
		parent::__construct($id);

		if(is_null($data))
		{
			$data = DS_GlobalAlliance::GetDataById($id);
		}
		
		assert(key_exists('alliance_name', $data));
		assert(key_exists('short_name', $data));
		assert(key_exists('id_leader', $data));
		assert(key_exists('join_password', $data));
		assert(key_exists('join_payment', $data));
		assert(key_exists('join_confirmation', $data));
		assert(key_exists('creation_date', $data));
		
		if(key_exists('alliance_creation', $data))
		{
			$this->needInsert = true;
		} else {
			$this->needInsert = false;
		}
		
		$this->isModifyed = false;
		
		$this->id = $id;
		
		$this->name = $data['alliance_name'];
		assert(!empty($this->name));
		
		$this->shortName = $data['short_name'];
		assert(!empty($this->shortName));
		
		$this->password = $data['join_password'];
		assert(!empty($this->password));
		
		$this->leader = DE_User::CreateFromID((int)$data['id_leader']);
		
		$this->joinPayment = (int)$data['join_payment'];
		assert($this->joinPayment >= 0);
		
		$this->confirmation = $data['join_confirmation'] == 't' ? true : false;
		
		$this->creationDate = GB_TypeFactory::CreateBase('GB_Date', $data['creation_date']);
	}
	
	static public function CreateFromID($id, $data = null)
	{
		assert(is_int($id) && $id > 0);
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);
	}
	
	static public function CreateInDB($data)
	{
		$id = DS_GlobalAlliance::GetNextAllianceID();
		
		assert(is_int($id) && $id > 0);
		
		$data['alliance_creation'] = 1;
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);
	}
	
	public function getID()
	{
		return $this->id;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($name)
	{
		assert(VD_AllianceName::Validate($name, 0, DS_Common::CreateForCurentDB()));
		
		$this->name = $name;
		
		$this->isModifyed = true;
	}

	public function getShortName()
	{
		return $this->shortName;
	}
	
	public function setShortName($tag)
	{
		assert(VD_AllianceTag::Validate($tag, 0, DS_Common::CreateForCurentDB()));
		
		$this->shortName = $tag;
		
		$this->isModifyed = true;
	}
	
	public function getPassword()
	{
		return $this->password;
	}
	
	public function setPassword($pass)
	{
		assert(VD_Password::Validate($pass));
		
		$this->password = $pass;
		
		$this->isModifyed = true;
	}
	
	public function getLeader()
	{
		return $this->leader;
	}
	
	public function setLeader(DE_User $leader)
	{
		$this->leader = $leader;
		
		$this->isModifyed = true;
	}
	
	public function getCreationDate()
	{
		return $this->creationDate;
	}
	
	public function getConfirmation($mode = 'php')
	{
		assert(($mode == 'php') || ($mode == 'pg'));
		
		if($mode == 'php')
		{
			return $this->confirmation;
		} elseif($mode == 'pg') {
			return $this->confirmation ? 't' : 'f';
		}
	}
	
	public function setConfirmation($conf)
	{
		assert(is_bool($conf));
		
		$this->confirmation = $conf;
		
		$this->isModifyed = true;
	}
	
	public function getJoinPayment()
	{
		return $this->joinPayment;
	}
	
	public function setJoinPayment($payment)
	{
		assert(is_int($payment) && $payment >= 0 && $payment < DE_GlobalSettings::Get('GLOBAL_ALLIANCE_MAX_JOIN_PAYMENT'));
		
		$this->joinPayment = $payment;
		
		$this->isModifyed = true;
	}
	
	public function commit()
	{
		if($this->needInsert)
		{
			DS_GlobalAlliance::InsertAlliance($this);
			$this->needInsert = false;
		} elseif($this->isModifyed) {
			DS_GlobalAlliance::UpdateAlliance($this);
			$this->isModifyed = false;
		}
		
		parent::commit();
	}
}

?>