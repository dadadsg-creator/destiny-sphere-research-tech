<?
	/**
	 *  Класс для работы со списками
	 *
	 *  Описание шаблона
	 *  Обязательные якоря:
	 *  {LIST_NAME} - имя списка
	 *  {ID} - ID знаяения
	 *  {NAME} - Значенеи опции
	 *  {SELECTED} - Определения элиента кака выбранного
	 *  {SIZE} - Размер списка
	 *
	 *  Обязателен динамический шаблон вложенный с именем {ROW}. Содержитстороку списка.
	 */
	class ListClass extends Object {

		/** Класс для работы с шаблонами */
		var $tpl;

		/** Каталог с шаблонами для списков */
		var $templateRoot = "/Lists/";

		/** Имя шаблона для формирования списка */
		var $tplName = "default_list.tpl.html";

		/** Имя списка */
		var $listName = '';

		/** Определяет первое поле пустое или нет */
		var $firstEmptry = false;

		/** Слово для отмечания выбранного элимента */
		var $selectedWord = 'SELECTED';

		/** Размер списка */
		var $lstSize = '1';

		/** Определяет надо ли сыбмитить форму при изменение списка. */
		var $onChangeSubmit = false;

		/** Массив со списком ID обьектов котрые можно будет вывести в список */
		var $outFilter = false;

        /** Заголовок списка ля редатирования */
        var $listTitle = '';

        /** Адрес страницу с общим админситривраонием списков */
        var $mainListAdminPage = "";

        /** Адрес старницы с администированием данного списка*/
        var $listAdminPage = "";

		/**
		 * Констурктор класса.
		 * Параметры:
		 * $tpl - обьект для работы с шаблонами
		 */
		function ListClass($tpl){
			$this->Object('ListClass');
			$this->tpl = $tpl;
            $this->mainListAdminPage = $_SESSION['mainListAdminPage'];
			$this->listAdminPage = $_SESSION['listAdminPage'];
		}

		/**
		 * Метод позваляет рекурсивно выбераить имя шаблона из вышетоящих папок.
		 */
		function getTplName($name){
			return $this->templateRoot.$name;
		}

		/**
		 * Извлекает данные для формирование списка
		 */
		function getList(){
			$msg = "U mast define method getList.";
			$this->error->processError($msg);
		}

		/**
		 * Выводит список
		 * Параметры:
		 * $id - id выбранного элимента
		 */
		function outSelect($id = ''){

			if(!is_array($id) && $id){
				$temp = $id;
				settype($id, 'array');
				$id[] = $temp;
			};

			$rs = $this->getList();

			$listNameForTpl = str_replace("[", "", $this->listName);
            $listNameForTpl = str_replace("]", "", $listNameForTpl);

			$this->tpl->define(array("list_".$listNameForTpl => $this->getTplName($this->tplName)));
			$this->tpl->defineDynamic("list_row", "list_".$listNameForTpl);

			$this->tpl->assign(array ("LIST_NAME" => $this->listName));
			$this->tpl->assign(array ("LIST_SIZE" => $this->lstSize));

            //Есть ли необхадимость ставить пост формы по изменению списка

			if($this->onChangeSubmit){
				$this->tpl->assign(array ("JAVA_SCRIPT" => "ONCHANGE=\"this.form.submit()\""));
			}else{
				$this->tpl->assign(array ("JAVA_SCRIPT" => ""));
			}

            //позволительно ли выберать несколько значений
            if($this->isMulty){
				$this->tpl->assign(array ("MULTIPLE" => "MULTIPLE"));
			}else{
				$this->tpl->assign(array ("MULTIPLE" => ""));
			}

            //Первое значение списка пустое
			if($this->firstEmptry){
				$this->tpl->assign(array ("NAME" => $this->emptyValue));
				$this->tpl->assign(array ("ID" => ""));
				$this->tpl->assign(array ("SELECTED" => ""));
				$this->tpl->parse("LIST_".$listNameForTpl."_ROW", ".list_row");
			}

			if(is_array($rs)){
				foreach($rs as $k=>$v){
					//Проверяем мож но ли элимент выводить
					if($this->outFilter && !in_array($v['id'], $this->outFilter)){
						continue;
					}

					if(is_array($id)){
						$included = in_array($v['id'], $id);
					}else{
						$included = false;
					}

					//Проверяем выбран ли этот элимент
					if( ( $included && $id) || (!$id && $v['selected']) ) {
						$selected = $this->selectedWord;
					}else{
						$selected = '';
					}

					$this->tpl->assign(array ("NAME" => $v['name']));
                    $this->tpl->assign(array ("TEXT_VALUE" => $id[$v['id']]));
					$this->tpl->assign(array ("ID" => $v['id']));
					$this->tpl->assign(array ("SELECTED" => $selected));

					$this->tpl->parse("LIST_".$listNameForTpl."_ROW", ".list_row");

			  	}
			}

			$this->tpl->parse ( 'LIST_'.$listNameForTpl, "list_".$listNameForTpl );
            return $this->tpl->fetch( 'LIST_'.$listNameForTpl );
		}

	}//END OF CLASS ListText

?>