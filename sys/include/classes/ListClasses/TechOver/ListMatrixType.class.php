<?
	class ListMatrixType extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_MatrixType';

		/** Ключевое поле */
		var $idField = 'matrix_type_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'matrix_type_name';

        /** Навазние списка производителей */
        var $listTitle = 'Тип панелей LCD';

        /**  имя списка */
        var $listName = 'matrix_type_id';

		function  ListMatrixType ($db, $tpl) {
			$this->Object ('ListMatrixType');
			$this->ListText($db, $tpl);
		}
	}

?>