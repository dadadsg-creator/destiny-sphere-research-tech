<?
	/**
     * Класс для вывода форм для работы с файлами
     */

	class FileWork extends FileManager {

    	/** Название группы для работы файла */
    	var $formName = "file";

        var $fileType = array(
    		"Type"=> "image/gif, image/pjpeg",
    		"MaxSize" => "200000",
		    "MinSize" => "",
		    "MaxWidth" => "200",
		    "MaxHeight" => "200",
		    "MinWidth" => "",
		    "MinHeight" => "",
		    "Width" => "",
		    "Height" => ""
		);

        var $tplPath = "/FileWork/";

        /** Система шаблонизации */
        var $tpl;

/*********** PUBLIC METHODS *************/
    	function FileWork($db, $tpl){
        	$this->Object("FileWork");
        	$this->FileManager($db);
        }

        function outForm($id = 0){
        	
        }

        function reciveForm(){
        }

        function outLink(){
        }

        function outImg(){
        }


/*********** PRIVATE METHODS **************/



	}


?>