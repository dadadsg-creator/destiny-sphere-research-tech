<?
	class ListMaxResolution extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_MaxResolution';

		/** Ключевое поле */
		var $idField = 'max_resolution_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'max_resolution_name';

        /** Навазние списка производителей */
        var $listTitle = 'Максимальное разрешение LCD';

        /**  имя списка */
        var $listName = 'max_resolution_id';

		function  ListMaxResolution ($db, $tpl) {
			$this->Object ('ListMaxResolution');
			$this->ListText($db, $tpl);
		}
	}

?>