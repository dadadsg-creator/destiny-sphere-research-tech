<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/11/27 15:44:40 $';
function getChar ($code) 
{
    $rusChars = array (
        1071 => 'Я',
        1070 => 'Ю',
        1069 => 'Э',
        1068 => 'Ь',
        1067 => 'Ы',
        1066 => 'Ъ',
        1065 => 'Щ',
        1064 => 'Ш',
        1063 => 'Ч',
        1062 => 'Ц',
        1061 => 'Х',
        1060 => 'Ф',
        1059 => 'У',
        1058 => 'Т',
        1057 => 'С',
        1056 => 'Р',
        1055 => 'П',
        1054 => 'О',
        1053 => 'Н',
        1052 => 'М',
        1051 => 'Л',
        1050 => 'К',
        1049 => 'Й',
        1048 => 'И',
        1047 => 'З',
        1046 => 'Ж',
        1025 => 'Ё',
        1045 => 'Е',
        1044 => 'Д',
        1043 => 'Г',
        1042 => 'В',
        1041 => 'Б',
        1040 => 'А',
        1103 => 'я',
        1102 => 'ю',
        1101 => 'э',
        1100 => 'ь',
        1099 => 'ы',
        1098 => 'ъ',
        1097 => 'щ',
        1096 => 'ш',
        1095 => 'ч',
        1094 => 'ц',
        1093 => 'х',
        1092 => 'ф',
        1091 => 'у',
        1090 => 'т',
        1089 => 'с',
        1088 => 'р',
        1087 => 'п',
        1086 => 'о',
        1085 => 'н',
        1084 => 'м',
        1083 => 'л',
        1082 => 'к',
        1081 => 'й',
        1080 => 'и',
        1079 => 'з',
        1078 => 'ж',
        1105 => 'ё',
        1077 => 'е',
        1076 => 'д',
        1075 => 'г',
        1074 => 'в',
        1073 => 'б',
        1072 => 'а',
    );
    
    if(key_exists($code, $rusChars)){
        return $rusChars[$code];
    }else{
        return Chr($code);
    }
}



function decode ( $string ) 
{

        return  $string;
        
        // декодирует данные, полученные от клиента
        $result = array();

        //$c = 26061963;
        $c = 29121986;
        $code = explode(",", $string);
        
/*
        $first = 10;
        $last = 10;

        $code = array_slice( $code, $first );
        $code = array_reverse( $code );
        $code = array_slice( $code, $last );
*/

        $result = array();
        $cnt = count($code);

        for ( $t=0; $t<$cnt-20; $t++) {
            $result[$t] = getChar ( $code[$cnt-$t-11] ^ $c);
        }

        return implode($result);
};

function code ( $string ) {
        
        $out  = array();
        
        $c = 29121986;
        $first = 10;
        $last = 10;
        
        srand ((double) microtime() * 1000000);
        
        for($i=0; $i < $first; $i++){
            $out[] = @rand ( 0, $c ); 
        }
        
        for($i = strlen($string)-1; $i>=0; $i--){
            $v = $string[$i];
            $out[] = ord($v) ^ $c;
        }
        
        for($i=0; $i < $last; $i++){
            $out[] = @rand ( 0, $c ); 
        }

        return implode(',', $out);
};


?>