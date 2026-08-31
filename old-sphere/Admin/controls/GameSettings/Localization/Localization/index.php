<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Localization><font size=4><b>HELP</b></font></a></p>';
?>
		
<img src="/Admin/img/cms_btn/lang_vis.gif" border="0">
<a href="dictionary.php" ><img src="/Admin/img/cms_btn/trans_non.gif" border="0"></a><br>
<br>
<?


class languages {
	
	var $common;
	
	var $allLangs;
	
	var $langShow = array();
	var $langEdit = array();

	
	function languages( $action = '' ){			
		$this->common = & ClassFactory::getInstance ( "DS_Common" );
		$this->common->setDbId(0);
		
		assert( $this->getAllLangs() );

		switch ( $action ){			
			case 'new':
				assert( $this->showLangForm() );
				break;
				
			case 'insert':
				assert( $this->addNewLang( @$_POST['shortname'], @$_POST['fullname'] ) );
				actionLog( 'Add New Language - '.@$_POST['fullname']);
				break;

			case 'edit':
				assert( $this->showLangForm( @$_GET['id'] ) );
				break;

			case 'update':
				assert( $this->updateLang( @$_POST['id'], @$_POST['shortname'], @$_POST['fullname'] ) );
				actionLog( 'Edit Language - '.@$_POST['fullname']);
				break;
				
			case 'delete':
				assert( $this->deleteLang( @$_GET['id'] ) );
				actionLog( 'Delete Language - '.@$_POST['fullname']);
				break;

			default:
				break;
		}
		
		$this->showAllLangs();
	}

	
	function getAllLangs( ){
		
		$sql = sprintf(
		"
		SELECT
			*
		FROM
			ds_language l
		;"
		);
		
		$res = $this->common->getQueryArray( $sql );
		assert( empty($this->common->errormsg) );
		
		
		foreach ( $res as $row ){
			$this->allLangs[ $row['id_language'] ]['short']	= $row['shortname'];
			$this->allLangs[ $row['id_language'] ]['full'] 	= $row['language'];
		}
		
		return true;
	}
	
	
	function showAllLangs( ){
		echo '<p>
		<table border=0 bgcolor=444E6C cellpadding=8 cellspacing=0>
			<tr>
				<td class=maintxt>
					<a class=sitemapa href="?action=new"><img src="/Admin/img/button-add.gif" border="0"></a>
				</td>
			</tr>
		</table>';
		
		echo '
		<table width="100%" border=0 cellpadding=5 cellspacing=0>
			<tr>
				<th align="left" class="th" width="100">ID</th>
				<th align="left" class="th" width="150">'.local( 'ADMIN_SHORTNAME' ).'</th>
				<th align="left" class="th" width="50%">'.local( 'ADMIN_FULLNAME' ).'</th>
				<th align="left" class="th" width="100">&nbsp;</th>
				<th align="left" class="th" width="100">&nbsp;</th>
			</tr>
		';
		foreach ( $this->allLangs as $id_lang => $names ){
			echo "
			<tr>
				<td class='td'>".$id_lang."</td>
				<td class='td'>".$names['short']."</td>
				<td class='td'>".$names['full']."</td>
				<td class='td'><a class=sitemapa href='?action=edit&id=".$id_lang."'>".local( 'ADMIN_EDIT' )."</a></td>
				<td class='td'><a class=sitemapa href='?action=delete&id=".$id_lang."'>".local( 'ADMIN_DELETE' )."</a></td>
			</tr>
			";
		}
		
		echo '</table>';
		
		return true;
	}

	
	function showLangForm( $id = '' ){

		//Выводим ссылку на помощь
		echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Localization><font size=4><b>HELP</b></font></a></p>';
	
		echo '<table>';
		
		if( $id == '' ){

			$id = 3;
			echo '<form action="?action=insert" method="POST">';
			
		}else{

			echo '
			<form action="?action=update" method="POST">
			<tr>
				<td>ID:</td>
				<td><input type="text" name="id" value="'.$id.'"></td>
			</tr>
			';
		}

		echo '
			<tr>
				<td class=maintxt>'.local('DS_SMALL_NAME').':</td>
				<td><input type="text" name="shortname" value="'.$this->allLangs[$id]['short'].'"></td>
			</tr>
			<tr>
				<td class=maintxt>'.local('DS_FULL_NAME').':</td>
				<td><input type="text" name="fullname" value="'.$this->allLangs[$id]['full'].'"></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" class=Submit name="submit" value="'.local('ADMIN_ADD').'"></td>
			</tr>
		</form>
		</table>
		';
		return true;
	}
	
	
	function addNewLang( $short, $full ){
		assert( $short != '' );
		assert( $full != '' );
		
		$sql = sprintf(
		"
		INSERT INTO ds_language( shortname, language  ) VALUES( '%s', '%s' );
		",
		pg_escape_string( $short ),
		pg_escape_string( $full )
		);
		
		$this->common->executeQuery( $sql );
		assert( $this->common->errormsg );
		
		return true;
	}
	
	function updateLang( $id, $short, $full ){
		assert( $id > 0 );
		assert( $short != '' );
		assert( $full != '' );
		
		$sql = sprintf(
		"
		UPDATE
			ds_language
		SET
			shortname = '%s',
			language = '%s'
		WHERE
			id_language = %d
		;",
		pg_escape_string( $short ),
		pg_escape_string( $full ),
		$id
		);
		
		$this->common->executeQuery( $sql );
		assert( $this->common->errormsg );
		
		return true;
	}
	
	function deleteLang( $id ){
		assert( $id > 0 );
		
		$sql = sprintf(
		"
		DELETE
		FROM
			ds_language
		WHERE
			id_language = %d
		;",
		$id
		);
		
		$this->common->executeQuery( $sql );
		assert( $this->common->errormsg );
		
		return true;
		
	}

}

$new =& new languages( @$_GET['action'] );

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>