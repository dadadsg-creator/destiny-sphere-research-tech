<?
	class ListAntiFlashCover extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_AntiFlashCover';

		/** Ключевое поле */
		var $idField = 'flash_cover_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'flash_cover_name';

        /** Навазние списка производителей */
        var $listTitle = 'Тип антибликового покрытия';

        /**  имя списка */
        var $listName = 'flash_cover_id';

		function ListAntiFlashCover ($db, $tpl) {
			$this->Object ('ListTubeVendor');
			$this->ListText($db, $tpl);
		}
	}

?>