<?
/**
 * Интерфейсный обьект для работы с БД
 */
class DBInterface extends MYSQLInterface{

    /** Ссылка на результат SELECT запроса */
    var $result = 0;

    /** Идентефикатор соединения */
    var $connection = 0;

    /** Тип используемой БД */
    var $data_base = 'mysql';

    /** Логировать ли простые дестявия или только ошибки */
    var $is_log_actions = '0';

    /** SQL  запрос */
    var $sql;

    /** Обьект для логирования */
    var $logger;

    /**
     * Коснтруктор класса. Осуществляет соединение с БД.
     * Параметры:
     * $host_or_dsn - Имя хоста или DSN
     * $user_name - Имя пользователя
     * $user_pass - Пароль пользователя
     * $db - Имя бд.
     */
    function DBinterface($host_or_dsn, $user_name, $user_pass, $db = '') {
        $this->Object('DBinterface');
        $this->logger = new Logger($this->objectType, constant("DB_LOG_NAME"),  constant("LOG_PATH"));

        //В зависимости от типа используемой БД осуществляем подключение
        if (preg_match( "/mysql/", $this->data_base )) {
            $this->dbConnect($host_or_dsn, $user_name, $user_pass, $db);
        };
        if (preg_match("/odbc/", $this->data_base )) {
            $this->dbConnect ($host_or_dsn, $user_name, $user_pass);
        };
        if (!$this->connection) {
            $this->error->processError("ERROR! Could not connect BD.");
        };
    }

    /**
     * Метод выполняющая SQL запросы. Возвращает двумерный массив
     * с резльтатми запросса, если это был SELECT запрос. Для остальных видов
     * запроссов возвращает 0 в случае неуудачного исполнения
     * или 1 в случае успешнго.
     *   Параметры:
     *   $sql - Запрос
     */
    function query($sql) {
        if (empty($sql)) {
            $this->error->processError("Пустой SQL пришел.");
        };
        $this->sql = $sql;

        //Испольнили запрос
        $this->dbQuery($sql);
        $this->Error();

        //Если надо логировать то логируем
        if ($this->is_log_actions) {
            $this->logger->logIt($sql);
        }

        //Проверяем это SELECT запрос или нет.
        $sql = strtolower($sql);
        if (preg_match("/select/",$sql)) {
            return $this->dbResult();
        } elseif (preg_match("/delete/",$sql) || preg_match("/update/",$sql )) {
            if ($this->result) {
                return $this->dbAffectedRows();
            } else {
                return 0;
            }
        } else {
            if ($this->result) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    /**
     * Метод для определения сколько строк будет затронуто delete или update
     * запросом
     */
    function affectedRows($sql) {
        $sql=strtolower($sql);

        //выделяем условие и имя таблицы для UPDATE
        if (preg_match("/update/",$sql)) {
            if(preg_match("/where/",$sql)){
                $where = substr($sql, strrpos($sql,"where") + 5);
            } else {
                $where = "";
            }
            $table = substr($sql, strrpos($sql,"update") + 6, strrpos($sql,"set") - strrpos($sql,"update") - 6);
        }

        //выделяем условие и имя таблицы для DELETE
        if (preg_match("/delete/",$sql)) {
            if(preg_match("/where/",$sql)) {
                $where = substr($sql, strrpos($sql,"where") + 5);
                $table = substr($sql, strrpos($sql,"from") + 4, strrpos($sql,"where") - strrpos($sql,"from") - 4);
            } else {
                $where = "";
                $table = substr($sql, strrpos($sql,"from") + 4);
            }
        }

        //порверка на случай пустого where
        if ($where) {
            $where = 'where '.$where;
        };

        $sql = "select * from $table $where";
        $rs = $this->query($sql);
        return count($rs);
    }

    /**
    * Метод возвращает последний ID сгенерированый AUTO_INCREMENT
    */
    function lastId() {
        return $this->getLastId();
    }

    //************************ [PRIVATE]  ********

    /**
    * Метод для обработки ошибок
    */
    function error() {
        $error = $this->dbError();
        if (!($error == 0)) {
//          $this->error->processError("<br>In SQL: \"".$this->sql."\"<br>".$error);
        }
    }

    /**
    * Метод для вывода результатов завроса в виде двумерного массива.
    */
    function output() {
        $result = $this->dbResult();
        return $result;
    }
}
?>