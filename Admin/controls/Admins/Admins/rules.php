<?php
require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/tree.class.php";
$inc = new IncludeManager();

class RulesEdit
{
	var $db = '';
	
	var $tree = '';
	
	var $userAllows = array();
	
	var $userID = 0;
	
	function RulesEdit($id)
	{
		$this->db = new DBinterface (	
							constant('DB_HOST'),
    						constant('DB_USER'),
                            constant('DB_PASS'),
                            constant('DB_NAME'));
                            
        $this->tree = new tree("root","");
        $this->create_pages_tree(0,$str);
        
        $this->userID = $id;        
        $this->setUserAllows();
	}
	
	function main ()
	{
		if($_REQUEST['action2'] == 'update'){
			return $this->updateRules();
		}else{
			$GLOBALS['NO_OUTPUT_FLAG'] = true;
			return $this->outForm();
		}
		
		
	}
	
	function outForm()
	{
		$sql = "SELECT * FROM Users WHERE ID = ".$this->userID;
		$rs = $this->db->query($sql);
		
		$out = "–едактирование прав дл€ пользовател€: <b>".$rs[0]['FirstName']." ".$rs[0]['SecondName']." ".$rs[0]['LastName']."</b>";
		
		$out .= "<form action='' method='POST'>";
		$out .= $this->tree->show_checkboxes('menus', $this->userAllows);	
		$out .= "<input type='hidden' name='action' value='rules'>";
		$out .= "<input type='hidden' name='action2' value='update'>";
		$out .= "<input type='submit'>";
		$out .= "</form>";
		return $out;
	}
	
	function updateRules()
	{
		$sql = "DELETE FROM Users_AdminMenu WHERE User_ID = ".$this->userID;
		$rs = $this->db->query($sql);

		$sql = "SELECT * FROM Users WHERE ID = ".$this->userID;
		$rs = $this->db->query($sql);
		
		$out = "Edit user permissions: <b>".$rs[0]['Login']."</b>";
		
		if(is_array($_REQUEST['menus'])){
			foreach($_REQUEST['menus'] as $k=>$v){
				$sql = "INSERT INTO Users_AdminMenu 
						(User_ID, Menu_ID)
						VALUES
						(".$this->userID.", ".$v.")";						
				$rs = $this->db->query($sql);
			}
		}
		
		actionLog($out);
	}
	
	function setUserAllows()
	{
		$sql = "SELECT * FROM Users_AdminMenu WHERE User_ID = ".$this->userID;
		$rs = $this->db->query($sql);
		if(is_array($rs))
		{
			foreach($rs as $k=>$v)
			{
				 $this->userAllows[] = $v['Menu_ID'];
			}
		}
	}
	
	function create_pages_tree($start_page_id,$ref="") 
	{
		$query = "SELECT * FROM AdminMenu  WHERE Parent_ID = '$start_page_id' ORDER BY ShowOrder";
		$rs = $this->db->query($query);
		if(is_array($rs)){
			foreach ($rs as $k=>$v){
				if ($ref) {
					$href = $ref.$v['ID'];
				}
				$parent_id = ($v['Parent_ID']) ? $v['Parent_ID'] : "root";
				$title = $v['Title'];
				$title = str_replace("<br>","",$title);
				$title = str_replace("&ndash;","",$title);
				$title = str_replace("Ц","",$title);
				
				$str = '&nbsp;&nbsp;<a href="#">+</a> <a href="#">-</a>';
				$this->tree->addnode($parent_id,$v['ID'],$title,$href, $str);
				$this->create_pages_tree($v['ID'],$ref);
			}
		}
		
	}
	
}                            	

$rules = new RulesEdit($_REQUEST['ID']);
echo $rules->main();
?>