<?
// включаем убийство сессии в случае assert или ds_error

require_once 'Main.php';
SU_GameSessionWork::SessionContinue();


if (!$_SESSION['uid'])
{
 die('Ваша сессия истекла');
}
$user_id = $_SESSION['uid'];

$transid = UC_FreeToPay::insertTransaction($user_id);
$User_sex = '';
$User_age = '';
$User_location = '';

$md5str = constant('FREETOPAY_ID').':'.$user_id.':'.$transid.':'.$User_sex.':'.$User_age.':'.$User_location.':'.constant('FREETOPAY_ApiSecretKeyA');

$sig = md5($md5str);
$url = "http://freetopay.ru/ifrm.php?sid=".constant('FREETOPAY_ID').'&uid='.$user_id.'&transac_id='.$transid.'&sex='.$User_sex.'&age='.$User_age.'&location='.$User_location.'&email=&sig='.$sig;
SU_DebugTools::LogAll('md5str='.$md5str.'<br>, url='.$url,'freetopay.log');
header("Location: $url");
?>

