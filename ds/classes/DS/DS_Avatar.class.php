<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Avatar extends DS_Base
{
	static public function AddUserAvatar(DE_User $user, $imageUrl)
	{
		assert(!empty($imageUrl));
		
		$common = self::getCommon();
		$common->setDbId(0);

		$sql = "INSERT INTO ds_user_has_image (id_user, image_url) VALUES (".$user->getId().", '".pg_escape_string(urldecode($imageUrl))."')";

		$common->executeQuery ($sql);	

		assert(empty($common->errormsg));
	}
	
	static public function ChangeUserAvatar(DE_User $user, $imageUrl)
	{
		assert(!empty($imageUrl));
		
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "UPDATE ds_user_has_image SET image_url='".pg_escape_string(urldecode($imageUrl))."' WHERE id_user=".$user->getId();

		$common->executeQuery ($sql);	
		assert(empty($common->errormsg));
	}
	
	static function GetUrlUserAvatar($userId)
	{
		assert($userId >= 0);
		
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
				SELECT 
					image_url 
				FROM
					ds_user_has_image
				WHERE 
					id_user = $userId";

		$res = $common->getResultArray ( "", $sql, "", "", "");
		assert(empty($common->errormsg));

		if ( sizeof ($res) > 0 )
		{
			return $res[0]['image_url'];
		}
		else {
			return false;
		}
	}
}//END OF CLASS

?>