<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Объект с данными для репликации
 *
 */
class SU_ReplicationEntityData
{
	/**
	 * Действие
	 *
	 * @var string
	 */
	public $action;
	
	/**
	 * Значения ключевых полей
	 *
	 * @var array
	 */
	public $id;
	
	/**
	 * Название таблицы
	 *
	 * @var string
	 */
	public $tableName;
	
	/**
	 * Названия ключевых полей
	 *
	 * @var array
	 */
	public $fields;
	
	/**
	 * ИД строки из таблицы репликации
	 *
	 * @var int
	 */
	public $replicationID;
	
	/**
	 * ИД базы в которой меняем данные
	 *
	 * @var int
	 */
	public $slaveID;
	
	/**
	 * Массив со строкой из таблицы $tableName
	 *
	 * @var array
	 */
	public $data;
	
	/**
	 * Список разрешенных действий
	 *
	 * @var array
	 */
	static private $allowedActions = array('I', 'U', 'D');
	
	public function __construct(array $data)
	{
		$this->_validateCreationData($data);
		
		$this->action = $data['action'];
		
		$this->id = explode(',', $data['id']);
		assert(count($this->id) > 0);
		
		$this->fields = explode(',', $data['field_name']);
		assert(count($this->fields) > 0);
		assert(count($this->fields) == count($this->id));
		
		$this->tableName = $data['table_name'];
		assert(!empty($this->tableName));
		
		$this->replicationID = (int)$data['id_replication'];
		assert($this->replicationID > 0);
		
		$this->slaveID = (int)$data['id_slave'];
		assert($this->slaveID > 0);
	}
	
	private function _validateCreationData(array $data)
	{
		assert(key_exists('action', $data));
		assert(key_exists('id', $data));
		assert(key_exists('table_name', $data));
		assert(key_exists('field_name', $data));
		assert(key_exists('id_replication', $data));		
		assert(key_exists('id_slave', $data));		
	}
	
	/**
	 * проверка общих данных
	 *
	 */
	public function validate()
	{
		assert(!empty($this->tableName));
		assert(in_array($this->action, self::$allowedActions));
		assert(is_int($this->slaveID) && $this->slaveID > 0);
		assert(is_array($this->fields));
		assert(is_array($this->id));
		assert(count($this->fields) > 0);
		assert(count($this->fields) == count($this->id));
	}
	
	/**
	 * проверка данных, которые нужнну при вставке или апдейте
	 *
	 */
	public function validateData()
	{
		assert(is_array($this->data));
		assert(count($this->data) >= count($this->fields));
	}
	
	public function setReplicationData(array $data)
	{
		assert(count($data) > 0);
		$this->data = $data;
	}
}

?>