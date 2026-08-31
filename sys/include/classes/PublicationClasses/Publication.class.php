<?php
require_once($_SERVER['DOCUMENT_ROOT']."/stream/PlayerLoader.class.php");

    class Publication extends Object {
		/** ID в БД */
    	var $id;
        /** Заголвок статьи*/
        var $alert = "Нет публикаций";
        /** Текст статьи */
        var $body;
		/** Дата новости */
        var $newsDate;
        /** Краткий текст */
        var $descript;
        /** источник */
        var $authors;
		
        var $Tema;
        
        /** День месяц и год новости отдельно */
        var $dateDay;
        var $dateMonth;
        var $dateYear;
        var $MenuImgAct;
		var $Url;
		var $movie;
		var $defaultMovieWidth = 320;
		var $defaultMovieHeight = 240;

        function Publication(){
        	$this->Object('Publication');
        }

        function importData($rs){
	    	$this->id = $rs['ID'];
	        $this->alert = $rs['Alert'];
	        $this->body = $rs['Body'];
            $date = substr ($rs['NewsDate'], 0, 10);
            $date = explode ('-', $date);
	        $this->newsDate = $date[2]."/".$date[1]."/".$date[0];

            $this->dateDay = $date[2];
            $this->dateMonth = $date[1];
            $this->dateYear = $date[0];
            $this->MenuImgAct = $rs['MenuImgAct'];
            $this->Url = $rs['Url'];

            $this->Tema = $rs['tema'];

	        $this->descript = $rs['Descript'];
	        $this->authors = $rs['authors'];
	        $this->movie = $rs['movie'];
	        
	        if(!empty($rs['movie'])){
	        	
	        	empty($rs['movie_width']) ? $width = $this->defaultMovieWidth : $width = intval($rs['movie_width']);
	        	empty($rs['movie_height']) ? $height = $this->defaultMovieHeight : $height = intval($rs['movie_height']);
	        	
	        	$loader = new PlayerLoader($rs['movie'], $width, $height);
				$this->movie =  $loader->getOutput();
	        }
	        else {
	        	$this->movie = '';
	        }
        }

    }//END OF CLASS OBJECT



?>
