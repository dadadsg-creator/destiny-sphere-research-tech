<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 *
 */
interface DE_AttributesInterface
{    	
   	public function getAttribute($attributeTypeName, $attributeName, $noObject = false);
   	public function haveAttribute($attributeTypeName, $attributeName);
}



?>