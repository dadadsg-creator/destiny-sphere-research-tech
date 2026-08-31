<?
	class ListPlugAndPlay extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_PlugAndPlay';

		/** Ключевое поле */
		var $idField = 'plug_and_play_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'plug_and_play_name';

        /** Навазние списка производителей */
        var $listTitle = 'Типы PlugAndPlay';

        /**  имя списка */
        var $listName = 'plag_and_play_id';

		function ListPlugAndPlay ($db, $tpl) {
			$this->Object ('ListTubeVendor');
			$this->ListText($db, $tpl);
		}
	}

?>