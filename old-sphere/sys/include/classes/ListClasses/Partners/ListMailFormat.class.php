<?
	class ListMailFormat extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'PartnerMailFormat';

		/** Ключевое поле */
		var $idField = 'mail_format_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'mail_format_name';

        /** Навазние списка производителей */
        var $listTitle = 'Формат почты';

        /**  имя списка */
        var $listName = 'mail_format_id';
		
	 	/** Заменяем шадлон по умолчанию на chekbox */
        var $tplName = 'default_radio.tpl.html';

        /** Соответсвено заменяем и слово для выделения */
        var $selectedWord = 'checked';

		function  ListMailFormat ($db, $tpl) {
			$this->Object ('ListMailFormat');
			$this->ListText($db, $tpl);
		}
	}

?>