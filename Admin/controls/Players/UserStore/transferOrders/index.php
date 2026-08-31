<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

$common = DS_Common::CreateForCurentDB(0);

if ($_REQUEST['delete']) {
    $sql = "delete from ds_worldtransfer where id_worldtransfer=".(int)$_REQUEST['delete'];
    $common->executeQuery($sql);
}

$sql = "select wt.*, u.username, wc1.worldname as worldname_from, wc2.worldname as worldname_to
    from ds_worldcollection wc1, ds_worldcollection wc2, ds_user u, ds_worldtransfer wt
    where wt.id_user=u.id_user
    and wt.id_world_from=wc1.id_world and wt.db_from=wc1.dbname
    and wt.id_world_to=wc2.id_world and wt.db_to=wc2.dbname
    order by wt.transfer_date";
$res = $common->GetResultArray("", $sql);

foreach ($res as $key=>$row) {
    $sql = "select 1 from ds_userinstancecollection
        where id_user={$row['id_user']} and id_world={$row['id_world_to']} and dbname='{$row['db_to']}'";
    $res2 = $common->GetResultArray("", $sql);
    $res[$key]['done'] = count($res2);
}

?>
<style>
td, th {color: white;}
</style>
<table cellspacing="0" cellpadding="4" border="1">
    <th>User</th>
    <th>From</th>
    <th>To</th>
    <th>Done</th>
    <th>Date</th>
    <th>Action</th>
<? foreach ($res as $row) { ?>
    <tr>
        <td><a href="../transfer?user_name=<?=$row['username']?>"><?=$row['username']?></a></td>
        <td><?=$row['worldname_from']?></td>
        <td><?=$row['worldname_to']?></td>
        <td><?=$row['done']?'v':''?></td>
        <td><?=$row['transfer_date']?></td>
        <td><a href="?delete=<?=$row['id_worldtransfer']?>" onClick="return confirm('Are you sure?')">delete</a></td>
    </tr>
<? } ?>
</table>
