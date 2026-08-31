<?
	class ListPartnerSex extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'PartnerSex';

		/** Ключевое поле */
		var $idField = 'sex_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'sex_name';

        /** Навазние списка производителей */
        var $listTitle = 'Пол';

        /**  имя списка */
        var $listName = 'sex_id';
		
		/** Заменяем шадлон по умолчанию на chekbox */
        var $tplName = 'default_radio.tpl.html';

        /** Соответсвено заменяем и слово для выделения */
        var $selectedWord = 'checked';

		function  ListPartnerSex ($db, $tpl) {
			$this->Object ('ListPartnerSex');
			$this->ListText($db, $tpl);
		}
	}

?>