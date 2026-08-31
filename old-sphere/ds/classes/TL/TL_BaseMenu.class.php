<?php $CVS_Version = '$Revision$ ; $Date$';

class TL_BaseMenu extends TL_Base
{
  
    // получить все закладки здания
    static protected function GetBuildingTabs(&$root, $bid, $lang, $current) 
    {
        
        $lang = DE_Language::CreateForCurentLanguage();
                
        $res = DS_Interface::GetBuildingTabs((int)$lang->getID(), (int)$bid);

        $ch = $root->new_child ( "alltabs" );

        for ( $i=0; $i<sizeof ( $res ); $i++ ) {

            if(!empty($res[$i]['affected_by_modifier']) && !DE_WorldSettings::CreateForCurentWorld()->get($res[$i]['affected_by_modifier']))
            {
                continue;
            }
            
            $ch2 = $ch->new_child ( "tab" );
            $ch2->set_attribute ( "id", trim ( $res[$i]['specialfunctionname'] ) );
            $ch2->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
            $ch2->set_attribute ( "icon", urlencode ( trim ( $res[$i]['tab_image'] ) ) );
            if(!empty($res[$i]['url']))
            {
                $ch2->set_attribute ( "nc", urlencode ( trim ( $res[$i]['url'] ) ) );
            }

            if ( $res[$i]['specialfunctionname'] == $current ) {
                $ch2->set_attribute ( "cur", "1" );
            }

            self::AddChildTabs ( $res[$i]['id_specialfunction'], $ch2, (int)$lang->getID(), $current );
        }
    
    }
    
    // добавить к закладке дочерние закладки
    static protected function AddChildTabs ( $id_tab, &$tab, $lang, $current ) 
    {       

        $res = DS_Interface::GetChildTabs((int)$lang, (int)$id_tab);

        for ( $i=0; $i<sizeof ( $res ); $i++ ) {

            $ch = $tab->new_child ( "tab" );
            $ch->set_attribute ( "id", trim ( $res[$i]['specialfunctionname'] ) );
            $ch->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
            $ch->set_attribute ( "icon", urlencode ( trim ( $res[$i]['tab_image'] ) ) );
            if ( $res[$i]['specialfunctionname'] == $current ) {
                $ch->set_attribute ( "cur", "1" );
            }
            self::addChildTabs ( $res[$i]['id_specialfunction'], $ch, $lang, $current );
        }       
    }
    

}

?>