<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 *
 */
interface DC_CollectionInterface
{
	public function resetNext();
	public function countCollection();
	public function next();
	public function deleteElement($elementForDelete);
} 

?>