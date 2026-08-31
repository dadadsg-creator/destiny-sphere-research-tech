<?
	class ListMultyPowerManagment extends ListMultyText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_PowerManagment';

		/** Ключевое поле */
		var $idField = 'power_managment_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'power_managment_name';

        /** Навазние списка производителей */
        var $listTitle = 'Список стандартов управления питанием';

        /**  имя списка */
        var $listName = 'power_managment_id[]';

        /** Размер видимой области списка */
        var $lstSize = 3;

        /** Тип монитора */
        var $monitorType = "";

        /** Имя таблицы ассоциатора */
        var $tableAssocName = 'p_PowerManagmentAssoc';

		function ListMultyPowerManagment ($db, $tpl, $monitorType = '') {
			$this->Object ('ListMultyPowerManagment');
			$this->ListMultyText($db, $tpl, $monitorType);
		}
	}

?>