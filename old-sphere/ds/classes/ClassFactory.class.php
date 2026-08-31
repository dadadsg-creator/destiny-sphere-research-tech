<?php $CVS_Version = '$Revision$ ; $Date$';

class ClassFactory {		

	static public function GetInstance ( $classname ) {
//assert($classname != 'SystemMessages');
		import ($classname);		
		return new $classname;
	}
}

?>