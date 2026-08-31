<?
	class ListProductNews extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'ProductNews';

		/** Ключевое поле */
		var $idField = 'status_news_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'status_news';

        /** Навазние списка производителей */
        var $listTitle = 'Список значений продукта по новизне';

        /**  имя списка */
        var $listName = 'status_news_id';

		function ListProductNews ($db, $tpl) {
			$this->Object ('ListProductNews');
			$this->ListText($db, $tpl);
		}
	}

?>