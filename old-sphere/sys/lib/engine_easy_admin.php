<?
/*function getmicrotime(){ 
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
    } 
$start_time =  getmicrotime();*/

require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/templates.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/PageManagerBase.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/PageManager5.php";

ob_start();
if ($mode == "vis_admin") {
    headerLocation("/?p=".$p);
};
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">

<html>
<head>
<title>Административная консоль</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
</head>
<body>

<script language="JavaScript">
    function openWin(url){
       var ds_win = window.open(url,'ds_win', 'resizable=yes,scrollbars=yes,menubar=no,height=600,width=800');
       ds_win.focus();
    }
    var preloadFlag = false;
    function newImage(arg) {
        if (document.images) {
            rslt = new Image();
            rslt.src = arg;
            return rslt; 
        }
    }
    function changeImages(img, file) {
        if (document.images && (preloadFlag == true)) {
            img.src = file;
        }
    }
    function showBorder(n) {
        eval('d2_'+n+'.style.display="block"');
        eval('d3_'+n+'.style.display="block"');
        eval('d4_'+n+'.style.display="block"');
        eval('d5_'+n+'.style.display="block"');
        eval('button_'+n+'.style.display="block"');
    }
    function hideBorder(n) {
        eval('d2_'+n+'.style.display="none"');
        eval('d3_'+n+'.style.display="none"');
        eval('d4_'+n+'.style.display="none"');  
        eval('d5_'+n+'.style.display="none"');  
        eval('button_'+n+'.style.display="none"');  
    }
    function callDialog() {
        d = showModelessDialog("",window,"status:false;dialogWidth:300px;dialogHeight:300px");
        alert(d.window);
    }

    newImage("/Admin/img/botton-text-down.gif"); 
    newImage("/Admin/img/botton-service-down.gif"); 
    preloadFlag = true;
</script>
    
<?
if (!isset($p)) $p = $default_page;
$P = new PageManager(array("Mode"=>"EasyAdmin","PageID"=>$p));
if (isset($P)) {
    $P->ApplyTemplate(1,"main");
} else {
    echo "<br>Страница не обработана";
}
//echo "<br><center><small>Скрипт выполнен за ".round (getmicrotime()-$start_time,3)." сек.</small></center>";
?>

</body>
</html>
<?
ob_end_flush();
?>
