<?
	class ListSubscribe extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'PartnerSubscribe';

		/** Ключевое поле */
		var $idField = 'subscribe_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'subscribe_name';

        /** Навазние списка производителей */
        var $listTitle = 'Подписаться на рассылку';

        /**  имя списка */
        var $listName = 'subscribe_id';

		/** Заменяем шадлон по умолчанию на chekbox */
        var $tplName = 'default_radio.tpl.html';

        /** Соответсвено заменяем и слово для выделения */
        var $selectedWord = 'checked';

		function  ListSubscribe ($db, $tpl) {
			$this->Object ('ListSubscribe');
			$this->ListText($db, $tpl);
		}
	}

?>