<?
    /**
     * Класс для загрузки и хранения файлов
     *
     * Описание типа файла:
     * type = array(
     		"Type"=> GIF, JPEG, ZIP - Тип файла.
            "MaxSize" => 100000 максимальный размер в байтах.
            "MinSize" => 100  минимальный размер фалйа в байтах.
            "MaxWidth" => максимальная ширина графического файла в пикселях.
            "MaxHeight" => максимальная ширина файла в пикселях.
            "MinWidth" => минимальная ширина файла в пикселях.
            "MinHeight" => максимальная ширина файла в пикселях.
            "Width" => ширина файла в пикселях.
            "Height" => высота файла в пикселях.
     )


     create table FileStoring (
     	FileId int AUTO_INCREMENT PRIMARY KEY,
        StoreName varchar(255),
        OriginalName varchar(255),
        FileType varchar(50),
        FileSize int,
        Width int,
        Height int
     )

     */

  class FileManager extends Object {

     /** Обьект для работы с БД */
     var $db;

     /** Директория для хранения файлов */
     var $fileStorePath = '/files/';

     /** Способ хранения фалов по умолчанию */
     var $defStoreType = "file";

     /** Таблица в БД с данными о фале */
     var $fileDataTable = "FileStoring";

     /** Тексты ошибок при несоотвсиве паарметров */
     var $errorMsgs = array(
        "Type"=> "Тип файла нессответсвует требуемому",
        "MaxSize" => "Файл больше допустимомго размера",
        "MinSize" => "Файл меньше допустимогор размера",
        "MaxWidth" => "Ширина изображения больше допустимой",
        "MaxHeight" => "Высота изображения больше допустимой",
        "MinWidth" => "Ширина изображения меньше допустимой",
        "MinHeight" => "Высота изображения меньше допустимой",
        "Width" => "Ширина несоответствует требуемой",
        "Height" => "Высота изображения несоответствует требуемой"
     );

     /** Список поддерживаемых типов и расширения для хранения их */
     var $mimeTypes = array(
        	'image/gif' => 'gif',
            'image/pjpeg' => 'jpg',
            'application/x-zip-compressed'=>'zip',
            'application/pdf'=>'pdf',
            'application/vnd.ms-excel'=>'xls'
     );
     /** массив с текстами ошибок возникших при обработке */
     var $errors = array ();

     /**
      * Конструкnоh класса.
      * Параметры:
      * $fileTypes - Массив с описанием типов фалов загружаемых
      * $db - обьект для работы с БД.
      */
     function FileManager ($db){
       $this->Object("FileManager");
       $this->db = $db;
	   if ($db->objectType != "DBinterface"){
          $this->error->processError ("Incorrect object for DB work.");
	   }
     } //END OF METHOD FileManager


     /**
      * Занесение файла в ситему.
      * Параметры:
      *  $file - строка из массива FILES с опсиани ем этого файла
      *  $fileType - опсиани етипа файла
      */
     function put($file, $fileType, $storeNameType = 'auto'){
     	if(!$this->isCorrectType($file, $fileType)){
        	return false;
        };
        return $this->importRecord($file, $storeNameType);
     }

     /**
      * Возвращает ссылка на файл.
      * Паарметры:
      * $id - ID в БД файла
      * $linkType - тип ссылки требуемывй
      * $params - текст вставляемый в тег
      */
     function get($id){
        $rs = $this->getDbRecord($id);
       	if(!$rs){
        	return "";
        }else{
        	return $this->fileStorePath.$rs['StoreName'];
        }

     }

     /**
      * Удаление файла из сиситемы
      * Параметры:
      * $id - ID файла в БД.
      */
     function delete ($id){
     	if($this->deleteFile($id)){
        	return 1;
		}

	 	if(!$this->deleteDbrecord($id)){
            $this->errors[] = "Неудалось удалить из БД сведения о файле с ID = ".$id;
            return 1;
       	}

     	return 0;
     }

     /**
      * Обновление файла в БД.
      * Параметры:
      * $id - ID старого файла в БД
	  * $file - строка из массива FILES с опсиани ем этого файла
      * $fileType - опсиани етипа файла
      */
     function update($id, $file, $fileType, $storeNameType = 'auto'){

     	$rs = $this->getDbRecord($id);

        if(!$rs){
        	return $this->put($file, $fileType, $storeNameType);
        }

      	if(!$this->isCorrectType($file, $fileType)){
        	return false;
        };

        return $this->updateRecord ($id, $file, $rs, $storeNameType);
     }

    /**
     * Извлекает из БД сведения о файле
     */
     function getDbRecord($id){
        $sql = "SELECT * FROM ".$this->fileDataTable." WHERE FileId = '".$id."'";
        $rs = $this->db->query($sql);
       	return $rs[0];
     }
//---------------------- Private -----------------------//

     function deleteFile($id){
     	if(!file_exists($_SERVER['DOCUMENT_ROOT'].$this->get($id))) return true;

        if(!unlink($_SERVER['DOCUMENT_ROOT'].$this->get($id))){
            $this->errors[] = "Неудалось удалить файл";
            return false;
        }
     	return true;
     }

	 /**
      * Проверка файла н соответсвие описанному типу
      * Параметры:
	  * $file - сктрока из массива FILES
      * $fileType - опсиание типа файла
      */
     function isCorrectType($file, $fileType){
     	//Првоеряме на соответсвие типу файлы
		$mimeTypes = explode(',', $fileType['Type']);
        foreach ($mimeTypes as $k=>$v) $mimeTypes[$k] = trim($v);
        if(!in_array($file['type'], $mimeTypes)){
        	$haveErrors = true;
            $this->errors[] = $this->errorMsgs['Type'];
        }

        //Проверяем сооветсвие размеру
        if($fileType['MaxSize'] && ($file['size']>$fileType['MaxSize']) ){
            $haveErrors = true;
            $this->errors[] = $this->errorMsgs['MaxSize'];
        }
        if($fileType['MinSize'] && ($file['size']<$fileType['MinSize']) ){
            $haveErrors = true;
            $this->errors[] = $this->errorMsgs['MaxSize'];
        }

        return !$haveErrors;
     }

     /**
      * Удаляет из БД сведения о файле
      * Парметры:
      * $id - ID строки в БД
      */
     function deleteDbRecord($id){
     	$sql = "DELETE FROM ".$this->fileDataTable." WHERE FileId = '".$id."'";
        if(!$this->db->query($sql)){
        	$this->errors[] = "Неудалось удалить из БД строку c ID = ".$id;
        	return false;
        };
        return true;
     }

     /**
      * Занесенеи файла в БД и капирвоание файла
      * Парметры:
      * $file - строка из массива FILES
      */
     function importRecord ($file, $storeNameType){

        $width = 0;
        $height = 0;
     	//Заносим в БД сведения о файле
        $sql = " INSERT INTO ".$this->fileDataTable."
        		 (StoreName, OriginalName, FileType, FileSize, Width, Height)
                 VALUES
                 (
                 	'".$storeName."',
                 	'".$file['name']."',
                    '".$file['type']."',
                    '".$file['size']."',
                    '".$width."',
                    '".$height."'
                 )";
        if(!$this->db->query($sql)){
        	$this->errors[] = "Неудалось занести в БД сведения о файле";
        	return 0;
        };

        $id = $this->db->getLastId();

        $storeName = $this->formStoreName($id, $file, $storeNameType);

        $sql = "UPDATE ".$this->fileDataTable."
        	    SET StoreName = '".$storeName."'
                WHERE FileId = '".$id."'";
        if(!$this->db->query($sql)){
        	$this->deleteDbrecord($id);
        	$this->errors[] = "Неудалось занести в БД о StoreName файла";
        	return 0;
        }

        if(!$this->copyFile($storeName, $file, $id)){
        	return 0;
        }

        return $id;
     }

     /**
      * Формирвет имя для хранения файла
      *
      */
     function formStoreName($id, $file, $storeNameType = 'auto'){
        if($this->mimeTypes[$file['type']]){
        	$storeExtension = $this->mimeTypes[$file['type']];
        }else{
        	$this->errors[] = "Неопиcанно разширение для типа файла ".$mimeTypes[$file['type']];
        	return "";
        }

        if($storeNameType == 'auto'){
        	return $storeName = $id.'.'.$storeExtension;
        }elseif($storeNameType == 'self'){
        	if (file_exists($_SERVER['DOCUMENT_ROOT'].$this->fileStorePath.$file['name'])){
                $this->errors[] = "Имя файла ".$file['name']." не уникально.";
 		       	return "";
            }else{
				return $file['name'];
            }
        }
     }

     /**
      * Метод для копирования фавйла к месту хранения
      * Параметры:
      * $storeName - имя под которым файл должен храниться
      */
     function copyFile($storeName, $file, $id){
        //копируем файл в место хранения
        if(!is_dir($_SERVER['DOCUMENT_ROOT'].$this->fileStorePath)){
        	$this->errors[] = "Путь указанный для хранения файлов ".$_SERVER['DOCUMENT_ROOT'].$this->fileStorePath." не корректен";
        	$this->deleteDbrecord($id);
        	return false;
        }

        if(!copy($file['tmp_name'], $_SERVER['DOCUMENT_ROOT'].$this->fileStorePath.$storeName)){
            $this->errors[] = "Неудалось скопировать файл";
            $this->deleteDbrecord($id);
        	return false;
        }

        return true;
     }

     /**
      * Обновление записи с фалом
      * Параметры:
      * $id - ID в бд старой записи
      * $file -  стркоа с файлом
      * $rs - предидущее состояние строки в БД
      */
     function updateRecord ($id, $file, $rs, $storeNameType){
        $storeName = $this->formStoreName($id, $file);
        $width = 0;
        $height = 0;

        //Обновлячем файл в месте хранения
        if(!$this->deleteFile($id)){
        	//return false;
        }

        //Обновляем сведения в БД
        $sql = "UPDATE ".$this->fileDataTable."
        	    SET
                	StoreName = '".$storeName."',
                    OriginalName = '".$file['name']."',
                    FileType = '".$file['type']."',
                    FileSize = '".$file['size']."',
                    Width = '".$width."',
                    Height = '".$height."'
                WHERE FileId = '".$id."'";

        if(!$this->db->query($sql)){
            //$this->errors[] = "Неудалось обнавить запись о файле с ID = ".$id;
            //return false;
        }

        if(!$this->copyFile($storeName, $file, $id, $storeNameType)){
        	return false;
        }

     	return true;
     }
  }

?>