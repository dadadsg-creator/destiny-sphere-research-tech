<?
	class ListTubeVendor extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_TubeVendor';

		/** Ключевое поле */
		var $idField = 'tube_vendor_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'tube_vendor_name';

        /** Навазние списка производителей */
        var $listTitle = 'Производители трубок';

        /**  имя списка */
        var $listName = 'tube_vendor_id';

        /** Первый элимент пустой*/
        var $firstEmptry = false;

		function ListTubeVendor ($db, $tpl) {
			$this->Object ('ListTubeVendor');
			$this->ListText($db, $tpl);
		}
	}

?>