<?php $CVS_Version = '$Revision$ ; $Date$';

class building {
	public $b_id_building; //unique id
	public $b_name;  //string
	public $b_localizedname;
	public $b_description; //string
	public $b_neededSciences = array(); //array of Strings

	public $b_neededBuildings = array(); //array of Strings
	public $b_Groundtypes = array(); //array of Strings
	public $b_buildtime; //float
	public $b_kadcosts; //integer
	public $b_nanocosts; //integer
	public $b_stasiscosts; //integer
	public $b_prodcosts; //integer
	public $b_foodcosts; //integer
	public $b_workercosts; //integer
	public $b_production; //amount of Turnproduction
	public $b_productionRes; // resname of Turnupkeep
	public $b_upkeep; //amount of upkeep 
	public $b_upkeepRes; //resname of upkeep
	
	function building() {
	//Constructor
	}
	function set_b_neededSciences($oneScience,$sciencelevel,$branchname){
		$temparray = array();
		$temparray[0] = $oneScience;
		$temparray[1] = $sciencelevel;
		$temparray[2] = $branchname;
		$this->b_neededSciences[] = $temparray;
	}
	function get_b_neededSciences(){
		$retString = '';
		foreach($this->b_neededSciences as $ns) {
			$retString = $retString . $ns[0] . " " . $ns[1] . " ". $ns[2] . "<BR>\n";
		}
		return $retString;
	} 
	function get_b_layoutedSciences(){
		foreach($this->b_neededSciences as $ns) {
			print "<tr>";
			print "<td><img src='img_buildings/rez_" . $ns[2] . "_small.gif' width=21 height=22 alt=" . str_replace ( " ", "&nbsp;", $ns[0]) . "></td>";
            print "<td>"; 
            print '<div align="center">' . $ns[1]. '</div></td>';
            print "</tr>";
		}
	}                                  
	function set_b_neededBuildings($thename){
		$this->b_neededBuildings[] = $thename;
	}
	function get_b_neededBuildings() {
		$retString = '';
		foreach($this->b_neededBuildings as $nb){
			$retString = $retString  .$nb . ", <br>\n";

		}
		$retString = substr($retString, 0,strlen($retString)-7);    
		return $retString;
	}
	function set_b_neededGroundtypes($celltype, $groundname) {
		$temparray = array();
		$temparray[0] = $celltype;
		$temparray[1] = $groundname;
		$this->b_Groundtypes[] = $temparray;
	}
	function get_b_neededGroundtypes() {
		$retString ='';
		foreach($this->b_Groundtypes as $gt){
			$retString = $retString . $gt[0] . "/" . $gt[1] . "<br>\n";
		}
		return $retString;
	}		
	function get_b_layoutedGroundtypes() {
		print '<div align="center">';
		foreach($this->b_Groundtypes as $gt) {

			print '<img src="img_buildings/00_ground_' . $gt[0] . '_' . $gt[1] . '.gif" width="35" height="40" vspace="2" hspace="2">'; 

		}
		print '</div>';
	}
	function set_b_id_building($b_id_building){
		$this->b_id_building = $b_id_building;
	}
	function get_b_id_building(){
		return $this->b_id_building;
	}
	function set_b_name($b_name){
		$this->b_name = $b_name;
	}
	function get_b_name() {
         return $this->b_name;
    }
	function set_b_localizedname($b_name){
		$this->b_localizedname = $b_name;
	}
	function get_b_localizedname() {
         return $this->b_localizedname;
    }
    function set_b_description($b_description){
    	$this->b_description = $b_description;
    }
    function get_b_description() {
    	return $this->b_description;
    }
    function set_b_buildtime($b_buildtime){
    	$this->b_buildtime = $b_buildtime;
    }
    function get_b_buildtime(){
    	return $this->b_buildtime;
    }
    function set_b_kadcosts($b_kadcosts){
    	$this->b_kadcosts = $b_kadcosts;
    }
    function get_b_kadcosts(){
    	return $this->b_kadcosts;
    }
    function set_b_nanocosts($b_nanocosts){
    	$this->b_nanocosts = $b_nanocosts;
    }
    function get_b_nanocosts(){
    	return $this->b_nanocosts;
    }
    function set_b_stasiscosts($b_stasiscosts){
    	$this->b_stasiscosts = $b_stasiscosts;
    }
    function get_b_stasiscosts(){
    	return $this->b_stasiscosts;
    }
    function set_b_prodcosts($b_prodcosts){
    	$this->b_prodcosts = $b_prodcosts;
    }
    function get_b_prodcosts(){
    	return $this->b_prodcosts;
    }
    function set_b_foodcosts($b_foodcosts){
    	$this->b_foodcosts = $b_foodcosts;
    }
    function get_b_foodcosts(){
    	return $this->b_foodcosts;
    }
    function set_b_workercosts($b_workercosts){
    	$this->b_workercosts = $b_workercosts;
    }
    function get_b_workercosts(){
    	return $this->b_workercosts;
    }
    function set_b_production($b_production){
    	$this->b_production = $b_production;
    }
    function get_b_production(){
    	return $this->b_production;
    }
    function set_b_productionRes($b_productionRes){
    	$this->b_productionRes = $b_productionRes;
    }
    function get_b_productionRes(){
    	return $this->b_productionRes;
    }
    function set_b_upkeep($b_upkeep){
    	$this->b_upkeep = $b_upkeep;
    }
    function get_b_upkeep(){
    	return $this->b_upkeep;
    }
    function set_b_upkeepRes($b_upkeepRes){
    	$this->b_upkeepRes = $b_upkeepRes;
    }
    function get_b_upkeepRes(){
    	return $this->b_upkeepRes;
    }
    function get_b_points(){
    	return ($this->b_kadcosts*2 + $this->b_stasiscosts + $this->b_nanocosts*4 + $this->b_foodcosts + $this->b_prodcosts*10)/10;
    }

