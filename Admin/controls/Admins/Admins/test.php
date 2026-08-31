<?php

require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/tree.class.php";
$inc = new IncludeManager();

class RulesEdit
{
	var $db = '';
	
	var $tree = '';
	
	function RulesEdit()
	{
		$this->db = new DBinterface (	
							constant('DB_HOST'),
    						constant('DB_USER'),
                            constant('DB_PASS'),
                            constant('DB_NAME'));
        $this->tree = new tree("root","");
        $this->create_pages_tree(0,$str);
	}
	
	function main ()
	{
	
		return $this->tree->show_checkboxes('test');	
	}
	
	function create_pages_tree($start_page_id,$ref="") 
	{
		$query = "SELECT * FROM AdminMenu  WHERE Parent_ID = '$start_page_id' ORDER BY ShowOrder";
		$rs = $this->db->query($query);
		foreach ($rs as $k=>$v){
			if ($ref) {
				$href = $ref.$v['ID'];
			}
			$parent_id = ($v['Parent_ID']) ? $v['Parent_ID'] : "root";
			$title = $v['Title'];
			$title = str_replace("<br>","",$title);
			$title = str_replace("&ndash;","",$title);
			$title = str_replace("–","",$title);
			
			$str = '&nbsp;&nbsp;<a href="#">+</a> <a href="#">-</a>';
			$this->tree->addnode($parent_id,$v['ID'],$title,$href, $str);
			$this->create_pages_tree($tree,$v['ID'],$ref);
		}
	}
	
}                            	

$rules = new RulesEdit();	
echo $rules->main();
?>