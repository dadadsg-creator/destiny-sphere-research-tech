<?
  /**
    Супер класс для всех классов в системе. Несет в себе базовые функциии
  */
  class Object {

    /** Тип обьекта для идентефикации */
    var $objectType;
    
	var $error;
	
    /**
      Конструтокр.
      Параметры:
        $objectType - тип обекта
    */
    function Object ($objectType){
		if(!$this->objectType){
			$this->objectType = $objectType;
		}
		$this->error = new Error ($this->objectType, constant("ERROR_LOG_NAME"),  constant("LOG_PATH"));
    }
  }; //END OF CLASS Object

?>
