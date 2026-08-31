<?
	class ListRecomendResolution extends ListText {

		/** Имя таблицы из которой извлекают список */
		var $tblName = 'p_RecomendResolution';

		/** Ключевое поле */
		var $idField = 'recomend_resolution_id';

        /** Вводиться ли в ручную ключевое поле */
        var $handIdField = false;

		/** Полдя для вывода в список */
		var $nameField = 'recomend_resolution_name';

        /** Навазние списка производителей */
        var $listTitle = 'Рекомендованные разрешения LCD';

        /**  имя списка */
        var $listName = 'recomend_resolution_id';

		function  ListRecomendResolution ($db, $tpl) {
			$this->Object ('ListRecomendResolution');
			$this->ListText($db, $tpl);
		}
	}

?>