<?
	class ListPanelTechology extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_PanelTechnology';

		/** Ключевое поле */
		var $idField = 'panel_technology_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'panel_technology_name';

        /** Навазние списка производителей */
        var $listTitle = 'Технология изготовления панелей LCD';

        /**  имя списка */
        var $listName = 'panel_technology_id';

		function  ListPanelTechology ($db, $tpl) {
			$this->Object ('ListPanelTechology');
			$this->ListText($db, $tpl);
		}
	}

?>