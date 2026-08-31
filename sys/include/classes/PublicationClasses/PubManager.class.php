<?php

	class PubManager extends Object {

    	/** Интерфейсный обьект для работы с БД  */
    	var $db;

        /** Класс шаблонизации */
        var $tpl;

        /** Путь к шаблонам */
        var $tplPath = '/Publication/';

        /** Заменитель PageManager в SimSite */
        var $pageManager = '';

        /** Класс для работы с параметрами */
        var $param = '';

        /** Класс для работы с БД */
        var $search = '';

        /** коллличесвто продуктов на старницу */
        var $productsPerPage = 2;

        /** Тип публикации с которыми работаем */
        var $pubType = 'newsBelinea';

		var $dateFilterTpl = "date_filter.tpl.html";
		var $dateFilterListTpl = "date_filter_list.tpl.html";

        function PubManager($pubType, $db, $tpl, $parameters, $pageManager){
        	$this->Object('PubManager');
        	$this->db = $db;
            $this->tpl = $tpl;
            $this->search = new PubSearch ($this->db, $pubType);
            $this->pageManager = $pageManager;
            $this->param = new Parameters ($parameters, $this->pageManager);
            $this->pubtype = $pubType;
        }

        /** СОнвоной метогд */
        function main(){
           	$action = $this->param->get('Action');
            $id = $this->param->get('product_id');
            switch ($action){
               	case 'delete':
                	echo $this->delete($id);
            	case 'edit':
                	echo $this->edit($id);
                    return;
                case 'editAction':
                	$this->editAction();
                	break;
                case 'newElemet':
                	echo $this->newElement();
                    return;
                case 'newElementAction':
                	$this->newElementAction();
                    break;
            }//END OF switch
            echo $this->listAll();
        }

        function formOrder(){
        	$order = $this->param->get('order');
			return $order;
        }

        function listProduct($id, $tplName){
        	$rs = $this->search->getByDBId($id);
            $this->tpl->define(array('main' => $this->tplPath.$tplName));
            $this->tpl->defineDynamic("row", "main");
            $assign['SELF_URL'] = "?p=".$this->pageManager->Page['id'];
            $assign['BASE_URL'] = "?p=".$this->links['mainUrl'];
            $this->formRows($rs);
            $this->tpl->assign($assign);
            $this->tpl->parse ( "MAIN", "main" );
            return $this->tpl->fetch( "MAIN" );
        }

        /** Вывод списка обьектов */
        function listAll($tplName, $count = '', $yearPub = '', $monthPub = '', $isArchive = false){
        	$pageId = $this->param->get('page_id');

            if(!$yearPub){
            	if($count){
	                $this->productsPerPage = $count;
	                $rs_count = 1;
	                $rs = $this->search->getAll($this->formOrder(),($pageId-1)*$this->productsPerPage, $this->productsPerPage);
	            }else{
	                $rs_count = $this->search->getAll($this->formOrder(), 0, 0, true);
	                $rs = $this->search->getAll($this->formOrder(),($pageId-1)*$this->productsPerPage, $this->productsPerPage);
	            };
            }else{
                if($count){
	                $this->productsPerPage = $count;
	                $rs_count = 1;
	                $rs = $this->search->getForArchive($yearPub, $monthPub, $this->formOrder(),($pageId-1)*$this->productsPerPage, $this->productsPerPage);
	            }else{
	                $rs_count = $this->search->getForArchive($yearPub, $monthPub, $this->formOrder(), 0, 0, true);
                    $rs = $this->search->getForArchive($yearPub, $monthPub, $this->formOrder(),($pageId-1)*$this->productsPerPage, $this->productsPerPage);
	            };
            }

            $this->tpl->define(array('main' => $this->tplPath.$tplName));
            $this->tpl->defineDynamic("row", "main");
            $this->tpl->defineDynamic("page", "main");

            $assign['SELF_URL'] = "?p=".$this->pageManager->Page['id'];
            $assign['BASE_URL'] = "?p=".$this->links['mainUrl'];

            if($isArchive){
		        $assign['DATE_FILTER'] = $this->dateFilter();
            }else{
            	$assign['DATE_FILTER'] = '';
            }


            $this->formRows($rs);

            $this->pageBlock($rs_count, $this->productsPerPage, $pageId);

            $this->tpl->assign($assign);

            $this->tpl->parse ( "MAIN", "main" );
            return $this->tpl->fetch( "MAIN" );
        }

        /** Формирует массив с якорями ля вода класса */
        function formOutSeetForProduct($product){
        	$classVars = get_class_vars(get_class($product));

            foreach($classVars as $k=>$v){
                	if($product->{$k}){
                    	$assign[strtoupper($k)] = $product->{$k};
                    }else{
                    	$assign[strtoupper($k)] = "&nbsp;";
                    }
            }

            return $assign;
        }

        /** Формирование строк для ввода */
        function formRows($rs){
            if(is_array($rs)){
                foreach($rs as $k=>$v){
                    $assign = $this->formOutSeetForProduct($v);
                	$this->tpl->assign($assign);
	                $this->tpl->parse ( "ROW", ".row" );
                };
            }else{
             	$product = new Publication();
                $assign = $this->formOutSeetForProduct($product);
                $this->tpl->assign($assign);
                $this->tpl->parse ( "ROW", ".row" );
            }
        }

        /** Вывод секции со списокм ссылок на др страницы */
		function pageBlock($pubCount, $pubPerPage, $pageNum){
			if($this->pageCount ($pubCount, $pubPerPage) == 1 || $this->pageCount ($pubCount, $pubPerPage) == 0){
                $this->tpl->assign(array ("PAGES" => ""));
				$this->tpl->parse("PAGES", ".page");
			}else{
                for ($i = 1; $i <= $this->pageCount($pubCount, $pubPerPage); $i++){
					unset ($page);
					if($i != $pageNum) {
						$page = "&nbsp;<a href=\"?p=".$this->pageManager->Page['id']."&page_id=".$i."\" class=wnd_gen_txt><b><img src=\"img/elements/icon_minipost.gif\" border=0>".$i."</b></a> ";
 					}else{
						$page = "<span class=wnd_header_txt_small><b><img src=\"img/elements/icon_newest_reply.gif\" border=0>".$i."</b></span>";
					}
					$this->tpl->assign(array ("PAGES" => $page));
					$this->tpl->parse("PAGE", ".page");
				}
            }
		}

        /** Подсчет необходимого кооличества страниц для вывдоа списка */
       	function pageCount ( $pubCount, $pubPerPage ){
     		if ( $pubCount == 0 ) $pages = 0;
     		elseif ( $pubCount < $pubPerPage ) $pages = 1;
     		else $pages = ceil ( $pubCount / $pubPerPage );
     		return $pages;
   		}

        function dateFilter (){
			$filterTpl = $this->tplPath.$this->dateFilterTpl;
			$listTpl = $this->dateFilterListTpl;

			$list = new ListDate($this->tpl, $listTpl, "Pub", true,
						$this->search->getYearsList());

			$this->tpl->define(array('filter' => $filterTpl));
			$this->tpl->assign(array ('FILTER_URL' => "?p=".$this->pageManager->Page['id']));

			if($this->param->get('yearPub') != "NULL"){
				$filterDate = $this->param->get('yearPub')."-".$this->param->get('monthPub');
			}else{
				$filterDate = false;
			}

			$this->tpl->assign(array ('FILTER_LIST' => $list->outList($filterDate)));

			$this->tpl->assign(array ('ACTION_NAME' => $this->actionName));
			$this->tpl->assign(array ('ACTION_VALUE' => "showArchivePeriod"));

			$this->tpl->parse ( "FILTER", "filter" );
            return $this->tpl->fetch( "FILTER" );
		}
    }//END OF CLASS ProductAdmin


?>