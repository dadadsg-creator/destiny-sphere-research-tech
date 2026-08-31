<?php



 class ListAllAdmin extends ListClass {



    	var $db;



        var $param;



        var $pageManager;



        /** Список скрпиттов с листами */

        var $lists = '';/*array (

            'ListMailFormat' => array ( 'ClassName' => 'ListMailFormat'),
			'ListMultyPartnerDirections' => array ( 'ClassName' => 'ListMultyPartnerDirections'),
			'ListPartnerPost' => array ( 'ClassName' => 'ListPartnerPost'),
			'ListPartnerPreferableName' => array ( 'ClassName' => 'ListPartnerPreferableName'),
			'ListPartnerSex' => array ( 'ClassName' => 'ListPartnerSex'),
			'ListMultyPartnerResponsibility' => array ( 'ClassName' => 'ListMultyPartnerResponsibility'),
			'ListMultyPartnerSphears' => array ( 'ClassName' => 'ListMultyPartnerSphears'),

        );*/



        /** Конуструтор класса */

        function ListAllAdmin($db, $tpl, $parameters){

        	$this->Object('ListClass');

            $this->ListClass($tpl);

        	$this->db = $db;

            $this->pageManager = new PageManager;

            $this->param = new Parameters ($parameters, $this->pageManager);

        }



        /********************** PUBLIC METHODS ***************/



        /** Основной метод */

        function main(){

        	if(!is_array($this->lists)){

            	echo $this->outEmptySet();

                return;

            }else{

            	foreach($this->lists as $k=>$v){

                	$a = $this->createClass($v['ClassName']);

                    $b['listTitle'] = $a->listTitle;

                    $b['listCount'] = $a->countElements();

                    $b['url'] = "listsMain2.php?classListAllias=".$v['ClassName'];

                	$rs[] = $b;

                };



                echo $this->outSet($rs);

                return;

            }

        }



		/** создает обьект для администриванония спсиков */

        function getClass(){

        	$name = $this->param->get('classListAllias');

        	return $this->createClass($name);

        }



        /******** OUTPUT METHODS **********************/



        /** Вывод сообщения что нет список для администрирования */

        function outEmptySet(){

        	$this->tpl->define(array('main' => $this->templateRoot.'no_lists_for_admin.tpl.html'));

            $this->tpl->parse ( "MAIN", "main" );

            return $this->tpl->fetch( "MAIN" );

        }



        /** Вывод списка ссылко на админ списокв */

        function outSet($rs){



        	if(!is_array($rs)){

            	$this->error->processError('Resut set is not an array');

            }



        	$this->tpl->define(array('main' => $this->templateRoot.'lists_for_admin.tpl.html'));

            $this->tpl->defineDynamic("row", "main");



            foreach($rs as $k=>$v){

            	$this->tpl->assign(array('LIST_NAME'=>$v['listTitle']));

                $this->tpl->assign(array('COUNT'=>$v['listCount']));

                $this->tpl->assign(array('LIST_URL'=>$v['url']));

                $this->tpl->parse('ROW', '.row');

            }





            $this->tpl->parse ( "MAIN", "main" );

            return $this->tpl->fetch( "MAIN" );

        }



		/******** PRIVATE METHODS *********************/



        /** Создают класс по имени */

        function createClass($className){

        	return eval ('return new '.$className.' ($this->db, $this->tpl);');

        }



    }//END OF CLASS ListAllAdmin















?>