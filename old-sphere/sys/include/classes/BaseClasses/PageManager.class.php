<?php
	class PageManager extends Object{

        /** Массив с параметрами страницы */
    	var $Page;
        var $page;

        function PageManager(){
        	$this->page['id'] = $_SERVER['PHP_SELF'];
            $this->Page['id'] = $_SERVER['PHP_SELF'];
        }

    }
?>