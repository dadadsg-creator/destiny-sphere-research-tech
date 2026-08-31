<?php $CVS_Version = '$Revision$ ; $Date$';

setlocale(LC_CTYPE, 'ru_RU.koi8r');

/**
 * Из названия и списка слов и так понятно что оно делает
 *
 */
class GB_AntiMatFilter
{

    /**
     * Краткий словарь мата
     *
     * @var array
     */
    static private $words = array(
            'medievalage',
            'сука',
            'ебало',
            'хуй',
            'нахуй',
            'похуй',
            'хуйня',
            'хуевина',
            'хуесос',
            'пизда',
            'пиздец',
            'пиздуй',
            'пиздобол',
            'пиздоболина',
            'пиздоболиха',
            'пиздатый',
            'пиздатая',
            'пиздатое',
            'пидор',
            'пидорас',
            'поеботина',
            'поебать',
            'еботня',
            'ебало',
            'ебальник',
            'ебаный',
            'разебный',
            'medieval',
            'evalage',
            'cgi-bin'
        );

    public static function Exec($string)
    {
        if (self::Detect($string))
        {
            $string = self::Replace($string);
        }
        return $string;
    }

    public static function Replace($string)
    {
        foreach (self::$words as $key => $value)
        {

            $patterns[] = "/".addslashes($value)."/i";
        }

        $replacements = "*****";

        return preg_replace($patterns, $replacements, $string);
    }

    public static function Detect($string)
    {
        foreach (self::$words as $key => $value)
        {
            if (preg_match("/".$value."/i", $string))
            {
                return true;
            }
        }


        return false;
    }

}

?>