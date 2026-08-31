<?
	class ListMultyPartnerDirections extends ListMultyText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'PartnerDirections';

		/** Ключевое поле */
		var $idField = 'direction_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'direction_name';

        /** Навазние списка производителей */
        var $listTitle = 'Направления деятельности компании';

        /**  имя списка */
        var $listName = 'direction_id[]';

        /** Размер видимой области списка */
        var $lstSize = 3;

        /** Тип монитора */
        var $monitorType = "";

        /** Имя таблицы ассоциатора */
        var $tableAssocName = 'PartnersDirectionsAssoc';

		function ListMultyPartnerDirections ($db, $tpl, $monitorType = '') {
			$this->Object ('ListMultyPartnerDirections');
			$this->ListMultyText($db, $tpl, $monitorType);
		}
	}

?>