<?
	/**
	 *  Класс для работы со списками, данные для которых храняться в БД
	 *
	 */
	class ListText extends ListClass  {

		/** Класс для работы с БД */
		var $db = '';

		/** Имя таблицы из которой извлекают список */
		var $tblName = '';

		/** Ключевое поле */
		var $idField = 'Id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'Name';

        /** Имя поля запрещающего вывода сроки в список*/
		var $hideField = 'hide';

		/** Поле определяющее поле выбранное по умолчанию. */
		var $defSelectField = 'default_val';

		/** Массив с полями для сортировки */
		var $additionalSortFields = 'sort';

        var $listTitle = '';

        var $isMulty = false;

        var $listSize = 1;

        var $monitorTypeUse = false;

        var $monitorType = "";

		/**
		 * Констурктор класса.
		 * Параметры:
		 * $db - обьект для работы с БД
		 * $tpl - обьект для работы с шаблонами
		 */
		function ListText($db, $tpl){
			$this->Object ('ListText');
			$this->ListClass($tpl);
			$this->db = $db;
		}

		/**
		 * Извлекает из БД данные для формирование списка
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
                    	deleted <> 1 AND
						 hide <> 1";
			$sql .=	" ORDER BY ".$this->additionalSortFields." ,".$this->nameField;
			return $this->db->query($sql);
		}

		/**
		 * Извлекает из БД занчение по ID
		 */
		function getValue($id){
			$sql = "SELECT
                       	".$this->idField." as id,
						".$this->nameField." as name,
						".$this->defSelectField." as selected,
                        ".$this->hideField." as hide,
                        ".$this->additionalSortFields." as sort,
                        deleted
					FROM ".$this->tblName."
					WHERE ".$this->idField." = '$id' ";
            if($this->monitorType){
				$sql .= " and monitor_type = '".$this->monitorType."'";
            }
            $rs = $this->db->query($sql);
			return $rs[0]['name'];
		}

        /**
		 * Извлекает из БД занчение по ID
		 */
		function getElement($id){
			$sql = "SELECT
                       	".$this->idField." as id,
						".$this->nameField." as name,
						".$this->defSelectField." as selected,
                        ".$this->hideField." as hide,
                        ".$this->additionalSortFields." as sort,
                        deleted
					FROM ".$this->tblName."
					WHERE ".$this->idField." = '$id'";
            if($this->monitorType){
				$sql .= " and monitor_type = '".$this->monitorType."'";
            }
            $rs = $this->db->query($sql);
			return $rs[0];
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
			return $rs[0];
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
	}//END OF CLASS ListText

?>