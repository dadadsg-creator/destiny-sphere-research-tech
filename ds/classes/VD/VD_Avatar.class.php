<?php  $CVS_Version = '$Revision$ ; $Date$';

class VD_Avatar extends VD_Base
{

	static public function Validate($image)
	{
		assert(!empty($image));

		if ($image['type'] != "image/jpeg" && $image['type'] != "image/gif" && $image['type'] != "image/png" && $image['type'] != "image/pjpeg")
		{
			self::$errorCode = 'NOT_ALLOWABLE_IMAGE_TYPE';
			return false;
		}
		
		$imageSize = getimagesize($image['tmp_name']);
		if ($imageSize[0] > 100 || $imageSize[1] > 100)
		{
			self::$errorCode = 'IMAGE_SIZE_TOO_LONG';
			return false;
		}
		
		if ($image['size'] > 6000)
		{
			self::$errorCode = 'IMAGE_TOO_BIG';
			return false;
		}

		return true;
	}
}

?>