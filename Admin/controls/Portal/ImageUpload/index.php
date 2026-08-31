<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

class UploadImage
{
	private $action = 'index';
	
	private $uploadDir = '/UserFiles/';
	
	public function __construct(array $data, array $files)
	{
		$this->_getParams($data);
		
		switch ($this->action)
		{
			case 'index':
				$this->_showUploadForm();
				break;
		
			case 'upload':
				$this->_storeUploadFile($files);
				break;
				
			default:
				break;
		}
	}
	
	private function _getParams(array $data)
	{
		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}
	}
	
	private function _showUploadForm()
	{
		echo "
			<form action='' method='post' enctype='multipart/form-data'>
			<input type='hidden' name='action' value='upload'>
			Upload file: <input type='file' size=30 name='file'><br>
			<input type='submit' value='send file'>
			</form>
			";
	}
	
	private function _storeUploadFile(array $files)
	{	
		assert(key_exists('file', $files));
		assert(is_uploaded_file($files['file']['tmp_name']));
		
		if ($files['file']['name'] != "")
		{
			//Если у нас есть временный файл, заливаем его на ftp

			$path = $_SERVER['DOCUMENT_ROOT'].$this->uploadDir;
			$newFile = $files['file']['name'];
			$oldFile = $files['file']['name'];

			$tempName = explode(".", $oldFile);
						
			$allowed_ext = array('gif', 'jpg', 'png');
			
			if(!in_array(strtolower($tempName[count($tempName) - 1]), $allowed_ext))
			{
				ShowCMSMessage("Wrong file extension", 2);
				return;
			}
			
			//Проверяем, нет ли файла с таким же именем
			$i = 1;
			while (file_exists($path.$newFile) && $i<100)
			{
				//Если есть, то прибавляем к файлу цифру, и делаем проверку заново
				$tempName[0] = $tempName[0].'_'.$i;
				$newFile = "";

				foreach ($tempName as $key => $val)
				{
					if ($key != 0 )
					{
						//Добавляем точуку
						$newFile .= ".";
					}
					$newFile .= $val;
				}
				$i++;
			}

			if (move_uploaded_file($files['file']['tmp_name'], $path.$newFile)) {
				ShowCMSMessage("File was uploaded with name $newFile", 1);
			} else {
				ShowCMSMessage("Cant't upload file $newFile to ".$path, 2);
			}
		}
		
		$this->_showUploadForm();
	}
}

$u = new UploadImage($_REQUEST, $_FILES);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>