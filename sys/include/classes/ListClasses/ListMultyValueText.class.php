<?
	/**
	 *  Класс для работы со списками с множественным выбором
	 *
	 */
	class ListMultyValueText extends ListText  {

        /** Позваляет ли список выбор нескольких элиментов */
        var $isMulty = true;

        /** Размер видимой области списка */
        var $listSize = 2;

        /** Тип монитора */
        var $monitorType = "";

        /** Имя таблицы ассоциатора */
        var $tableAssocName = '';

        /** Полее с ассоциированным значением */
        var $additionField = '';

        var $tplName = 'default_list_value.tpl.html';


		/**
		 * Констурктор класса.
		 * Параметры:
		 * $db - обьект для работы с БД
		 * $tpl - обьект для работы с шаблонами
		 */
		function ListMultyValueText ($db, $tpl, $monitorType){
			$this->Object ('ListMultyValueText');
            $this->ListText($db, $tpl);
            $this->monitorType = $monitorType;
		}

        /**
		 * Переопределениеи из ListText
         * Ивлекает список из БД
		 */
		function getList($rs = ''){
			$sql = 	"SELECT
						".$this->idField." as id,
						".$this->nameField." as name,
						".$this->defSelectField." as selected,
                        ".$this->hideField." as hide,
                        ".$this->additionalSortFields." as sort,
						deleted
					FROM
                    	".$this->tblName."
                    WHERE
                    	deleted <> 1";
			$sql .=	" ORDER BY ".$this->additionalSortFields." ,".$this->nameField;
			$frec = $this->db->query($sql);
			return $frec;
		}

		/**
		 * Извлекает из БД занчение по ID
		 */
		function getValue($rs){
        	$list = $this->getList();
            foreach($list as $k=>$v){
            	if($rs[$v['id']]){
                	$out[] = array('name'=>$v['name'], 'value'=>$rs[$v['id']]);
                }
            }
			return $out;
		}

        /** Извлеченеи списка значений для обьекта */
        function importMultyValues ($id, $rs = ''){

           	$listName = str_replace("[", "", $this->listName);
	        $listName = str_replace("]", "", $listName);

        	if(!$rs[$listName]){
            	$sql = "SELECT
	                        f.".$this->idField." as id,
                            a.".$this->additionField." as text_value
	                    FROM
	                        ".$this->tblName." f,
	                        ".$this->tableAssocName." a
	                    WHERE
	                        a.monitor_id = '".$id."' and
	                        f.".$this->idField." = a.".$this->idField." and
	                        a.monitor_type = '".$this->monitorType."'";
	            $rs = $this->db->query($sql);

	            if(is_array($rs)){
	                foreach ($rs as $k=>$v){
	                    $out[$v['id']] = $v['text_value'];
	                }
	            }
            }else{
				$out = $rs[$listName];
            }
			return $out;
        }



        /**
		 * Извлекает из БД занчение по NAME
		 */
		function getValueByName ($name){
			$sql = "SELECT
                       	".$this->idField." as id,
						".$this->nameField." as name,
						".$this->defSelectField." as selected,
                        ".$this->hideField." as hide,
                        ".$this->additionalSortFields." as sort,
                        deleted
					FROM ".$this->tblName."
					WHERE ".$this->nameField." = '$name'";
            if($this->monitorType){
				$sql .= " and monitor_type = '".$this->monitorType."'";
            }
            $rs = $this->db->query($sql);
			return $rs;
		}

        /**
		 * обновляем в БД сведения по пункту списка
		 */
		function updateValue($rs, $id){
			$sql = "UPDATE ".$this->tblName."
            		SET
                    	".$this->idField." = '".$rs['id']."',
						".$this->nameField." = '".$rs['name']."',
						".$this->defSelectField." = '".$rs['selected']."',
                        ".$this->hideField." = '".$rs['hide']."',
                        ".$this->additionalSortFields." = '".$rs['sort']."'
                    WHERE ".$this->idField." = ".$id;
            $rs = $this->db->query($sql);
            return true;
		}

        /** Определяет колличесвто элиментов в списку*/
        function countElements(){
        	$sql = "SELECT count(".$this->idField.") as colvo
            		FROM ".$this->tblName."
                    WHERE deleted <> '1'";
            $rs = $this->db->query($sql);
            return $rs[0]['colvo'];
        }

        /**
		 * Добавдение в БД сведения по пукту списка
		 */
		function insertValue($rs){
        	if($rs['id']){
                $sql = "INSERT INTO ".$this->tblName."
            		(
                    	".$this->idField.",
                        ".$this->nameField.",
                     	".$this->defSelectField.",
                        ".$this->hideField.",
                     	".$this->additionalSortFields."
                    )
                    VALUES
                    (
                    	'".$rs['id']."',
                        '".$rs['name']."',
                        '".$rs['selected']."',
                    	'".$rs['hide']."',
                        '".$rs['sort']."'
                    )";
            }else{
                $sql = "INSERT INTO ".$this->tblName."
            		(
                        ".$this->nameField.",
                     	".$this->defSelectField.",
                        ".$this->hideField.",
                     	".$this->additionalSortFields."
                    )
                    VALUES
                    (
                        '".$rs['name']."',
                        '".$rs['selected']."',
                    	'".$rs['hide']."',
                        '".$rs['sort']."'
                    )";
            }
            $rs = $this->db->query($sql);
            return true;
		}

        /** Занесение записи в ассоциатор */
        function insertInAssoc ($masterId, $rs){

        	if(!is_array($rs)){
            	$temp[] = $rs;
                $rs = $temp;
            }

            foreach ($rs as $k=>$v){
            	$sql = "insert into ".$this->tableAssocName."
                		(monitor_id, monitor_type, ".$this->idField.", ".$this->additionField.")
                        values
                        ('".$masterId."', '".$this->monitorType."', '".$k."', '".$v."')";
                $this->db->query($sql);
            }

            return 1;
        }

        /** Удаление записей из ассоциатора */
        function deleteFromAssoc ($masterId){
            $sql = "delete from ".$this->tableAssocName."
            		where
                    	monitor_id = '".$masterId."' and
                        monitor_type = '".$this->monitorType."'";
        	$this->db->query($sql);

            return 1;
        }

        /** Обновление записий ассоциатора */
        function updateAssoc ($masterId, $rs){
        	$this->deleteFromAssoc ($masterId);
            $this->insertInAssoc ($masterId, $rs);
			return 1;
        }
	}//END OF CLASS ListText

?>