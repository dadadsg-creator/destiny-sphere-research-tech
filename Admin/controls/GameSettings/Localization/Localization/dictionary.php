<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
error_reporting(E_ERROR);

?>
<a href="index.php" class=maintxt><img src="/Admin/img/cms_btn/lang_non.gif" border="0"></a>
<img src="/Admin/img/cms_btn/trans_vis.gif" border="0"><br>
<br>
<?

class dictionary 
{
	
	var $common;
	
	var $allLangs;
	var $text;
	
	var $limit = 0;

	var $offset = 0;
	var $page = 1;
	
	var $searchPattern = '';
	var $search = '';
	var $search2 = '';

	var $sortPattern = '';
	var $sortOrder = '';
	
	var $numrows;
	
	var $langShow = array();

	
	function dictionary( $action = '' )
	{

		
		$this->common = &ClassFactory::getInstance ( "DS_Common" );
		$this->common->setDbId(0);
		
		assert($this->getAllLangs());
		assert($this->setLimit($_GET['limit']));
		assert($this->setPage($_GET['page']));
		assert($this->setSortOrder( $_GET['sort']));
		
		if (isset($_REQUEST['showlang']))
		{
			$this->langShow = $this->getLangIdByName($_REQUEST['showlang']);
		} else {
			if(isset($_SESSION['showlang']))
			{
				assert($this->langShow = $_SESSION['showlang']);
			}else{
				assert($this->langShow = $this->getLangIdByName(''));
			}			
		}
		

		
		$_SESSION['showlang'] = $this->langShow;
				
		if(isset($_GET['search']))
		{
			assert($this->setSearch('text', $_GET['search']));
		}else{
			assert($this->setSearch());
		}
		

		switch ( $action )
		{

			case 'new':
				assert( $this->showTextForm() );
				break;
			case 'insert':
				actionLog('Add New Localization Text');
				assert($this->addNewText());
				break;
// -------------------------------------------
			case 'edit':				
				assert($this->showTextForm($_GET['id']));
				actionLog('Edit Localization Text '.$_GET['id']);
				break;
			case 'update':
				assert( $this->updateText( ) );
				actionLog( 'Edit Localization Text - '.$_GET['id'] );
				break;
// -------------------------------------------
			case 'delete':
				assert( $this->deleteText( $_GET['id'] ) );
				actionLog( 'Delete Localization Text - '.$_GET['id'] );
				break;
// -------------------------------------------

			case 'setparams':
				assert( $this->setLimit( @$_POST['limit'] ) );
				assert( $this->setPage( 1 ) );
				assert( $this->setSortOrder( $_POST['sort'] ) );
				assert( $this->langShow = $this->getLangIdByName( @$_POST['showlang'] ) );
				if( !empty( $_POST['search'] ) ){
					assert( $this->setSearch( 'text', $_POST['search'] ));
				}

			default:
				$this->getLocalizationText();
				
				assert( $this->showPanel() );
				assert( $this->showText() );
				assert( $this->showPages() );
				break;
		}
	}

	
	function getAllLangs( ){
		
		$sql = sprintf(
		"
		SELECT
			*
		FROM
			ds_language l
		ORDER BY
			id_language DESC
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
	
	
	function getLangIdByName( $langName = '' ){
		
		$sql = sprintf(
		"
		SELECT
			l.id_language
		FROM
			ds_language l
		WHERE
			l.language = '%s'
			OR l.shortname = '%s'
			OR l.id_language = '%s'
		;",
		pg_escape_string( $langName ),
		pg_escape_string( $langName ),
		pg_escape_string( (int)$langName )
		);
		
		$res = $this->common->getQueryArray( $sql );
		assert( empty($this->common->errormsg) );
		
		return ( isset($res[0]['id_language']) ? $res[0]['id_language'] : 3 );
	}
	
	
	function getBaseHref( $page = true ){
		
		$out = array();
		
		if (!empty($this->limit)){
			
			$out[] = "limit=".$this->limit;
		}
		
		if (!empty($this->langShow)){
			
			$out[] = "showlang=".$this->langShow;
		}

		if (!empty($this->sortPattern)){
			
			$out[] = "sort=".$this->sortPattern;
		}
		
		if (!empty($this->page) && $page){
			
			$out[] = "page=".$this->page;
		}

		return implode( "&", $out);
	}

	
	function setLimit( $num = '' ){
				
		$this->limit = ( $num > 0 )? $num : 25;
		
		return true;
	}
	
	
	function setPage( $page = '' ){
				
		$this->page = ( $page > 0 ) ? $page : 1;
		
		$this->offset = (($this->page - 1) * $this->limit);
		
		return true;
	}
	

	function setSearch( $pattern = '', $params ='')
	{
		
		
		switch($pattern)
		{
			case 'id' :
				$this->searchPattern = 'id';
				$this->search = "AND ltl2.id_localizedtext = '".pg_escape_string($params)."'";				
				break;
			
			case 'text' :
				$this->searchPattern = 'text';
				$this->search = "AND ltl2.text ilike '%%".pg_escape_string($params)."%%' AND ltl2.id_language = ".$this->langShow;				
				break;
					
			default :
				$this->searchPattern = "";
				$this->search = "AND ltl2.id_language = ".$this->langShow;				
				break;
		}
		
		return true;
	}
	
		
	function setSortOrder( $pattern = '' ){
		
		$this->sortPattern = $pattern;
		
		switch ( strtolower($this->sortPattern) ){
			
			case 'text' :
				$this->sortOrder = "ltl2.text,";
				break;

				case 'id' :
			default :
				$this->sortOrder = "ltl2.id_localizedtext,";
				break;
		}
		
		return true;
	}
	
	function getLocalizationText()
	{
		
		$sql =" SELECT
           			count(*) as cnt
           		FROM
         			ds_localizedtextlanguage ltl1
         			LEFT JOIN ds_localizedtextlanguage ltl2 ON (ltl1.id_localizedtext = ltl2.id_localizedtext ".$this->search.")
         		WHERE
             			ltl1.id_language = 3";		
		$res = $this->common->getQueryArray( $sql );
		assert( empty($this->common->errormsg) && count($res)>0);

		$this->numrows = $res[0]['cnt'];

		$sql = "
			SELECT
      			ltl.id_localizedtext,
	  			ltl.id_language,
	  			ltl.textru,
	  			ltl.textlang
			FROM
			(
  				SELECT
	  				ltl1.id_localizedtext,
	  				ltl2.id_language,
	  				ltl1.text as textru,
	  				ltl2.text as textlang
  				FROM
     				ds_localizedtextlanguage ltl1
     				LEFT JOIN ds_localizedtextlanguage ltl2 ON (ltl1.id_localizedtext = ltl2.id_localizedtext ".$this->search.")
  				WHERE
     				ltl1.id_language = 3     				
  				ORDER BY
  					".$this->sortOrder."
     				ltl2.id_localizedtext
			) ltl
			LIMIT
			".$this->limit."
			OFFSET
			".$this->offset."";

		$res = $this->common->getQueryArray($sql);
		assert(empty($this->common->errormsg));
		
		if(count($res) == 0)
		{		
			return false;
		}
		
		foreach($res as $k=>$v)
		{		
			$this->text[$v['id_localizedtext']]['ru'] = $v['textru'];
			$this->text[$v['id_localizedtext']]['lang'] = $v['textlang'];
		}

		return true;
	}
	
	function showPanel(){
		echo '
		<form action="?action=setparams" method="POST">
		<table width="80%" border=0 cellpadding=5 cellspacing=0>
			<tr>
				<th align="left" class="th">'.local( 'ADMIN_SEARCH' ).':</th>
				<th align="left" class="th">'.local( 'ADMIN_LANGUAGE' ).':</th>
				<th align="left" class="th">'.local( 'ADMIN_SORT_BY' ).':</th>
				<th align="left" class="th">'.local( 'ADMIN_LIMIT' ).':</th>
				<th align="left" class="th"></th>
			</tr>
			<tr bgcolor=#444E6C>
				<td class="maintxt">
					<input type="text" name="search" value="">
				</td>
				<td class="maintxt">
					<select name="showlang">
		';
		
		foreach ( $this->allLangs as $langId => $langNames ){
			echo '
						<option value="'.$langNames['short'].'" '.($langId == $this->langShow ? 'selected' : '').'>'.$langNames['full'].'</option>';
		}
		
		echo '
					</select>
				</td>
				<td class="maintxt">
					<select name="sort">
						<option value="id" selected >ID</option>
						<option value="text" >'.local( 'ADMIN_TEXT' ).'</option>
					</select>
				</td>
				<td class="maintxt">
					<input type="text" name="limit" value="'.$this->limit.'">
				</td>
				<td class="maintxt">
					<input type="submit" name="submit" class="submit" value="'.local( 'ADMIN_APPLY' ).'">
				</td>
			</tr>
		</table>
		</form>
		';
		
		return true;
	}
	
	
	function showPages( ){

		$allpages = ceil( $this->numrows / $this->limit );
		$links = array();
		$out = '';
		
		for ( $i=1; $i < ($allpages+1); $i++ ){
			
			if( $i == $this->page ){
				
				$links[] = "<span class=select_txt>[<b>".$i."</b>]</span>";

			}else{
				
				$links[] = '<a href="?'.$this->getBaseHref(false).'&page='.$i.'" class=maintxt>'.$i.'</a>';
			}
			
			if( ($i % 20) == 0){
				
				$out .= '
				<tr>
					<td align=center class="th">
						<span class=maintxt>'.implode( ",&nbsp;&nbsp;&nbsp; ", $links).'</span>
					</td>
				</tr>
				';
				
				$links = array();
			}
		}
		
		echo '
		<br/>
		<table width=100% cellspacing=0 cellpadding=5 border=0>
		'.$out.'
		</table>
		';
		
		return true;
	}
	

	function showText( ){
		
		echo '
		<p>
		<table border=0 bgcolor=444E6C cellpadding=5 cellspacing=0>
			<tr>
				<td class=maintxt>
					<a class=sitemapa href="?action=new"><img src="/Admin/img/button-add.gif" border="0"></a>
				</td>
			</tr>
		</table>';
		
		if(empty($this->text))
		{			
			echo local( 'DS_ACTION_NOTHING_FOUND' );			
		}else{
			
			echo '
			<table width="100%" border=0 cellpadding=5 cellspacing=0>
				<tr>
					<th align="left" class="th">ID</th>
					<th align="left" class="th">'.local( 'ADMIN_TEXT' ).'</th>
					<th align="left" class="th">'.local( 'ADMIN_TEXT' ).'</th>
					<th colspan="2" align="left" class="th">'.local('ADMIN_ACTIONS').'</th>
				</tr>
			';
	
			foreach ( $this->text as $id_text => $text)
			{
				$textRu = $text['ru'];
				
				if($this->langShow == 3)
				{
					$textLang = "";
				}else{
					$textLang = $text['lang'];
				}
				
				echo '
					<tr>
						<td class="td" width="20">'.$id_text.'</td>
						<td class="td" width="40%">'.$textRu.'</td>
						<td class="td" width="40%">'.$textLang.'</td>
						<td class="td" width="20"><a href="?'.$this->getBaseHref().'&action=edit&id='.$id_text.'"><img src="/Admin/img/knopka_edit.gif" border="0" alt="'.local( 'ADMIN_EDIT' ).'"></a></td>
						<td class="td" width="20"><a href="?'.$this->getBaseHref().'&action=delete&id="'.$id_text.'"><img src="/Admin/img/knopka_del.gif" border="0" alt="'.local( 'ADMIN_DELETE' ).'"></a></td>
					</tr>
				';			
				
			}
			
			echo '</table>';
		}
		
		return true;
	}
	
	
	function showTextForm($id = '')
	{
		if(!empty($id))
		{
			$sql ="SELECT
					*
			   FROM
					ds_localizedtextlanguage
			   WHERE
			   		id_localizedtext = ".$id;		
			$res = $this->common->getQueryArray( $sql );
			assert( empty($this->common->errormsg) && count($res)>0);
			
			foreach($res as $k=>$v)
			{
				$data[$v['id_language']] = $v['text'];
			}
		}
		
		
		if( $id == '' )
		{

			$id = 3;
			echo '
			<form action="?'.$this->getBaseHref().'&action=insert" method="POST">
			';			
		}else{

			echo '
		<table width="100%" border=0 cellpadding=10 cellspacing=0>
			<form action="?'.$this->getBaseHref().'&action=update" method="POST">
			<tr>
				<th class="th">ID:</th>
				<th class="th"><input type="hidden" name="id" value="'.$id.'">'.$id.'</th>
			</tr>
		</table>
			';
		}
		
		echo '
		<table width="100%" border=0 cellpadding=10 cellspacing=0>
			<tr>
				<th align="left" class="th">'.local( 'ADMIN_LANGUAGE' ).'</th>
				<th align="left"  class="th">'.local( 'ADMIN_TEXT' ).'</th>
			</tr>
		';
		
		foreach ( $this->allLangs as $id_lang => $langNames )
		{
			
			echo '
			<tr>
				<td class="td">'.$langNames['full'].'</td>
				<td class="td"><textarea name="text_'.$langNames['short'].'" rows=8 cols=50>'.@$data[$id_lang].'</textarea></td>
			</tr>
			';
		}

		echo '
			<tr>
				<td ></td>
				<td ><input type="submit" name="submit" value="'.local( 'ADMIN_SAVE' ).'"></td>
			</tr>
		
		</form>
		</table>
		';
		
		return true;
	}
	
	function updateText( ){
		assert( @$_POST['id'] > 0 );
		
		$this->common->setDbId(0);
		
		foreach ( $this->allLangs as $id_lang => $langNames ){
			
			if( @$_POST[ "text_".$langNames['short'] ] == '' ){
				continue;
			}
			
			$sql = sprintf(
			"
			SELECT
				*
			FROM
				ds_localizedtextlanguage
			WHERE
				id_language = %d
				AND id_localizedtext = %d
			;",
			$id_lang,
			$_POST['id']
			);
			$res = $this->common->getQueryArray( $sql );
			assert(empty($this->common->errormsg));
			
			if( count( $res ) < 1 ){

				$sql = sprintf(
				"
				INSERT INTO
					ds_localizedtextlanguage (  id_language, id_localizedtext, text )
				VALUES
				( '%d', '%d', '%s'  )
				;",
				$id_lang,
				$_POST['id'],
				pg_escape_string( @$_POST[ "text_".$langNames['short'] ] )
				);
				$this->common->executeQuery( $sql );
				assert(empty($this->common->errormsg));
				
			}else{
				
				$sql = sprintf(
				"
				UPDATE
					ds_localizedtextlanguage
				SET
					text = '%s'
				WHERE
					id_language = %d
					AND id_localizedtext = %d
				;",
				pg_escape_string( @$_POST[ "text_".$langNames['short'] ] ),
				$id_lang,
				$_POST['id']
				);
				$this->common->executeQuery( $sql );
				assert(empty($this->common->errormsg));
			}
		}
		
		echo local( 'ADMIN_SAVED' ).'.<br/> <a href="dictionary.php?'.$this->getBaseHref().'" >'.local( 'ADMIN_TRANSLATIONS' ).'</a>';

		return true;
	}
	
	function addNewText( ){

		$this->common->setDbId(0);

		$sql = sprintf(
		"
		SELECT 
			MAX(id_localizedtext) as id_localizedtext
		FROM
    		ds_localizedtext
		;
		");			
		$res = $this->common->getQueryArray( $sql );
		assert(empty($this->common->errormsg));
		
		$id_localizedtext = $res[0]['id_localizedtext'] + 1;

		$sql = sprintf(
		"
		INSERT INTO 
			ds_localizedtext (id_localizedtext)
		VALUES('%d');
		",
		$id_localizedtext
		);
		$this->common->executeQuery( $sql );
		assert(empty($this->common->errormsg));
		

		foreach ( $this->allLangs as $id_lang => $langNames ){
			
			if( @$_POST[ "text_".$langNames['short'] ] == '' ){
				
				continue;
			}
			
			$sql = sprintf(
			"
			INSERT INTO
				ds_localizedtextlanguage (  id_language, id_localizedtext, text )
			VALUES
			( '%d', '%d', '%s'  )
			;",
			$id_lang,
			$id_localizedtext,
			pg_escape_string( @$_POST[ "text_".$langNames['short'] ] )
			);			
			$this->common->executeQuery( $sql );
			assert(empty($this->common->errormsg));
		}
		
		echo local( 'ADMIN_SAVED' ).'.<br/> <a href="dictionary.php?'.$this->getBaseHref().'" >'.local( 'ADMIN_TRANSLATIONS' ).'</a>';

		return true;
	}
	
	function deleteText( $id ){
		assert( $id > 0 );
		
		$sql = sprintf(
		"
		DELETE
		FROM
			ds_localizedtextlanguage
		WHERE
			id_localizedtext = %d
		;
		",
		$id
		);
		$this->common->executeQuery( $sql );
		assert( $this->common->errormsg );
		
		$sql = sprintf(
		"
		DELETE
		FROM
			ds_localizedtext
		WHERE
			id_localizedtext = %d
		;
		",
		$id
		);
		$this->common->executeQuery( $sql );
		assert( $this->common->errormsg );
		
		echo local( 'ADMIN_DELETED' ).'.<br/> <a href="dictionary.php?'.$this->getBaseHref().'" >'.local( 'ADMIN_TRANSLATIONS' ).'</a>';
		
		return true;
	}
}

$new =& new dictionary( $_GET['action'] );

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>