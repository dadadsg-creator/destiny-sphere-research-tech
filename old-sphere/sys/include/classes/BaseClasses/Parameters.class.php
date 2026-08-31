<?
/**
 *	Класс для получения и обработки параметров через Get, Post, Cookie, Session.
 *	Описание параметров:
 *
 *	$p = array(	"ParameterName" => array("Type" => "ParameterType", "Default" => "DefaultValue", "Method" => "RequestMethod",
 *									"Session" => "SessionRegister", "SessionRewrite" => "SessionRewrite")
 *				);
 *
 *	ParameterName	-	имя параметра.
 *	ParameterType 	- 	тип параметра
 *							int - целое число,
 *							float - число с плавающей точкой,
 *							string - цифры, буквы, "_" и "-",
 *							mixed - всё (по умолчанию).
 *	DefaultValue	-	значение, присваиваемое параметру по умолчанию. (По умолчанию NULL).
 *	RequestMethod	-	метод, которым будет передан параметр (Get, Post, Cookie, по умолчанию Request).
 *	SessionRegister	-	определяет будет ли данный параметр зарегистрирован в сессии автоматически.
 *	SessionRewrite	-	определяет будет ли параметр в сессии перезаписан параметром переданным по Request.
 *  PageOutReset 	- 	Рестарт всех параметра когда пользователь уходит со страницу
 *
 *	Пример:
 *
 *	$p = array(	"good_id" => array("Type" => "int", "Default" => "0", "Method" => "Get",
 *									"Session" => "Y", "SessionRewrite" => "Y"),
 *				"password" => array("Type" => "mixed", "Default" => "NULL", "Method" => "Post",
 *									"Session" => "Y", "SessionRewrite" => "N"),
 *				"action" => array("Type" => "string", "Default" => "NULL", "Method" => "Request",
 *									"Session" => "N", "SessionRewrite" => "N")
 *				);
 *  Примеры вызова:
 *  $params = new Parametrs($p);
 *  //Полeчаем значение
 *  $param->get('news_id');
 *  //Устонавливаем новое значениепараметра
 *  $param->set('newsId', 10);
 *  //Формируем хваост для сылки
 *  echo "news.php".$param->formParams("GET", "newsId,action");
 */
class Parameters extends Object {

	/** Массив с описаниями параметров системы. */
	var $parameters;

	/** Массив со значениями параметров. */
	var $paramValues;

	/** На новой ли странице находимся */
	var $isNewPage = true;

	/** Менеджер страниц симсайта */
	var $pageManager;

	/**
	 *	Конструктор класса.
	 *	Параметры:
	 *		$parameters - описание параметров системы.
	 */
	function Parameters ($parameters, $pageManager) {
		$this->Object("Parametrs");

		$this->pageManager = $pageManager;

		if (!is_array($parameters)) {
			$this->error->processError ("Empty parameter's overview.");
		}else{
			$this->parameters = $parameters;
		}

		$this->testNewPage();

		//Импортируем параметры
		$this->importParams();
	}

	/**
	 * Метод определяет мы пришли на новую страницы, или же опять вернулись на прежнюю
	 */
	function testNewPage(){

		$i = count($_SESSION['visitedPages']) - 2;
		$tmp = $_SESSION['visitedPages'];
		$LastPageId = $tmp[$i];
		$pageId = $this->pageManager->Page['id'];

		if(!$LastPageId){
			$this->isNewPage = true;
			return;
		}

		if($_REQUEST['parmetersReset']){
			$this->isNewPage = true;
			return;
		}

		if($LastPageId == $pageId){
			$this->isNewPage = false;
		}else{
			$this->isNewPage = true;
		}
		return;
	}

	/**
	 *	Метод для получения значения параметра.
	 *	Возвращает "" если значение параметра не было установлено или значение
	 *	по умолчанию, определённое в описании параметра.
	 *	В случае отсутствия параметра генерируется ошибка.
	 */
	function get($paramName)
	{
		if (!$this->isExist($paramName)) {
			$this->error->processError ("Parameter [".$paramName."] not defined. U cant get it.");
		}
		return $this->getValue($paramName);
	}

	/**
	 * Метод устанавливает значени параметра
	 * Парметры:
	 * $paramName - имя параметра
	 * $paramValue - значения параметра
	 */
	function set($paramName, $paramValue)
	{
		if ($paramName == 'pubId')
		{
			$paramValue = (int)$paramValue;
		}
		
		//Проверяем есть ли такой параметр
		if (!$this->isExist($paramName)) {
			$this->error->processError ("Parameter [".$paramName."] not defined. U cant set it.");
		}

		//Проверяем, соответсвует ли присваеваемое значени его типу.
		if (!$this->isCorrectType($paramName, $paramValue))
		{
			$this->error->processError ("Value [".$paramValue."] is incorrect for parameter [".$paramName."] by type.");
		}

		//Устанавливаем значение.
		$this->paramValues[$paramName] = $paramValue;

		//Если это необходимо, то одновляем значение в сесии
		if( ($this->parametrs[$paramName]['Session'] == 'Y') &&
		($this->parametrs[$paramName]['SessionRewrite'] == 'N') ) {
			$this->setToSession($pramName, $paramValue);
		}
	}

