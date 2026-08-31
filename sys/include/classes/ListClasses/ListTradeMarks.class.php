<?
	class ListTradeMarks extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'TradeMarks';

		/** Ключевое поле */
		var $idField = 'trade_mark_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'mark_name';

        /** Навазние списка производителей */
        var $listTitle = 'Список торговых марок';

        /**  имя списка */
        var $listName = 'trade_mark_id';

		function ListTradeMarks ($db, $tpl) {
			$this->Object ('ListTradeMarks');
			$this->ListText($db, $tpl);
		}
	}

?>