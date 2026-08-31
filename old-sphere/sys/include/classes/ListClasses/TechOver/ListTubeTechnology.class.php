<?
	class ListTubeTechnology extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_TubeTechnology';

		/** Ключевое поле */
		var $idField = 'tube_technology_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'tube_technology_name';

        /** Навазние списка производителей */
        var $listTitle = 'Технолгиия изготовления трубки';

        /**  имя списка */
        var $listName = 'tube_technology_id';

		function ListTubeTechnology ($db, $tpl) {
			$this->Object ('ListTubeTechnology');
			$this->ListText($db, $tpl);
		}
	}

?>