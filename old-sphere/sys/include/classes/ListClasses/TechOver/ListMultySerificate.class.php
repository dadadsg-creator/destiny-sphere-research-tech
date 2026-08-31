<?
	class ListMultySerificate extends ListMultyText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_Sertificate';

		/** Ключевое поле */
		var $idField = 'sertificate_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'sertificate_name';

        /** Навазние списка производителей */
        var $listTitle = 'Список сертификатов';

        /**  имя списка */
        var $listName = 'sertificate_id[]';

        /** Размер видимой области списка */
        var $lstSize = 7;

        /** Тип монитора */
        var $monitorType = "";

        /** Имя таблицы ассоциатора */
        var $tableAssocName = 'p_SertificateAssoc';

		function ListMultySerificate ($db, $tpl, $monitorType = '') {
			$this->Object ('ListMultySerificate');
			$this->ListMultyText($db, $tpl, $monitorType);
		}
	}

?>