   /* Dies ist die statische Vergleichsfunktion: */

   function cmp_names($a, $b)
   {
       $al = strtolower($a->b_localizedname);
       $bl = strtolower($b->b_localizedname);
       if ($al == $bl) return 0;
       return ($al > $bl) ? +1 : -1;
   }
   function cmp_buildtime($a, $b)
   {
       $al = $a->b_buildtime;
       $bl = $b->b_buildtime;
       if ($al == $bl) return 0;
       return ($al > $bl) ? +1 : -1;
   }
   function cmp_kaderium($a, $b)
   {
       $al = $a->b_kadcosts;
       $bl = $b->b_kadcosts;
       if ($al == $bl) return 0;
       return ($al > $bl) ? +1 : -1;
   }
   function cmp_nano($a, $b)
   {
       $al = $a->b_nanocosts;
       $bl = $b->b_nanocosts;
       if ($al == $bl) return 0;
       return ($al > $bl) ? +1 : -1;
   }
   function cmp_food($a, $b)
   {
       $al = $a->b_foodcosts;
       $bl = $b->b_foodcosts;
       if ($al == $bl) return 0;
       return ($al > $bl) ? +1 : -1;
   }
   function cmp_stasis($a, $b)
   {
       $al = $a->b_stasiscosts;
       $bl = $b->b_stasiscosts;
       if ($al == $bl) return 0;
       return ($al > $bl) ? +1 : -1;
   }
   function cmp_prodium($a, $b)
   {
       $al = $a->b_prodcosts;
       $bl = $b->b_prodcosts;
       if ($al == $bl) return 0;
       return ($al > $bl) ? +1 : -1;
   }
   function cmp_worker($a, $b)
   {
       $al = $a->b_workercosts;
       $bl = $b->b_workercosts;
       if ($al == $bl) return 0;
       return ($al > $bl) ? +1 : -1;
   }


