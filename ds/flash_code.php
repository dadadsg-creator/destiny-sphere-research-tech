<?
    global $version, $location, $comb;

    $obj = "<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,65,0\" width=\"" . $width . "\" height=\"" . $height . "\" id=\"ds\">
    <param name=\"movie\" value=\"flash/ds3-".$version.".swf\">
           <PARAM NAME=\"FlashVars\" VALUE=\"vers=$version";

    if ( @$location ) $obj .= "&location=$location";
    $obj .= "&lang=".$_SESSION['shortname']."&id_comb=$comb&race=".$_SESSION['race'].$_SESSION['windowKey']."&mode=$mode&rl=ds_text_logo.swf";
    $obj .= "&ck=".$_SESSION['client_key'];
    $obj .= "&VERS=".constant('VERSION_NAME');
    $obj .= "&chatServer=noname.gamespace.ru";
    if (key_exists('sport', $_SESSION) && $_SESSION['sport'] != -1)
    {
        $obj .= "&sport=".$_SESSION['sport'];
    }
    if ( session_id() ) $obj .= "&".session_name()."=".session_id();

    $obj .= "\"><param name=\"quality\" value=\"high\">
    <param name=\"BGCOLOR\" value=\"#000000\">
    <PARAM NAME=\"menu\" VALUE=\"false\">
    <EMBED src=\"flash/ds3-".$version.".swf\" FlashVars=\"vers=$version";

    if ( @$location ) $obj .= "&location=$location";
    $obj .= "&race=".$_SESSION['race'].@$_SESSION['windowKey'];
    $obj .= "&lang=".$_SESSION['shortname']."&id_comb=$comb&mode=$mode&rl=ds_text_logo.swf";
    $obj .= "&ck=".$_SESSION['client_key'];
    $obj .= "&VERS=".constant('VERSION_NAME');
    $obj .= "&chatServer=noname.gamespace.ru";
    if (key_exists('sport', $_SESSION) && $_SESSION['sport'] != -1)
    {
        $obj .= "&sport=".$_SESSION['sport'];
    }
    if ( session_id() ) $obj .= "&".session_name()."=".session_id();

    $obj .="&quality=\"high\" bgcolor=\"#000000\" WIDTH=\"" . $width . "\" HEIGHT=\"" . $height . "\" NAME=\"ds\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" type=\"application/x-shockwave-flash\"></embed></object>";

    echo $obj;
?>
