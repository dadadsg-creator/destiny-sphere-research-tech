<?
	class ListMultyValueResolutionWithFreq extends ListMultyValueText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_Resolution';

		/** Ключевое поле */
		var $idField = 'resolution_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'resolution_name';

        /** Навазние списка производителей */
        var $listTitle = 'Список расширений для указания частот рабты';

        /**  имя списка */
        var $listName = 'resolution_id';

        /** Размер видимой области списка */
        var $lstSize = 3;

        /** Тип монитора */
        var $monitorType = "";

        /** Имя таблицы ассоциатора */
        var $tableAssocName = 'p_ResolutionWithFreq';

        /** Полее с ассоциированным значением */
        var $additionField = 'freq_val';

		function ListMultyValueResolutionWithFreq ($db, $tpl, $monitorType = '') {
			$this->Object ('ListMultyValueResolutionWithFreq');
			$this->ListMultyValueText($db, $tpl, $monitorType);
		}
	}

?>