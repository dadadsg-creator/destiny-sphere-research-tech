<?
	class ListPartnerPreferableName extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'PartnerPreferableName';

		/** Ключевое поле */
		var $idField = 'preferable_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'preferable_name';

        /** Навазние списка производителей */
        var $listTitle = 'Пиоритетное написание название компании';

        /**  имя списка */
        var $listName = 'preferable_id';

		function  ListPartnerPreferableName ($db, $tpl) {
			$this->Object ('ListPartnerPreferableName');
			$this->ListText($db, $tpl);
		}
	}

?>