    function printbuilding() {
    	print "<br>\n----------I D : " . $this->get_b_id_building() . "----------------<br>\n";
    	print "Name:" . $this->get_b_localizedname() . "/" . $this->get_b_name() . "<br>\n";
    	print "Beschreibung:" . $this->get_b_description() . "<br>\n";
    	print "Bauzeit:" . $this->get_b_buildtime(). "<br>\n";
    	print "Kaderium:" . $this->get_b_kadcosts(). "<br>\n";
    	print "Nano:" . $this->get_b_nanocosts(). "<br>\n";
    	print "Stasis:" . $this->get_b_stasiscosts(). "<br>\n";
    	print "Prodium:" . $this->get_b_prodcosts(). "<br>\n";
    	print "Nahrung:" . $this->get_b_foodcosts(). "<br>\n";
    	print "Arbeiter:" . $this->get_b_workercosts(). "<br>\n";
    	print "Produktion:" . $this->get_b_production() ." ". $this->get_b_productionRes() ."<br>\n";
    	print "Verbrauch:" . $this->get_b_upkeep(). " " . $this->get_b_upkeepRes(). "<br>\n";
    	print "<b>Benötigte Forschungen:</b> <br>\n" . $this->get_b_neededSciences(). "<br>\n";
    	print "<b>Benötigte Gebäude:</b> <br>\n" . $this->get_b_neededBuildings(). "<br>\n";
    	print "<b>BUntergrund:</b> <br>\n" . $this->get_b_neededGroundtypes(). "<br>\n";
    	print "-----------------------------------------------------<br>\n";
    }

    function layoutBuilding($aRacename) {
 ?>
 								  <tr height=34  bgcolor="60707A"> 
                                  <td align="center" width="65" ><img src="img_buildings/<? print $aRacename ."_" . $this->get_b_name() ?>.jpg" ></td>
                                  <td width="120" ><? print $this->get_b_localizedname() ?></td>
                                  <td height=34   width="200" align="center" bgcolor="60707A"> 
                                    <div align="left"><? print $this->get_b_description() ?> </div>
                                  </td>
                                  <td width="68" > 
                                    <div align="center"> 
                                      <table width="130" border="0" cellspacing="0" cellpadding="2">
                                         
                                          <? $this->get_b_layoutedSciences() ?>
                                        
                                      </table>
                                    </div>
                                  </td>
                                  <td  width=117 > 
                                    <div align="center"><? print $this->get_b_neededBuildings() ?></div>
                                  </td>
                                  <td colspan="2" > 
                                    <? $this->get_b_layoutedGroundtypes() ?>
                                  </td>
                                  <td  align=right width=39 bgcolor="926D96"  ><? print $this->get_b_buildtime() ?></td>
                                  <td  align=right width=63 bgcolor="#CC9933"  ="1000"><? print $this->get_b_kadcosts() ?></td>
                                  <td  align=right width=69 bgcolor="#6666CC"  ="1000"><? print $this->get_b_nanocosts() ?></td>
                                  <td  align=right width=54 bgcolor="#999999"  ="1000"><? print $this->get_b_stasiscosts() ?></td>
                                  <td  align=right width=56 bgcolor="#999966"  ><? print $this->get_b_prodcosts() ?></td>
                                  <td  align=right width=44 bgcolor="#669966"  ="1000"><? print $this->get_b_foodcosts() ?></td>
                                  <td  align=right width=56 bgcolor="#888888"  ><? print $this->get_b_workercosts() ?></td>
                                  <td  width=77 > 
                                    <div align="center"><? if($this->get_b_productionRes()) print "<img src='img_buildings/rez_" . $this->get_b_productionRes() . "_small.gif' width=21 height=22>";
                                    						print "   " .$this->get_b_production() . "";
                                    					   ?></div>
                                  </td>
                                  <td  width=77 > 
                                    <div align="center"><? if($this->get_b_upkeepRes()) print "<img src='img_buildings/rez_" . $this->get_b_upkeepRes() . "_small.gif' width=21 height=22>";
                                    						print "   " .$this->get_b_upkeep() . "";
                                    					   ?></div>
                                  </td>
                                  <td  align=right width="44" bgcolor="555555"><? print $this->get_b_points() ?></td>
                                  <td width="120" ><? print $this->get_b_localizedname() ?></td>
                                  <td align="center" width="65" ><img src="img_buildings/<? print $aRacename ."_" . $this->get_b_name() ?>.jpg" ></td>
                                </tr>   
                                <?	
    }

}
//$thebuilding = new building();
//$thebuilding->set_b_neededSciences("Stasis",5);
/*$thebuilding->set_b_name("Haus");
$thebuilding->set_b_neededSciences("Stasis 7");
$thebuilding->set_b_neededSciences("Kaderium 8");
echo $thebuilding->get_b_name();
*/
//$thebuilding->get_b_neededSciences();
//$thebuilding->printbuilding();
?>