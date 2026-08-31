<?php

	/**
     * Класс для администроивания списка
     */
    class ListAdmin extends ListText {

        /** Класс для работы с параметрами */
        var $param = '';

        /** Обьект конкретного списка */
        var $listClass = '';

        /** шаблоны для админского интерфейса */
        var $tpls = array (
        	'main' => 'default_admin_list.tpl.html',
            'form' => 'default_admin_edit.tpl.html'
        );

        /** Менеджер страниц */
        var $pageManager = '';

        /** путь к шадлонам внутри папки templates */
        var $tplPath = '';

        /** Внутри классовые сообщения */
        var $msg;

        /************ PUBLIC METHODS *****************/

        /** Конструтор класса */
    	function ListAdmin ($db, $tpl, $listClass, $parameters){
        	$_SESSION['listAdminPage'] = $_SERVER['PHP_SELF'];

            $this->tplPath = $this->templateRoot;

        	$this->Object('ListClass');
            $this->ListText($db, $tpl);

            $this->pageManager = new PageManager;
            $this->param = new Parameters ($parameters, $this->pageManager);
            $this->listClass = $listClass;

			$this->tblName = $this->listClass->tblName;
			$this->idField = $this->listClass->idField;
        	$this->handIdField = $this->listClass->handIdField;
			$this->nameField = $this->listClass->nameField;
			$this->hideField = $this->listClass->hideField;
			$this->defSelectField = $this->listClass->defSelectField;
			$this->additionalSortFields = $this->listClass->additionalSortFields;
        }

        /** метод управляющий администрированием */
        function admin(){
        	$action = $this->param->get('Action');
            $id = $this->param->get('Id');

            switch ($action){
            	case 'edit':
                	echo $this->edit($id);
                    return;
                case 'editAction':
                	$rs = $this->testForm($_REQUEST);
                    if(!$rs) return;
                	$this->editAction($rs);
                	break;
                case 'newElemet':
                	echo $this->newElement();
					return;
                case 'newElementAction':
                    $rs = $this->testForm($_REQUEST);
                    if(!$rs) return;
                	$this->newElementAction($rs);
                    break;
            }//END OF switch

           	echo $this->showList();

			return true;
        }

        /********** PRIVATE METHODS ***********************/

        /** Вывод списка */
		function showList(){
        	$rs = $this->listClass->getList();

            $this->tpl->define(array('main' => $this->tplPath.$this->tpls['main']));
            $this->tpl->defineDynamic("row", "main");

            $this->tpl->assign(array ('LIST_NAME' => $this->listClass->listTitle));
            $this->tpl->assign(array ('SELF_URL' => $this->pageManager->page['id']));

            if(is_array($rs)){
            	foreach($rs as $k=>$v){
	                $this->tpl->assign(array ('ID' => $v['id']));
	                $this->tpl->assign(array ('NAME' => $v['name']));
	                $this->tpl->assign(array ('SORT_VAL' => $v['sort']));
	                $this->tpl->assign(array ('HIDE' => $v['hide']));
	                $this->tpl->assign(array ('SELECT' => $v['selected']));
	                $this->tpl->parse ( "ROW", ".row" );
                }
            }else{
            	$this->tpl->assign(array ('ID' => '-'));
                $this->tpl->assign(array ('NAME' => '-'));
                $this->tpl->assign(array ('SORT_VAL' => '-'));
                $this->tpl->assign(array ('HIDE' => '-'));
                $this->tpl->assign(array ('SELECT' => '-'));
                $this->tpl->assign(array ('ACTIONS' => '-'));
                $this->tpl->parse ( "ROW", ".row" );
            }

            $this->tpl->parse ( "MAIN", "main" );
            return $this->tpl->fetch( "MAIN" );
        }

        /** метод для вывода формы редактирования */
        function edit($id){
        	$rs = $this->listClass->getElement($id);
            return $this->outForm('editAction', $rs);
        }

        /** Осущетсвление действий по редактированию */
        function editAction($rs){
        	$oldElement = $_SESSION['OldVersionOfListElement'];
        	if(!$this->listClass->updateValue($rs, $oldElement['id'])){
            	die('Неудалось обновить сведения в БД о элементе списка');
            };
        }

        /** Форма для внесения нового элимента */
        function newElement(){
            return $this->outForm('newElementAction');
        }

        /** Дейсвтия по внесению новоо элимента */
        function newElementAction($rs){
        	if(!$this->listClass->insertValue($rs)){
            	die('Неудалось внести сведения в БД о элементе списка');
            };
        }

        /*********** SUPORT METHODS *********************/

        /** Проверка  корректности заполнения формы */
        function testForm($rs){
        	//Проверяем поля обезательные дял заполенния
            if(!$rs['name']) {
            	$errors[] = "Незаполнено поле с именем.";
            }

            settype($rs['id'], integer);

            if($this->handIdField && !$rs['id']){
				$errors[] = "Незаполнено поле с идентефикатором.";
            }elseif($this->handIdField && $rs['id']){
				//Проверяем уникальность ID
                if($rs['Action'] == 'newElementAction'){
                    if(!$this->isUnicId($rs['id'])){
                		$errors[] = "Введенный ID не уникален.";
                	};
                }elseif($rs['Action'] == 'editAction'){
                    if(!$this->isUnicUpdate($rs)){
						$errors[] = "После обновления неуникально или ID или NAME.";
                    }
                }
            }

            //Если обнаружили ошибки
            if($errors){
            	$this->msg = implode('<br>', $errors);
                echo $this->outForm($this->param->get('Action'), $rs);
                return;
            }
            return $rs;
        }

        /** Првоерка того уникален ли апдейт */
        function isUnicUpdate($rs){
        	$oldVersion = $_SESSION['OldVersionOfListElement'];
            $newVersion = $rs;

            if($oldVersion['id'] == $newVersion['id'] && $oldVersion['name'] != $newVersion['name']){
            	return $this->isUnicElement($newVersion['name']);
            }elseif($oldVersion['id'] != $newVersion['id'] && $oldVersion['name'] == $newVersion['name']){
				return $this->isUnicId($newVersion['id']);
            }elseif($oldVersion['id'] == $newVersion['id'] && $oldVersion['name'] == $newVersion['name']){
				return true;
            }else{
            	return $this->isUnicElement($newVersion['name']) && $this->isUnicId($newVersion['id']);
            }
        }

        /** Проверка уникальности значени яэлимента */
        function isUnicElement($name){
        	$a = $this->listClass->getValueByName($name);
        	if($a){
            	return false;
            }else{
            	return true;
            }
        }

        /**  Проверка уникальности ID */
        function isUnicId($id){
        	$a = $this->listClass->getValue($id);

        	if($a){
            	return false;
            }else{
            	return true;
            };
        }

        /** Вовд формы для ввода и редактирования */
        function outForm($action, $rs = ''){
        	if(!is_array($rs)){
            	settype($rs, 'array');
            }else{
            	//Кладем в сесию, что бы потом провериь правильность более точно.
                $_SESSION['OldVersionOfListElement'] = $rs;
            }

            //Выовд сообщений если есть
            if($this->msg){
            	$msg = $this->msg;
                unset($this->msg);
            }else{
            	$msg = '';
            }

            $sortList = new ListNum ($this->tpl, 0, 1000, 0, 'sort');

            $hideData = array(
            	'1' => 'Скрыт',
				'0' => 'Видим'
            );
            $hideList = new ListArray($this->tpl, $hideData, 0, 'hide');

            $selectData = array(
            	'1' => 'По умолчанию',
				'0' => 'Не по умолчанию'
            );
            $selectList = new ListArray($this->tpl, $selectData, 0, 'selected');

            $this->tpl->define(array('main' => $this->tplPath.$this->tpls['form']));
            $this->tpl->assign(array ('SELF_URL' => $this->pageManager->page['id']));
            $this->tpl->assign(array ('ACTION' => $action));
            $this->tpl->assign(array ('MSG' => $msg));

            if($this->listClass->handIdField){
            	$readonly = '';
            }else{
            	$readonly = 'READONLY';
            }

            $this->tpl->assign(array ('READONLY' => $readonly));
            $this->tpl->assign(array ('SORT_LIST' => $sortList->outSelect($rs['sort'])));
            $this->tpl->assign(array ('HIDE_LIST' => $hideList->outSelect($rs['hide'])));
            $this->tpl->assign(array ('SELECT_LIST' => $selectList->outSelect($rs['select'])));

			$this->tpl->assign(array ('ID' => $rs['id']));
            $this->tpl->assign(array ('NAME' => $rs['name']));

            $this->tpl->parse ( "MAIN", "main" );
            return $this->tpl->fetch( "MAIN" );
        }
    }//END OF CLASS ListAdmin

?>