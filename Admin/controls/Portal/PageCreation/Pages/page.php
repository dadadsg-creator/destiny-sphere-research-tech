<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

$ID = $_REQUEST['ID'];

$conn_id = getMysqlConnection();
if ($ID) {
    $query = "SELECT P.Title, AT.ID, AT.Title FROM Pages P LEFT JOIN AssemblyTemplate AT on P.AssemblyTemplate_id=AT.id WHERE P.ID='$ID'";
    $result = @mysql_db_query(DBName,$query,$conn_id);
    if ($result) {
        if ($row = mysql_fetch_row($result)) {
            $PageTitle = $row[0];
            $AssemblyTemplateID = $row[1];
            $TemplateTitle = $row[2];
        } else $err = local('ADMIN_PAGE_NOT_FOUND_IDENT', $ID);
    } else $err = local('ADMIN_DATABASE_ACCESS_ERROR');
} else $err = local('ADMIN_PAGE_NOT_SET_ID');

?>

<center><h3><?=($preview==1) ? local('ADMIN_PAGE_PREVIEW') : local('ADMIN_ASSIGNING_ELEMENTS_PAGES');?></h3></center>
<script language="JavaScript">
    function Edit() {
        frm = document.getElementById("DocIFRAME");
        frm.src='/t/3/index.php?AssemblyTemplateID=<?=$AssemblyTemplateID?>&p=<?=$ID?>';
    }
    function View() {
        frm = document.getElementById("DocIFRAME");
        frm.src='/t/3/view.php?p=<?=$ID?>';
    }
</script>
<table width="100%"><tr>
    <td width="30%">
        <table>
            <tr><td>
                    <a href="index.php"><?=local('ADMIN_RETURN_TO_PAGES')?></a>
                </td>
            </tr>
            <tr><td class=maintxt>
                    <?=($preview == 1) ? "<a href=\"?ID=$ID\">".local('ADMIN_ENABLE_EDIT_MODE')."</a>" : "<a href=\"?ID=$ID&preview=1\">".local('ADMIN_VIEW_MODE')."</a>";?>
                </td>
            </tr>
        </table>
    </td>
    <td>
        <table>
            <tr><td width="100" maintxt><?=local('ADMIN_PAGE')?>:</td>
                <td><?echo ($PageTitle) ? "<b>".$PageTitle."</b>" : "<font color=red><b>Error:</b></font>&nbsp;&nbsp;".$err;?></td>
            </tr>
            <tr><td width="100" maintxt><?=local('ADMIN_MODEL')?>:</td>
                <td><?echo ($TemplateTitle) ? "<b>".$TemplateTitle."</b>" : "<font color=red>".local('ADMIN_NOT_SET')."</font>";?></td>
            </tr>
        </table>
    </td>
</tr></table>

<?if (!$err && $TemplateTitle) {
    $work_dir = ($LanguageID == "en") ? "/eng/" : "/";
?>

<IFRAME id="DocIFRAME" src="<?=($preview==1) ? $work_dir."?p=$ID" : $work_dir."easyadmin.php?p=$ID";?>&<?=session_name()."=".session_id()?>" width="100%" height="800" scrolling="auto" frameborder="1">
  [<? local('ADMIN_DONT_SUPPORT_IFRAME_MESSAGE'); ?>]
</IFRAME>
<?}?>

<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>