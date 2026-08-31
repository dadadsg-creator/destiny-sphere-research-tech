<?
	class ListMultyErgonomics extends ListMultyText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_Ergonomics';

		/** Ключевое поле */
		var $idField = 'ergonomics_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'ergonomics_name';

        /** Навазние списка производителей */
        var $listTitle = 'Список стандартов эргономических';

        /**  имя списка */
        var $listName = 'ergonomics_id[]';

        /** Размер видимой области списка */
        var $lstSize = 3;

        /** Тип монитора */
        var $monitorType = "";

        /** Имя таблицы ассоциатора */
        var $tableAssocName = 'p_ErgonomicsAssoc';

		function ListMultyErgonomics ($db, $tpl, $monitorType = '') {
			$this->Object ('ListMultyErgonomics');
			$this->ListMultyText($db, $tpl, $monitorType);
		}
	}

?>