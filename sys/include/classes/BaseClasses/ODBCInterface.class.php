<?
/**
      Класс, для непосредсвенной работы с БД с помощью ODBC. Предназначен для
      обеспечения работы класса DBinterface
   */
   class ODBCinterface extends Object {

     /**
       Соединение с БД
       Параметры:
         $dataSorce -  имя DSN
         $userName - Имя пользователя
         $userPass - Пароль

     */
     function dbConnect  ($dataSorce,$userName,$userPass) {
       $this->connection = @odbc_connect($dataSorce,$userName,$userPass);
       if(!$this->connection) {
         $this->error->processError("Неудолось устоновиьт соединение с БД.");
       };
     }

     /** Метод для определения ошибок БД */
     function dbError() {
       //Эта строка должна быть закоментированна, если ваш ПХП скомпелирован
       //без поддержки ODBC. В данном случае ошибки не будут детектироваться
       $error = @ODBC_error ( $this->connection ).' '.@ODBC_errormsg ( $this->connection );
       if ( $error == ' ' ) { $error = 0; };
       return $error;
     }

     /**
       Метод для передачи запрсоа БД
         Параметры:
         $sql - Текст запроса
     */
     function dbQuery ($sql){
       $this->result = @ODBC_exec ( $this->connection, $sql );
     }

     /**
       Метод формирует результаты SELECT запроса в виде двумерного массива
       Где 1 индекс это номер сроки, начиная с 0, а второй индекс это имя столбца в результате
     */
     function dbResult () {

       $count_cols = @odbc_num_fields($this->result);

       while (@odbc_fetch_row($this->result)){
         for ( $col = 1; $col <= $count_cols; $col++ ) {
           $field = @odbc_result ( $this->result,  $col );
           $field_name = strtolower ( @odbc_field_name ( $this->result, $col ) );
           $result_row [$field_name] = $field;
         };//End of FOR

         $result[] = $result_row;
       };//end of WHILE

       @odbc_free_result( $this->result );

       return $result;
     }

   };//End OF class ODBC_db_interface


?>