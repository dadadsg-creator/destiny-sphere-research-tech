<?php

class UC_AddAvatar
{
	static private $allianceAvatarLocation = "ds/allianceavatars/";
	static private $userAvatarLocation = "ds/useravatars/";
	
	public function AddUserAvatar(DE_User $user, $image)
	{
		$imageUrl = self::_saveAvatarOnServer(self::$userAvatarLocation, $image);
		
		if (empty($imageUrl))
		{
			return false;
		}
 	
		//Обновляем сведения об аватаре в бд
		DS_Avatar::AddUserAvatar($user, $imageUrl);
		
		return true;
	}
	
	public function ChangeUserAvatar(DE_User $user, $image)
	{
		$oldAvatar = DS_Avatar::GetUrlUserAvatar($user->getId());
		
		//Если у юзверя не было аватарки ранее, то это ошибка
		if (empty($oldAvatar))
		{
			return false;
		}
		
		$imageUrl = self::_saveAvatarOnServer(self::$userAvatarLocation, $image);
		
		if (empty($imageUrl))
		{
			return false;
		}
 	
		//Обновляем сведения об аватаре в бд
		DS_Avatar::ChangeUserAvatar($user, $imageUrl);
		
		//Удаляем файлик со старой аватаркой
		@unlink(self::$userAvatarLocation.$deleteFile);
		
		return true;
	}
	
	public function AddAllianceAvatar(DE_User $user, array $data)
	{
		return true;
	}
	
	private static function _saveAvatarOnServer($avatarLocation, $image)
	{
		//Если у нас есть временный файл, заливаем его на ftp
		$path = $_SERVER['DOCUMENT_ROOT'].$avatarLocation;
		$newFile = $image['name'];
		$oldFile = $image['name'];

		//Проверяем, нет ли файла с таким же именем
		$i = 1;
		while (file_exists($path.$newFile) AND $i<100)
		{
			//Если есть, то прибавляем к файлу цифру, и делаем проверку заново
			$tempName = explode(".", $oldFile);
			$tempName[0] = $tempName[0].$i;
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

		if (!move_uploaded_file($image['tmp_name'], $path.$newFile)) 
		{
			return false;
		}
		
		return $avatarLocation.$newFile;
	}
}

?>


