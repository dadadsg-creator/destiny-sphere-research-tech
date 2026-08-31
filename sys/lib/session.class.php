<?
class session {
  var $table = "Sessions";          // название таблицы в базе данных
  var $conn_id;                     // идентификатор соединения с базой данных
  var $cookie_name = "SimSiteSID";  // имя переменной в cookie хранящей идентификатор сессии
  var $sid;                         // идентификатор сессии
  var $user_id;                     // идентификатор пользователя открывшего сессию
  var $max_inaction_time = 120;//15;        // максимальное время бездействия в минутах
  var $Error;
  var $ErrMsg;
  
  function session() {
    $this->conn_id = getMysqlConnection();
    $this->sid = isset($_COOKIE[$this->cookie_name]) ? $_COOKIE[$this->cookie_name] : '';
    //eval("global \$".$this->cookie_name."; \$this->sid = \$".$this->cookie_name.";");

    // закрытие сессий у которых превышено время бездействия
    $query = "UPDATE $this->table SET status='disabled' WHERE status='active' AND (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(LastTime) > ".($this->max_inaction_time*60).")";
    $result = mysql_query($query, $this->conn_id);
    if (!$result) $this->Error .= mysql_error($this->conn_id);

    // обновление текущей либо открытие новой сессии
    if (empty($this->sid))  {
        $this->new_session();
    } else  {
        $query = "SELECT user_id FROM $this->table WHERE sid = '$this->sid' AND status='active'";
        $result = @mysql_db_query(DBName, $query, $this->conn_id);
        if ($result) {
            if ($row = mysql_fetch_row($result)) {
                $this->user_id = $row[0];
                $query = "UPDATE $this->table SET LastTime=now() WHERE sid='$this->sid'";
                $result = @mysql_db_query(DBName, $query, $this->conn_id);
                if (!$result) $this->Error .= mysql_error($this->conn_id);
            } else {
                $this->new_session();
            }
        } else $this->Error .= mysql_error($this->conn_id);
    }
    if ($this->Error) $this->ErrMsg = "Ошибка при обращении к базе данных";
  }

  function new_session() {
     $this->sid = md5(time());
     $this->user_id = "";
     SetCookie($this->cookie_name,$this->sid);
     // eval("global \$".$this->cookie_name."; \$".$this->cookie_name." = \$this->sid;");
  }

  function register($user) {
     $query = "SELECT * FROM $this->table WHERE sid='$this->sid'";
     $result = mysql_query($query, $this->conn_id);
     if ($result) {
         if ($row = mysql_fetch_row($result)) {
            $this->logoff();
            $this->new_session();
            $this->register($user);
         } else {
            $query = "INSERT INTO $this->table(sid,user_id,starttime,lasttime,status)
                                                 VALUES('$this->sid','$user',now(),now(),'active')";
            $result = @mysql_db_query(DBName, $query, $this->conn_id);
            if ($result) {
                $this->user_id = $user;
            } else {
                $this->Error .= mysql_error($this->conn_id);
            }
         }
     } else $this->Error .= mysql_error($this->conn_id);
     if ($this->Error) $this->ErrMsg = "Ошибка при обращении к базе данных";
  }

  function logoff() { 
     $this->user_id = "";
     SetCookie($this->cookie_name, "", time()-3600);
     //eval("global \$".$this->cookie_name."; \$".$this->cookie_name." = '';");
     
     $query = "UPDATE $this->table SET status='disabled',lasttime=now() WHERE sid='$this->sid'";
     $result = @mysql_db_query(DBName, $query, $this->conn_id);
     if (!$result) {
        $this->Error .= mysql_error($this->conn_id);
        $this->ErrMsg = "Ошибка при обращении к базе данных";
     }
  }
}

?>