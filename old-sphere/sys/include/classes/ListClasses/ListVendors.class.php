<?
	class ListVendors extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'Vendors';

		/** Ключевое поле */
		var $idField = 'vendor_code';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = true;

		/** Полдя для вывода в список */
		var $nameField = 'vendor_name';

        /** Навазние списка производителей */
        var $listTitle = 'Список производителей';

        /**  имя списка */
        var $listName = 'vendor_code';

		function ListVendors ($db, $tpl) {
			$this->Object ('ListVendors');
			$this->ListText($db, $tpl);
		}
	}

?>