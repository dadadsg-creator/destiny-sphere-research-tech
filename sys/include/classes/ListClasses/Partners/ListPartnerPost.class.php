<?
	class ListPartnerPost extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'PartnerPost';

		/** Ключевое поле */
		var $idField = 'post_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'post_name';

        /** Навазние списка производителей */
        var $listTitle = 'Должность в компании';

        /**  имя списка */
        var $listName = 'post_id';
		
		var $firstEmptry = true;

		function  ListPartnerPost ($db, $tpl) {
			$this->Object ('ListPartnerPost');
			$this->ListText($db, $tpl);
		}
	}

?>