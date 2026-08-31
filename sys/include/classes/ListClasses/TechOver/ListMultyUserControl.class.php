<?
	class ListMultyUserControl extends ListMultyText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_UserControl';

		/** Ключевое поле */
		var $idField = 'user_control_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'user_control_name';

        /** Навазние списка производителей */
        var $listTitle = 'Настройки пользователя CRT мониторов';

        /**  имя списка */
        var $listName = 'user_control_id[]';

        /** Размер видимой области списка */
        var $lstSize = 10;

        /** Тип монитора */
        var $monitorType = "";

        /** Имя таблицы ассоциатора */
        var $tableAssocName = 'p_UserControlAssoc';

		function ListMultyUserControl ($db, $tpl, $monitorType = '') {
			$this->Object ('ListMultiUserControl');
			$this->ListMultyText($db, $tpl, $monitorType);
		}
	}

?>