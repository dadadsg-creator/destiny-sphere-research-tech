<?
	class ListMultyPartnerSphears extends ListMultyText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'PartnerSphears';

		/** Ключевое поле */
		var $idField = 'sphere_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'sphere_name';

        /** Навазние списка производителей */
        var $listTitle = 'Сфера деятельности';

        /**  имя списка */
        var $listName = 'sphere_id[]';

        /** Размер видимой области списка */
        var $lstSize = 3;

        /** Тип монитора */
        var $monitorType = "";

        /** Имя таблицы ассоциатора */
        var $tableAssocName = 'PartnersSphearAssoc';

		function ListMultyListMultyPartnerSphears ($db, $tpl, $monitorType = '') {
			$this->Object ('ListMultyListMultyPartnerSphears');
			$this->ListMultyText($db, $tpl, $monitorType);
		}
	}

?>