<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 *  оманда получить лог шпионской акции
 *
 */
class UA_SpyGetLog extends UA_Action 
{
	
	/**
	 * Enter description here...
	 *
	 * @var DE_SpyLog
	 */
	private $log;
	
	function __construct()
	{
		parent::UA_Action();
	}
	
	/**
	 * проверка параметров
	 */
	function checkLocalParams()
	{
		$logID = (int)$this->root->get_attribute("id");
		assert($logID > 0);
		
		$this->log = DE_SpyLog::CreateFromID($logID);
		
		return true;
	}
	
	/**
	 * провер€ем допустимость действи€
	 */
	function checkAction()
	{
		assert($this->uiid == $this->log->getOwner()->getID());
		
		return true;
	}
	
	/**
	 * выполн€ем действие
	 */
	function performAction()
	{
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><log />";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();
		
		$ch = $root->new_child('title');
		$elem = $doc->create_text_node($this->log->getTitle());
		$ch->append_child($elem);
		
		$ch = $root->new_child('data');
		$elem = $doc->create_cdata_section($this->log->getHeader());
		$ch->append_child($elem);
		
		$ch = $root->new_child('text');
		$elem = $doc->create_cdata_section(urlencode(html_entity_decode($this->log->getLogText())));
		$ch->append_child($elem);
		
		SU_OutputHandler::Singleton()->addString($doc->dump_mem(), 'xmldata');
		
		return true;
	}
}

?>