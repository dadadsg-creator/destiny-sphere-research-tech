<?
	class ListProductStatusTypes extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'ProductStatusTypes';

		/** Ключевое поле */
		var $idField = 'status_type_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'status_name';

        /** Навазние списка производителей */
        var $listTitle = 'Список статусов продукта';

        /**  имя списка */
        var $listName = ' status_type_id';

		function ListProductStatusTypes ($db, $tpl) {
			$this->Object ('ListTradeMarks');
			$this->ListText($db, $tpl);
		}
	}

?>