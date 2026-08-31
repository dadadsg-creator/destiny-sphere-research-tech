<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Формирование менюшки списка сообщений
 *
 */
class TL_MessageOverview extends TL_BaseMenu
{
	static public function FormMenu(TI_MessageOverview $incoming)
	{
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><messageoverview />";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();
		
		$root->set_attribute('list', $incoming->type);
		
		foreach ($incoming->messages as $k => $message)
		{
			assert(key_exists('id_message', $message));
			assert(key_exists('sender', $message));
			assert(key_exists('reciever', $message));
			assert(key_exists('subject', $message));
			assert(key_exists('messagedate', $message));
			assert(key_exists('readdate', $message));
			assert(key_exists('id_messagescope', $message));
			assert(key_exists('text', $message));
			
			$ch = $root->new_child("message");
			
			$ch->set_attribute("id", $message['id_message']);		
			if($incoming->type == 'out')
			{
				$ch->set_attribute("name", $message['reciever']);
			} else {
				$ch->set_attribute("name", $message['sender']);
			}
			$ch->set_attribute("subj", str_replace(array('[', ']', '&', '%'), '', $message['subject']));
			$ch->set_attribute("datecreate", $message['messagedate']);
			if($incoming->type == 'in')
			{
				$ch->set_attribute("dateread", $message['readdate']);
			} else {
				$ch->set_attribute("dateread", '');
			}
			$ch->set_attribute("lscopename", $message['text']);
			$ch->set_attribute("scopeid", $message['id_messagescope']);
		}
		
		return $doc->dump_mem();
	}
}

?>