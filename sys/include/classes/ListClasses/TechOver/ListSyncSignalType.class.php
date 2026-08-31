<?
	class ListSyncSignalType extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_SyncSignalType';

		/** Ключевое поле */
		var $idField = 'sync_signal_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'sync_signal_name';

        /** Навазние списка производителей */
        var $listTitle = 'Тип синхро сигнала LCD';

        /**  имя списка */
        var $listName = 'sync_signal_id';

		function  ListSyncSignalType ($db, $tpl) {
			$this->Object ('ListSyncSignalType');
			$this->ListText($db, $tpl);
		}
	}

?>