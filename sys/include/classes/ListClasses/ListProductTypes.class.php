<?
	class ListProductTypes extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'ProductTypes';

		/** Ключевое поле */
		var $idField = 'product_type_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'type_name';

        /** Навазние списка производителей */
        var $listTitle = 'Список типов товаров';

        /**  имя списка */
        var $listName = 'product_type_id';

		function ListProductTypes ($db, $tpl) {
			$this->Object ('ListProductTypes');
			$this->ListText($db, $tpl);
		}
	}

?>