<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2010/07/07 08:54:33 $';

// Меню реинкарнации юнитов(то биш их покупки)

class PN_ProtectedField extends UA_GetBuildMenu {

	function __construct() {
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel ($showAlert = false, $messageParams = array()) {
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><protectedfield />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();

		// Выбираем стоимость восстановления одного процента
		$res = DS_Comb::GetProtectiveFieldRestorePrice($this->world);

		$price = '';

		foreach ($res as $k => $course)
		{
			if ($course['name'] == 'SC') {
				$priceSilver = $course['price'];
			} else {
				if (!empty($price)) {
					$price .= ',';
				}

				$price .= number_format($course['price'], 0, ".", "");
			}
		}

		$protectedField = DE_CombProtectiveField::CreateFromCombID($this->comb);
		$currentPersent = $protectedField->getProtectiveField();
		assert($currentPersent >= 0 && $currentPersent <= 100);

		$root->set_attribute ("currentpercent", $currentPersent);
		$root->set_attribute ("price_res", $price);
		$root->set_attribute ("price_silver", $priceSilver);

		// Закладки
		$this->getBuildingTabs ( $root, 91, $this->params["lang"], 'PN_ProtectedField' );

		SU_OutputHandler::Singleton()->addString($doc->dump_mem() , 'xmldata');

		if ($showAlert) {
			assert(is_array($messageParams) && count($messageParams) > 0);
			// Формируем ифу о восстановленном поле
			// Вы восстановили защитное поле на % persent.<br>С бав было снято %<br>.
			$messageName = 'RESTORE_FIELD_INFO_MESS';
			$rs = DS_Misc::GetLocolizedSystemMessage($messageName, (int)$_SESSION['lang']);
			$message = $rs['message'];

			for ($i=0; $i < 2; $i++)
			{
				$pos = strpos( $message, "%" );

				if (!key_exists($i, $messageParams))
				{
					$param = "[!LOST_PARAM!]";
				}else{
					$param = $messageParams[$i];
				}

				if(!empty($param) || $param == 0)
				{
					$message = substr_replace ($message, $param, $pos, 1);
				}
			}

			$val = $messageParams[0];
			$lastSign = substr($messageParams[0], -1, 1);

			if($val >= 5 && $val <= 20)
			{
				$repkase = 'процентов';
			} else {
				if ($lastSign == 1) {
					$repkase = 'процент';
				} else if ($lastSign >= 2 && $lastSign <= 4) {
					$repkase = 'процента';
				} else if ($lastSign >= 5 && $lastSign <= 9) {
					$repkase = 'процентов';
				} else {
					$repkase = 'процентов';
				}
			}

			$message = str_replace('persent', $repkase, $message);
			$title = "";
			SU_OutputHandler::Singleton()->addReadyString('outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf" title="'.$title.'"><txt><![CDATA['.$message.']]></txt></swloader>');	
		}

		return true;

	}
}
?>