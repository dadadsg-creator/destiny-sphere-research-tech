<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_OutUserCabinet
{
	static public function Out($page, $data)
	{	
		$pageClass = "UC_Page".$page;
		$pageObject = new $pageClass($data);
		$pageContent = $pageObject->out();		
		
		echo $pageContent;
	}
}

?>