<?
	/**
	 *  Класс для работы со списками с множественным выбором
	 *
	 */
	class ListMultyText extends ListText  {

        /** Позваляет ли список выбор нескольких элиментов */
        var $isMulty = true;

        /** Размер видимой области списка */
        var $listSize = 2;

        /** Тип монитора */
        var $monitorType = "";

        /** Имя таблицы ассоциатора */
        var $tableAssocName = '';

        /** Заменяем шадлон по умолчанию на chekbox */
        var $tplName = 'default_check_box.tpl.html';

        /** Соответсвено заменяем и слово для выделения */
        var $selectedWord = 'checked';
		
		
		var $monitor_id_name = 'monitor_id';
		var $monitor_type_name = 'monitor_type';
		
		/**
		 * Констурктор класса.
		 * Параметры:
		 * $db - обьект для работы с БД
		 * $tpl - обьект для работы с шаблонами
		 */
		function ListMultyText($db, $tpl, $monitorType = 'LCD'){
			$this->Object ('ListText');
            $this->ListText($db, $tpl);
            $this->monitorType = $monitorType;
		}

		/**
		 * Извлекает из БД занчение по ID
		 */
		function getValue($val, $separator = ', '){

        	if(!is_array($val)) return '';

        	foreach ($val as $k=>$v){
                $sql = "SELECT
							".$this->nameField." as name
						FROM ".$this->tblName."
						WHERE ".$this->idField." = '".$v."' ";
	            $rs = $this->db->query($sql);
                $out[] = $rs[0]['name'];
            }

			return implode($separator, $out);
		}

        /** Список значений за обектом */
        function importMultyValues ($id, $rs = ''){

           	$listName = str_replace("[", "", $this->listName);
	        $listName = str_replace("]", "", $listName);

        	if(!$rs[$listName]){
            	$sql = "SELECT
	                        f.".$this->idField." as id
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
	                    $out[] = $v['id'];
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
                		(".$this->monitor_id_name.", ".$this->monitor_type_name.", ".$this->idField.")
                        values
                        ('".$masterId."', '".$this->monitorType."', '".$v."')";
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