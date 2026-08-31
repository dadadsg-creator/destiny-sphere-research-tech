<?php

class Errors //Класс обработки ошибок
{
	public function ErrorMessage ($Message)
	{
		assert(is_string($Message) && ($Message!=''));
		
		echo $Message, "<br>\n", "<br>\n", "<br>\n";
		
		exit;
	}
}
?>