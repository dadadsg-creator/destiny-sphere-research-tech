<?
	class ListMultyPartnerResponsibility extends ListMultyText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'PartnerResponsibility';

		/** Ключевое поле */
		var $idField = 'responsibility_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'responsibility_name';

        /** Навазние списка производителей */
        var $listTitle = 'Зоны ответсвенности в компании';

        /**  имя списка */
        var $listName = 'responsibility_id[]';

        /** Размер видимой области списка */
        var $lstSize = 3;

        /** Тип монитора */
        var $monitorType = "";

        /** Имя таблицы ассоциатора */
        var $tableAssocName = 'PartnersResponsibilityListAssoc';

		function ListMultyPartnerResponsibility ($db, $tpl, $monitorType = '') {
			$this->Object ('ListMultyPartnerResponsibility');
			$this->ListMultyText($db, $tpl, $monitorType);
		}
	}
?>