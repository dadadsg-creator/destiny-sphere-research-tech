<?PHP

session_start ();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );

	if ( !$common->setDbId(0) ) {

		echo "DB-connection failed";

		exit;

	}
}

class ShowTechData {

	var $errormsg;
	var $id_user;
	var $language;

	function ShowTechData () {
		
		global $common;

		if ( ! ( isset ( $_GET['id_user'] ) || isset ( $_POST['id_user'] ) ) ) {
		
			echo "usage: showInvoice?id=XXX";
			return true;				
		}
		
		if ( isset ( $_GET['id_user'] ) ) $this->id_user = $_GET['id_user'];
		if ( isset ( $_POST['id_user'] ) ) $this->id_user = $_POST['id_user'];

		$sql = sprintf ( "select l.shortname
				from ds_user u,
				ds_language l
				where id_user = %d
				and u.id_language = l.id_language",
				$this->id_user );
				
		$res = $common->getResultArray ( "id_language_id_user", $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg = "SE ShowInvoice::ShowInvoice1";
			return false;
		}
		
		$this->language = $res[0]['shortname'];

		if ( !$this->getPostData ( $_POST ) ) {
			echo $this->errormsg;
		}
	}

	function getPostData ( $data ) {

		global $common;

		if ( sizeof ( $data ) == 0 ) {

			if ( !$this->show () ) {
				return false;
			}

		} else if ( isset ( $data['edit'] ) ) {

			$common->executeQuery ( "begin" );

			$sql = sprintf ( "update ds_userprofitechdata
					set id_techos = %d,
					id_techbrowser = %d,
					id_techram = %d,
					id_techcpu = %d,
					id_techflash = %d,
					id_techinternet = %d
					where id_user = %d",
					$data['os'],
					$data['browser'],
					$data['ram'],
					$data['cpu'],
					$data['flash'],
					$data['internet'],
					$this->id_user );

			$res = $common->executeQuery ( $sql );
			
			if ( $res === false ) {
				
				$this->errormsg = "showTechData::getPostData1";
				return false;
			}

			$common->executeQuery ( "commit" );

			$str = "Location: showTechData.php?id_user=$this->id_user";
			
			if ( SID ) $str .= "&".SID;
			
			header ( $str );
		}
		
		return true;
	}

	function show () {

		global $common;
		
		$sql = sprintf ( "select *
				from ds_userprofitechdata
				where id_user = %d",
				$this->id_user );

		$res = $common->getResultArray ( "profithechdata", $sql, "", "", "" );
		if ( $res === false ) {
			$this->errormsg = "SE Page3:showFirstPage3";
			return false;
		}

		echo "<table border='1' cellpadding='10' cellspacing='0'>
		<form method='post' action='showTechData.php'>
		<tr><td>OS:</td><td>";

		$sql = sprintf ( "select * from ds_userprofitechos order by id_techos" );

		$res2 = $common->getResultArray ( "profithechos", $sql, "", "", "GLOBAL_CACHE" );
		if ( !$res2 ) {
			$this->errormsg = "SE Page3:showFirstPage2";
			return false;
		}

		echo "<select name='os'>";

		for ( $i=0; $i<sizeof ( $res2 ); $i++ ) {
			
			echo "<option value='".$res2[$i]['id_techos']."'";
			
			if ( $res2[$i]['id_techos'] == $res[0]['id_techos'] ) echo " selected";

			echo ">".$res2[$i]['os'];			
		}

		echo "</select></td></tr>";

		$sql = sprintf ( "select * from ds_userprofitechbrowser order by id_techbrowser" );

		$res2 = $common->getResultArray ( "profithechbrowser", $sql, "", "", "GLOBAL_CACHE" );
		if ( !$res2 ) {
			$this->errormsg = "SE Page3:showFirstPage3";
			return false;
		}

		echo "<tr><td>Browser:</td><td><select name='browser'>";

		for ( $i=0; $i<sizeof ( $res2 ); $i++ ) {
			
			echo "<option value='".$res2[$i]['id_techbrowser']."'";
			
			if ( $res2[$i]['id_techbrowser'] == $res[0]['id_techbrowser'] ) echo " selected";

			echo ">".$res2[$i]['browser'];			
		}

		echo "</select></td></tr>";
				
		$sql = sprintf ( "select * from ds_userprofitechcpu order by id_techcpu" );

		$res2 = $common->getResultArray ( "profithechcpu", $sql, "", "", "GLOBAL_CACHE" );
		if ( !$res2 ) {
			$this->errormsg = "SE Page3:showFirstPage4";
			return false;
		}
		
		echo "<tr><td>CPU:</td><td><select name='cpu'>";

		for ( $i=0; $i<sizeof ( $res2 ); $i++ ) {

			echo "<option value='".$res2[$i]['id_techcpu']."'";
			
			if ( $res2[$i]['id_techcpu'] == $res[0]['id_techcpu'] ) echo " selected";

			echo ">".$res2[$i]['cpu'];			
		}

		echo "</select></td></tr>";

		$sql = sprintf ( "select * from ds_userprofitechram order by id_techram" );

		$res2 = $common->getResultArray ( "profithechram", $sql, "", "", "GLOBAL_CACHE" );
		if ( !$res2 ) {
			$this->errormsg = "SE Page3:showFirstPage5";
			return false;
		}
		
		echo "<tr><td>RAM:</td><td><select name='ram'>";

		for ( $i=0; $i<sizeof ( $res2 ); $i++ ) {
			
			echo "<option value='".$res2[$i]['id_techram']."'";
			
			if ( $res2[$i]['id_techram'] == $res[0]['id_techram'] ) echo " selected";

			echo ">".$res2[$i]['ram'];
		}

		echo "</select></td></tr>";

		$sql = sprintf ( "select * from ds_userprofitechinternetspeed order by id_techinternet" );

		$res2 = $common->getResultArray ( "profithechinternet", $sql, "", "", "GLOBAL_CACHE" );
		if ( !$res2 ) {
			$this->errormsg = "SE Page3:showFirstPage6";
			return false;
		}
		
		echo "<tr><td>Internet:</td><td><select name='internet'>";

		for ( $i=0; $i<sizeof ( $res2 ); $i++ ) {
			
			echo "<option value='".$res2[$i]['id_techinternet']."'";
			
			if ( $res2[$i]['id_techinternet'] == $res[0]['id_techinternet'] ) echo " selected";

			echo ">".$res2[$i]['internetspeed'];
		}

		echo "</select></td></tr>";

		$sql = sprintf ( "select * from ds_userprofitechflash order by id_techflash" );

		$res2 = $common->getResultArray ( "profithechflash", $sql, "", "", "GLOBAL_CACHE" );
		if ( !$res2 ) {
			$this->errormsg = "SE Page3:showFirstPage7";
			return false;
		}
		
		echo "<tr><td>Flash:</td><td><select name='flash'>";

		for ( $i=0; $i<sizeof ( $res2 ); $i++ ) {
			
			echo "<option value='".$res2[$i]['id_techflash']."'";
			
			if ( $res2[$i]['id_techflash'] == $res[0]['id_techflash'] ) echo " selected";

			echo ">".$res2[$i]['flashversion'];
		}

		echo "</select></td></tr>";
		echo "<tr><td colspan=2><input type=hidden name='id_user' value='$this->id_user'>";
		echo "<input type=submit name='edit' value=' Edit '>";
		echo "</form></table>";

	}
}

$std = new ShowTechData ();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>