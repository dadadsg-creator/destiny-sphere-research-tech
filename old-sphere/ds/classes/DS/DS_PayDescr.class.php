<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 *  Библиотека запросов, связанных с "витриной" (онисанием платных сервисов на портале)
 */
class DS_PayDescr extends DS_Base
{
  static public function getMenu($pID = 0, $visible=1, $mode=0)
  {
     global $conn_id;
     if ($mode==1) //админка - выбираем всё
       $sql = "select * from VitrinaMenu where parent_id={$pID} order by place asc, id asc";
     else
       $sql = "select * from VitrinaMenu where parent_id={$pID} and visible=$visible order by place asc, id asc";

     $result = mysql_query($sql,$conn_id);
     
     $children = array();
     if ($result) 
     {
       while ($row = mysql_fetch_row($result)) 
       {
            $children[] = $row;
         }
     }

     $res = array();
     //echo "pid=$pID, children=".var_export($children, true).'<br>';
     foreach ($children as $el)
     {
       $r = array();
       $r['val'] = $el;
       $r['children'] = self::getMenu($el[0], $visible, $mode);

       $res[] = $r;
     }
     return $res;
  }

  static public function getMenuVisibleList()
  {
     global $conn_id;
     $sql = "select id, visible from VitrinaMenu";

     $result = mysql_db_query(DBName,$sql,$conn_id);

     $res  = '';
     if ($result) 
     {

       while ($row = mysql_fetch_row($result)) 
       {
            $res .= $row[0].':'.$row[1].',';
         }
       $res = substr($res, 0, -1);
       $res = "{".$res."}";
     }
    
     return $res;
  }

  static public function addMenu($title, $pID)
  {
     global $conn_id;
     $title = mysql_escape_string(trim($title));
     if (!$pID)
        $pID = 0;
     $sql = "INSERT INTO VitrinaMenu(title, parent_id) values('$title', $pID)";
     //echo $sql;
     $result = mysql_db_query(DBName,$sql,$conn_id);
  }

  static public function updateMenu($title, $id, $visible)
  {
     global $conn_id;
     $title = mysql_escape_string(trim($title));
     $sql = "update VitrinaMenu set title='$title', visible=$visible where id=$id";
     $result = mysql_db_query(DBName,$sql,$conn_id);
  }

  static public function getAllParents($id)
  {
     global $conn_id;
     $parents = array();
     while(true)
     {
       $sql = "select * from VitrinaMenu where id={$id}";
       $result = mysql_db_query(DBName,$sql,$conn_id);
       $row =mysql_fetch_assoc($result);
       $parents[] = array('id'=>$row['id'], 'title'=> $row['title'], 'vis'=>$row['visible']);

       $id = $row['parent_id'];
       if ($id==0)
         break;
     }

     return $parents;
  }
  
  static public function delMenu($ID)
  {
     global $conn_id;
     $sql = "select * from VitrinaMenu where parent_id={$ID}";
     $result = mysql_db_query(DBName,$sql,$conn_id);

     $children = array();
     if ($result) 
     {
       while ($row = mysql_fetch_row($result)) 
       {
            $children[] = $row;
         }
     }

     $res = array();
     foreach ($children as $el)
     {
       self::delMenu($el[0]);
     }

     $sql = "delete from VitrinaMenu where id={$ID}";
     $result = @mysql_db_query(DBName,$sql,$conn_id);
  }

  static public function getText($menuID)
  {
     global $conn_id;
     $sql = "select id, menu_id, title, vitr_text from VitrinaText where menu_id={$menuID}";
     $result = mysql_db_query(DBName,$sql,$conn_id);

     if ($result) 
     {
       $row =  mysql_fetch_assoc($result);

       $row['title'] = str_replace('\"','"', $row['title']);
       $row['title'] = str_replace("\'","'", $row['title']);

       $row['vitr_text'] = str_replace('\"','"', $row['vitr_text']);
       $row['vitr_text'] = str_replace("\'","'", $row['vitr_text']);
       return $row;
     }
     else
       return array('id'=>0,'menu_id'=>0,'title'=>'', 'vitr_text'=>'');
  }

  static public function updateText($textID, $title, $text)
  {
     global $conn_id;
     $title = str_replace("'","\'", $title);
     $text  = str_replace("'","\'", $text);

     $sql = "UPDATE VitrinaText SET title='$title', vitr_text='$text' where id={$textID}";

     $result = mysql_db_query(DBName,$sql,$conn_id);
  }

  static public function insertText($menuID, $title, $text)
  {
     global $conn_id;
     $title = str_replace("'","\'", $title);
     $text  = str_replace("'","\'", $text);

     $sql = "INSERT INTO VitrinaText(menu_id, title, vitr_text) values($menuID, '$title', '$text')";
     $result = mysql_db_query(DBName,$sql,$conn_id);
  }
}
?>