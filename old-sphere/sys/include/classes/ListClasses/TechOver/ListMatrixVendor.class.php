<?
	class ListMatrixVendor extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_MatrixVendor';

		/** Ключевое поле */
		var $idField = 'matrix_vendor_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'matrix_vendor_name';

        /** Навазние списка производителей */
        var $listTitle = 'Производители панелей LCD';

        /**  имя списка */
        var $listName = 'matrix_vendor_id';

		function  ListMatrixVendor ($db, $tpl) {
			$this->Object ('ListMatrixVendor');
			$this->ListText($db, $tpl);
		}
	}

?>