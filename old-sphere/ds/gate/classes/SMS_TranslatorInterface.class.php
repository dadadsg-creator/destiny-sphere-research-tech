<?php

interface SMS_TranslatorInterface
{
	public function Translate($params);

	public function getPartnerCode();
}

?>