<?
/**
 * Класс, для непосредсвенной работы с БД MySQL. Предназначен для
 * обеспечения работы класса db_interface
 */
class MYSQLinterface extends Object{

	/**
	 * Соединение с БД
	 * Параметры:
	 *  $host -  имя хоста
	 *  $userName - Имя пользователя
	 *  $userPass - Пароль
	 *  $db - имя БД
	 */
	function dbConnect($host, $userName, $userPass, $db) {
		$this->connection = @mysql_connect($host, $userName, $userPass);
		if (!@mysql_select_db($db, $this->connection)){
			$this->error->processError("Неудолось выбрать БД с именем $db.");
		}
	}
	
	function selectDb($db) {
		if (!@mysql_select_db($db, $this->connection)){
			$this->error->processError("Неудолось выбрать БД с именем $db.");
		}
	}
	
	/**
	 * Метод определяет наличие ошибки БД и возвращает в случае ее возникновения
	 * строку с номером и текстом ошибки. В противном случае он вернет 0
	 */
	function dbError() {
		$error = @mysql_errno($this->connection).' '.@mysql_error($this->connection);
		if ($error == ' ') {
			$error = 0;
		}
		return $error;
	}
	
	/**
	 * Метод для передачи запрсоа БД
	 * Параметры:
	 * $sql - Текст запроса
	 */
	function dbQuery($sql) {
		$this->result = @mysql_query ($sql, $this->connection);
	}

	/**
	 * Метод возвращает последний, сгенерированый с помощью AUTO_INCREMENT, id
	 */
	function getLastId() {
		return mysql_insert_id($this->connection);
	}

	/**
	 * Метод формирует результаты SELECT-запроса в виде двумерного массива
	 * Где 1 индекс это номер сроки, начиная с 0, а второй индекс это имя столбца в результате
	 */
	function dbResult() {
		if (!@mysql_num_rows($this->result)) {
			return 0;
		}
		while ($row = @mysql_fetch_array($this->result)) {
			$result[] = $row;
		}
		@mysql_free_result($this->result);
		return $result;
	}
	
	/** Метод возвращает кол-во строк затронутых последним завросом */
	function dbAffectedRows() {
		return mysql_affected_rows();
	}
}
?>