	/**
     * Метод формирует список параметров для передачи по GET или POST
     * Параметры:
     * $type - Тип списка параметров для GET или POST
     * $paramNames - Массив с параметрами дял передачи или строка разделенная ","
	 */
	function formParams ($type, $paramNames){

		//Првоерка на случай если передали не массив а строку разделнную запятыми
		if(!is_array($paramNames)){
			$paramNames = explode(",", $paramNames);
			//Это на всякий слйчай уберам лишнее пробелы.
			foreach($paramNames as $k=>$v) $paramNames[$k] = trim($v);
		}

		//Поверяем все л параметры существуют
		foreach($paramNames as $v){
			if(!$this->isExist($v)){
				$this->error->processError ("Parametr with name [".$v."] dont exist in system. Method formParams.");
			}
		}
		$type = strtolower($type);
		switch ($type) {
			case 'post':
			$tail = $this->formPostTail($paramNames);
			break;
			case 'get':
			$tail = $this->formGetTail($paramNames);
			break;
			default:
			$this->error->processError ("Incorrect parametr type=".$type." for method formParams.");
		}

		return $tail;
	}

	###############################################[ Private ]#################################################
	/**
      * Метод формирует хвост из параметров для передачи по GET
      * Параметры:
      * $paramNames - массив с именами параметров для передачи
      */
	function formGetTail($paramNames){
		foreach($paramNames as $v){
			$tail .= $v."=".$this->paramValues[$v];
		}
		return $tail;
	}

	/**
      * Метод формирует хвост из параметров для передачи по POST
      * Параметры:
      * $paramNames - массив с именами параметров для передачи
      */
	function formPostTails($paramNames){
		foreach($paramNames as $v){
			$tail .= "<input type=\"hidden\" name=\"".$v."\" value=\"".$this->paramValues[$v]."\">";
		}
		return $tail;
	}

	/**
      * Метод импортирует в систему параметры переданые.
      */
	function importParams()
	{
		foreach ($this->parameters as $k=>$v)
		{
			//принимаем значение
			$param = $this->getParam($k,$v);

			//Проверяем корректность
			if(!$this->isCorrectType($k, $param))
			{
				$this->error->processError ("Value [".$param."] is incorrect for recived parameter [".$k."] by type.");
			}

			$this->paramValues[$k] = $param;

			//Если параметра нет в сесии, но в сесии хранипть надо, то заносим в сесиию
			if(($this->parameters[$k]['Session']=='Y')&&($param!="NULL")&&(!$this->getFromSession($k))){
				$this->setToSession($k, $param);
			};

			//Если разрешенно перезаписать в сесии, то перезапсываем.
			if(($this->parameters[$k]['Session']=='Y')&&($param!="NULL")&&($this->parameters[$k]['SessionRewrite']=='Y')){
				$this->setToSession($k, $param);
			};
		}//END OF foreach()
	}

	/**
	  * Метод для получения значения параметра.
	  * Параметры:
	  * $paramName - Имя параметра
	  * $paramOver - Описание параметра
	  */
	function getParam($paramName, $paramOver)
	{
		//принимаем параметр
		switch ($this->parameters[$paramName]['Method'])
		{
			case 'Post':
			$param = $_POST[$paramName];
			break;
			case 'Get':
			$param = $_GET[$paramName];
			break;
			case 'Cookie':
			$param = $_COOKIE[$paramName];
			break;
			case 'Request':
			$param = $_REQUEST[$paramName];
			break;
			case '':
			$param = $_REQUEST[$paramName];
			break;
			default:
			$this->error->processError("Incorrect Request type [".$this->parameters[$paramName]['Method']."] for parameter [".$paramName."].");
		}
		
		$param = urlencode($param);
		

		if ($paramName == "pubId")
		{
			$param = (int)$param;
		}
		
		//Теперь cмотрим что с сесией
		if(
		($param == "")&&
		($this->getFromSession($paramName) <> "")&&
		($this->parameters[$paramName]['Session'] == 'Y')&&
		!($this->parameters[$paramName]['PageOutReset'] == 'Y' && $this->isNewPage)
		){
			$param = $this->getFromSession($paramName);
		}

		//Присваеваем дефалтное значение если неопределенно
		if(!$param){
			if($this->parameters[$paramName]['Default']){
				$param = $this->parameters[$paramName]['Default'];
			}else{
				$param = "NULL";
			}
		}
		return $param;
	}

	/**
      * Метод извлекает из сесси значение параметра
      * Параметры:
      * $paramName - имя параметра
      */
	function getFromSession ($paramName) {
		return $_SESSION["param_".$paramName];
	}

	/**
      * Метод для занесения параметров в сесию
      * Параметры:
      * $paramName -  Имя параметра
      * $paramValue - Значение параметра
      */
	function setToSession($paramName, $paramValue){
		$_SESSION["param_".$paramName] = $paramValue;
	}

	/**
      * Метод определяет соответсвие значения параметра его типу.
      * Параметры:
      * $paramName - имя параметра для проверки
      * $paramValue - значение праамера проверяемое
      */
	function isCorrectType($paramName, $paramValue)
	{
		switch ($this->parameters[$paramName]['Type'])
		{
			case 'int':
			$regex = '[[:digit:]]+';
			break;
			case 'float':
			$regex = '';
			break;
			case 'string':
			$regex = "[[:alnum:]]+";
			break;
			case 'mixed':
			return 1;
			break;
			default:
			$this->error->processError
			("incorrect type name [".$this->parameters[$paramName]['Type']."]
           for parameter [".$paramName."].");
		}
		return (eregi("^".$regex."$", $paramValue) || ($paramValue=='NULL'));
	}

	/**
	 *	Метод определяет существует ли параметр в описании параметров системы.
	 *	Параметры:
	 *		$paramName - имя параметра.
	 *	Возвращаемое значение:
	 *		boolean.
	 */
	function isExist($paramName) {
		return array_key_exists ($paramName, $this->parameters);
	}

	/**
	 *	Возвращает значение параметра.
	 *	Параметры:
	 *		$paramName - имя параметра.
	 *	Возвращаемое значение:
	 *		значение параметра или NULL.
	 */
	function getValue($paramName) {
		return $this->paramValues[$paramName];
	}
}
?>