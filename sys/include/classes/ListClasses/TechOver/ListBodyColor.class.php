<?
	class ListBodyColor extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_BodyColor';

		/** Ключевое поле */
		var $idField = 'body_color_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'body_color_name';

        /** Навазние списка производителей */
        var $listTitle = 'Цвет корпуса';

        /**  имя списка */
        var $listName = 'body_color_id';

		function  ListBodyColor ($db, $tpl) {
			$this->Object ('BodyColor');
			$this->ListText($db, $tpl);
		}
	}

?>