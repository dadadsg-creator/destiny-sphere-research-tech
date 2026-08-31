<?
	class ListPowerType extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_PowerType';

		/** Ключевое поле */
		var $idField = 'power_type_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'power_type_name';

        /** Навазние списка производителей */
        var $listTitle = 'Тип источника питания LCD';

        /**  имя списка */
        var $listName = 'power_type_id';

		function  ListPowerType ($db, $tpl) {
			$this->Object ('ListPowerType');
			$this->ListText($db, $tpl);
		}
	}

?>