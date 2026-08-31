<?
	class ListProduct extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'Products';

		/** Ключевое поле */
		var $idField = 'product_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'model_code';

        /** Навазние списка производителей */
        var $listTitle = 'Модель монитора';

        /**  имя списка */
        var $listName = 'prod_id';

        /** Тип мониторов для выборки */
        var $monitorOutType = '';

		function  ListProduct ($db, $tpl, $monitorOutType = '') {
			$this->Object ('ListTubeTechnology');
			$this->ListText($db, $tpl);
            $this->monitorOutType = $monitorOutType;
		}

        /**
		 * Извлекает из БД данные для формирование списка.
         * Переопределенн из класса ListText
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
                    WHERE ";
            if($this->monitorOutType){
            	$sql .= " product_type_id = ".$this->monitorOutType." and ";
            }
            $sql .= " deleted <> 1";
			$sql .=	" ORDER BY ".$this->additionalSortFields." ,".$this->nameField;

			return $this->db->query($sql);
		}
	}

?>