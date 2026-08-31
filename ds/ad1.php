<?
    $ads_rate = rand(3, 5);
    
    switch( $ads_rate ){
        case '1':
        default:
            $movie_num = rand(1, 4); ?>
            <SCRIPT language=JavaScript>
            document.write('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="468" height="60" id="header_1" align="middle">');
            document.write('<param name="allowScriptAccess" value="sameDomain">');
            document.write('<param name="movie" value="img/banners/468x60_0'+<? echo $movie_num; ?>+'.swf">');
            document.write('<param name=quality value=high>');
            document.write('<param name="menu" value="false">');
            document.write('<param name="settings" value="false">');
            document.write('<param name="wmode" value="transparent">');
            document.write('<param name="bgcolor" value="#ffffff">');
            document.write('<embed src="img/banners/468x60_0'+<? echo $movie_num; ?>+'.swf" quality="high" wmode="transparent" bgcolor="#ffffff" width="468" height="60" name="header_1" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">');
            document.write('</object>');
            </SCRIPT>
<?
        break;
        case '2': //ads Index20
?>
            <iframe src=http://ad2.rambler.ru/ban.ban?pg=5483&ifr=1&target=_blank frameborder=0 width=468 height=60 marginwidth=0 marginheight=0 scrolling=no> </iframe>
<?
        break;
        case '3': //ads DestinySphere
?>
            <iframe src=http://ds-game.ru/banners/sciense_speedUp2_468_60_unlim.swf frameborder=0 width=468 height=60 marginwidth=0 marginheight=0 scrolling=no> </iframe>
<?      
            
        break;
        /*case '4': //ads DestinySphere
?>
            <iframe src="http://ds.rambler.ru/banners/468x60_ValDay_2.swf?link=http://ds-game.ru/?p=news%26pubId=1357%26pubMode=showPub" frameborder=0 width=468 height=60 marginwidth=0 marginheight=0 scrolling=no> </iframe>
<?      
            
        break;*/
    
        default:
?>
            <iframe src=http://ds-game.ru/banners/468_60_cat_unlim.swf frameborder=0 width=468 height=60 marginwidth=0 marginheight=0 scrolling=no> </iframe>
<?      
        break;      
    }
 ?>