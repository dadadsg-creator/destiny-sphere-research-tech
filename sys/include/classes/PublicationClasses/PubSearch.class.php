<?php
	class PubSearch extends Object {

    	/** ОБьекут джля работы с БД */
    	var $db;

        /** ТИп публикациис котрой рабоатем */
        var $pubType;
	var $externalWhere;

        /** Конструктор */
        function PubSearch ($db, $pubType)
	{
		$this->Object ('ProductSearch');
		$this->externalWhere = $where;
		if($db->objectType == "DBinterface")
		{
			$this->db = $db;
		}else{
            		$msg = "В качестве первого параметра конструтокра передали не DBInterface";
				$this->error->processError($msg);
		}
            $this->pubType = $pubType;
	}

		/**
		 * Созхдает экземпляр обьекта.
		 */
		function create ($rs)
		{
			
			$sql = "SELECT * FROM NewsCategories WHERE ID = ".$rs['PageCategory_ID'];
			$rst = $this->db->query($sql);

			if($rst)
			{
				$rs['tema'] = $rst[0]['Title'];
			}else{
				$rs['tema'] = '';
			}

        	$a = new Publication;
            $a->importData($rs);
        	return $a;
		}

        /** Формирование массива с результатoм */
		function formArray($sql){
        	$rs = $this->db->query($sql);
        	if (!$rs) return 0;
			foreach($rs as $v){
           		$obj[] = $this->create($v);
         	}
		 	unset($rs);
         	return $obj;
		}

        /** Формирвоание LIMIT */
		function formLimit($from, $to){
			if($from == 0 && $to == 0){
				return "";
			}else{
				return "LIMIT ".$from.",".$to;
			}
		}

        /** Oфрмляет условие пришедшее с параметром */
		function formCondition($condition){
			if($condition){
				return  " and ".$condition;
			}else{
				return "";
			}
		}

        /** Формировани яусловия для выборки */
        function formSelectSubject($count){
            if($count){
				return " count(ID) as colvo ";
			}else{
				return " * ";
			}
        }

        /** Формирование условия для сортировки */
        function formOrder($order){
        	if($order && $order != 'NULL'){
				if(!is_array($order)) settype($order, 'array');
				return " ORDER BY ".implode(',', $order);
            }else{
            	return " ORDER BY NewsDate DESC";
            }
		}

        /** Извлекает по условию */
        function getByCondition($condition = '', $order = '', $from = 0, $to = 0, $count = false){

			$sql = "SELECT ".$this->formSelectSubject($count)."
					FROM
                      NewsCompany
					WHERE
                    	Placer = '".$this->pubType."'
					  	".$this->formCondition($condition)."
                    ".$this->formOrder($order)."
					".$this->formLimit($from, $to);
//echo $sql;
			if($count){
				$rs = $this->db->query($sql);
				return $rs[0]['colvo'];
			}else{
				return $this->formArray($sql);
			}
		}

        function getAll($order = '', $from = 0, $to = 0, $count = false){
        	$rs = $this->getByCondition('', $order, $from, $to, $count);
            return $rs;

        }

        function getByDBId($id){
        	$condition = " ID =".$id." ";
			return $this->getByCondition($condition);
        }

		function getYearsList(){
			$sql = "SELECT DISTINCT YEAR(NewsDate) as YearNumber
					FROM NewsCompany
                    WHERE Placer = '".$this->pubType."'
					ORDER BY NewsDate";
			$rs = $this->db->query($sql);
			if($rs){
				foreach($rs as $k=>$v){
					$temp[] = $v['YearNumber'];
				}
				return $temp;
			}else{
				$temp[] = 0;
				return $temp;
			}
		}

		function getMonthList(){
			$sql = "SELECT DISTINCT MONTH(NewsDate) as MonthNumber
					FROM NewsCompany
					WHERE Placer = '".$this->pubType."'
					ORDER BY NewsDate";
			$rs = $this->db->query($sql);
			if($rs){
				foreach($rs as $k=>$v){
					if($v<10) $v = '0'.$v;
					$temp[] = $v;
				}
				return $temp;
			}else{
				$temp[] = 0;
				return $temp;
			}
		}

        function getForArchive($yearPub, $monthPub, $order = '', $from = 0, $to = 0, $count = false){
			if(!$yearPub){
				$yearPub = date('Y');
				$monthPub = date('m');
			}

			$condition = "NewsDate <= '".$yearPub."-".$monthPub."-31' and NewsDate >= '".$yearPub."-".$monthPub."-01'";
			return $this->getByCondition($condition, $order, $from, $to, $count);
		}

    }//END OF CLASS ProductSearch


?>