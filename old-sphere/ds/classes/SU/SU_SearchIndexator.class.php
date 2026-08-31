<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Статический класс получает список всех страничек портала
 */

class SU_SearchIndexator
{
	private static $linksArr = array();
	private static $root;
	private static $defaultTitle = 'Destiny Sphere';

	public static function  Exec($root)
	{
		assert(!empty($root));
		self::$root = $root;

		self::$linksArr[] = $root;

		self::_FindLinks($root);

		return;
	}

	private static function _AddLink($root, $link)
	{
		//Отсеиваем лишнее
		if ( $link == "" || $link == "http://" || preg_match("!.*jpg$!i", $link)
		|| preg_match("!mailto:!i", $link) || preg_match("!^www\.!i", $link)
		|| preg_match("!^javascript:!i", $link) )
		{
			return;
		}

		if (substr_count($link, '../') > 0)
		{
			//Заменяем корневые каталоги
			for ($i = 0; $i <= substr_count($link, '../'); $i++)
			{
				$slesh_position = strrpos($root, '/');
				$root = substr($root, 0, $slesh_position);
			}

			$link = $root."/".str_replace('../', "", $link);
		}

		//Если нет http://, значит ссылка относительная
		if (strpos($link, 'http://') === false && strpos($link, 'https://') === false) {
			//Если начинается с /, то пропущен слеш
			if ($link{0} == '/')
			{
				//Если вхождений больше 3х
				if (substr_count($root, '/') > 2) {
					$strrchr = strrpos($root, "/");
					$dir = substr($root, 0, $strrchr);
					$link = $dir.$link;
				} else {
					$link = $root.$link;
				}

			} else {
				//Если вхождений больше 3х
				if (substr_count($root, '/') > 2) {
					$strrchr = strrpos($root, "/");
					$dir = substr($root, 0, $strrchr);
					$link = $dir.'/'.$link;
				} else {
					$link = $root.'/'.$link;
				}
			}
		}

		if (!preg_match("!".self::$root."[\S]*!is", $link)) {
			return ;
		}

		if ($root == $link) {
			return;
		}

		if (!in_array($link, self::$linksArr)) {
			self::$linksArr[] = $link;

			//Ищем еще ссылки
			self::_FindLinks($link);
		}
	}

	private static function _FindLinks($url)
	{
		assert(!empty($url));

		$content = file_get_contents($url);

		self::_AddDoc($url, $content);

		preg_match_all("!<a[^>]*\shref=[\"']?([^>\"'#\s]+)[\"']?[^>]*>!is", $content, $href);
		preg_match_all("!location=[\"']?([^>\"'#\s]+)[\"']?!is", $content, $location);

		$links = array_merge ($href[1], $location[1]);

		foreach($links as $link)
		{
			self::_AddLink($url, $link);
		}
	}

	private static function _AddDoc($url, $content)
	{
		assert(!empty($url));

		if ($content == '') {
			continue;
		}

		// Выбираем заголовок
		preg_match("!<title>([^>]*)?</title>!is", $content, $mashes);

		$title = $mashes[1];

		if (empty($title)) {
			$title = self::$defaultTitle;
		}

		assert(!empty($title));

		// Выбираем только <body>
		preg_match("!<body>(.*)</body>!s", $content, $content);
		
		//Меры безопастности
		$content = mysql_escape_string($content[1]);

		if(empty($content))
		{
			return;
		}
		
		// Записываем данные в мускуль
		$dbWork = DS_MySQLWork::Singleton();
		$dbWork->connect('LOCKING');
		
		// Устанавливаем кодировку
		$sql = "SET NAMES cp1251";
		$dbWork->exec($sql);
		
		$sql = "INSERT INTO Search (url, title, content) VALUES ('".$url."', '".$title."', '".$content."')";
		$dbWork->exec($sql);
		
		echo $url." proccessed<br>";
	}
}

?>