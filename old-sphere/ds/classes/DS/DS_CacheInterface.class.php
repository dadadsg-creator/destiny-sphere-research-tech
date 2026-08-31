<?php $CVS_Version = '$Revision$ ; $Date$';

interface DS_CacheInterface
{
	public function get($mark);
	public function set($mark, $lifeTime, $data);
}

?>