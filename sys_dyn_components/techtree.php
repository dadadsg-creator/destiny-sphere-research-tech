<?php

  /* ======== language settings ======== */  
  /* allowed languages */
  /* please be sure to provide ALL translations for a language in $l and in buildings.php first or */
  /* you will have missing text!                                                                   */ 
  $allowed_lang = array ('de', 'en');
  $defaultlang = "de";

  /* language array */
  /* add each translation you want */
  /* please be sure to provide ALL translations for a language in $l and in buildings.php before */
  /* you allow this language or you will have missing text!                                      */ 
  $l = array (
    /* key                => array ('lang1' => "translation1"     , 'lang2' => "translation2"  , ... ), */
    'researchtree'        => array ('de' => "Forschungsbaum"      , 'en' => "research tree"      ),
    'voraner'             => array ('de' => "Voraner"             , 'en' => "Vorans"             ),
    'liensu'              => array ('de' => "Liensu"              , 'en' => "Liensu"             ),
    'psolao'              => array ('de' => "Psolao"              , 'en' => "Psolao"             ),
    'kaderium'            => array ('de' => "Kaderium"            , 'en' => "Kaderium"           ),
    'nano'                => array ('de' => "Nano-Kristalle"      , 'en' => "Nano Crystals"      ),
    'energia'             => array ('de' => "Stasis-Energie"      , 'en' => "Energy"             ),
    'eda'                 => array ('de' => "Nahrung"             , 'en' => "Food"               ),
    'prodium'             => array ('de' => "Prodium"             , 'en' => "Prodium"            ),
    'trade'               => array ('de' => "Handel"              , 'en' => "Trade"              ),
    'spy'                 => array ('de' => "Spionage"            , 'en' => "Spy"                ),
    'requirements'        => array ('de' => "Vorraussetzungen"    , 'en' => "requirements"       ),
    'abilities'           => array ('de' => "Fähigkeiten"         , 'en' => "abilities"          ),
    'needed_research'     => array ('de' => "Benötigte Forschung:", 'en' => "Required research:" ),
    'needed_building'     => array ('de' => "Benötigte Gebäude:"  , 'en' => "Required buildings:"),
    'no_buildings'        => array ('de' => "Keine"               , 'en' => "none"               ),
    'provides'            => array ('de' => "Ermöglicht:"         , 'en' => "Provides:"          ),
    'description'         => array ('de' => "Beschreibung:"       , 'en' => "description:"       ),  
    'builds_for_research' => array ('de' => "Benötigte Gebäude um dieses Forschungslevel erreichen zu können:",
                                        'en' => "Required buildings to reach this research level:"),
    'direct_needed'       => array ('de' => "Benötigte Forschung für dieses Gebäude",
                                        'en' => "Required research for this building"),
    'indirect_needed'     => array ('de' => "Insgesamt benötige Forschung für dieses Gebäude und dessen Vorraussetzungen",
                                       'en' => "Totally required research for this building and its requirements"),
    'buildings_error'     => array ('de' => "ACHTUNG: ".'$buildings'." ist vermutlich fehlerhaft. Ringförmige Abhänigkeit vermutet.",
                                        'en' => "WARNING: ".'$buildings'." probably contains errors. Supposing ring-shaped dependency.")
  );
  
  /* ======== user settings ======== */  
  $race = $HTTP_GET_VARS['race'];
  if (($race != "voraner") && ($race != "liensu") && ($race != "psolao")) {
    $race = "voraner";
  }
  /* user language */  
  $defaultlang = "de";
  $lang = $HTTP_GET_VARS['lang'];
  if (! in_array($lang, $allowed_lang)) {
    /* default language*/
    $lang = $defaultlang;
  }
  /* expand */
  $expand = $HTTP_GET_VARS['exp'];

  /* ======== buildings ======== */  
  
  include "buildings.php";

  /* ======== path settings ======== */  
  if ($lang == $defaultlang) {
    $treepath = "?race=".$race;
  } else {  
    $treepath = "?lang=".$lang."&amp;race=".$race;
  }
  $imagepath_resource_icon = "img/support/resource/";
  $imagepath_resource_cat = "img/support/techtree/";
  $imagepath_resource_box = "img/support/techtree/";
  $imagepath_builds = "img/support/builds/";
  $imagepath_builds_small = "img/support/builds/small/";
  $imagepath_dummy = "img/support/techtree/";
  $stylepath_dsstyle = "";
  $stylepath_mystyle = "";
  
  /* ======== abbreviation table ======== */  
  /* short (german) abbreviations of the 7 research categories, used in 'buildings.php' */
  $research_short = array (
    'kaderium' => "Ka",
    'nano'     => "NK",
    'energia'  => "St",
    'eda'      => "Na",
    'prodium'  => "Pr",
    'trade'    => "Ha",
    'spy'      => "Sp",
  );
  
  /* ======== html indention ======== */
  /* removes word hyphenation in 'long' words like 'Interringhandelskonnektor' */
  /* used for tooltips (title) and image alternate text (alt) */
  function nobreak($text) {
    return str_replace ("-<BR>", "", $text);
  }
 
  /* ======== html indention ======== */
  function html_indent($number_of_whitespaces) {
    global $sp;
    $sp .= str_repeat (" ", $number_of_whitespaces);
  }
  function html_unindent($number_of_whitespaces) {
    global $sp;
    $sp = substr($sp,0, strlen($sp)-$number_of_whitespaces);
  }

  /* ======== wnd ======== */
  function wnd_start($title) {
    global $sp, $imagepath_dummy;
    echo $sp."<TABLE class=\"wnd_100\" border=0 cellpadding=0 cellspacing=0>\n";
    echo $sp."  <TR>\n";
    echo $sp."    <TD class=\"wnd_t_l\"></TD>\n";
    echo $sp."    <TD class=\"wnd_t\"></TD>\n";
    echo $sp."    <TD class=\"wnd_t_r\"></TD>\n";
    echo $sp."  </TR>\n";
    echo $sp."  <TR>\n";
    echo $sp."    <TD class=\"wnd_l\"><IMG width=\"5\" height=\"5\" src=\"".$imagepath_dummy."dummy.gif\" alt=\"\" class=\"rt_img\"></TD>\n";
    echo $sp."    <TD class=\"wnd_outer\">\n";
    echo $sp."      <TABLE class=\"wnd_layout\">\n";
    echo $sp."        <TR>\n";
    echo $sp."          <TD class=\"wnd_header_txt\">\n";
    echo $sp."            <IMG src=\"img/elements/cap_div.gif\" alt=\"\" class=\"cap_img\">".$title."\n";
    echo $sp."          </TD>\n";
    echo $sp."        </TR>\n";
    echo $sp."        <TR>\n";
    echo $sp."          <TD class=\"wnd_gen_txt\">\n";
    html_indent(12);
  }
  function wnd_end() {
    global $sp, $imagepath_dummy;
    html_unindent(12);
    echo $sp."          </TD>\n";
    echo $sp."        </TR>\n";
    echo $sp."      </TABLE>\n";
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"wnd_r\"><IMG width=\"10\" height=\"5\" src=\"".$imagepath_dummy."dummy.gif\" alt=\"\" class=\"rt_img\"></TD>\n";
    echo $sp."  </TR>\n";
    echo $sp."  <TR>\n";
    echo $sp."    <TD class=\"wnd_b_l\"></TD>\n";
    echo $sp."    <TD class=\"wnd_b\"></TD>\n";
    echo $sp."    <TD class=\"wnd_b_r\"></TD>\n";
    echo $sp."  </TR>\n";
    echo $sp."</TABLE>\n";
  }

  /* ======== wnd2 ======== */
  function wnd2_start($full_width=false) {
    global $sp, $imagepath_dummy;
    if ($full_width) {
      echo $sp."<TABLE class=\"wnd2_100\" border=0 cellpadding=0 cellspacing=0>\n";
    } else {
      echo $sp."<TABLE class=\"wnd2\" border=0 cellpadding=0 cellspacing=0>\n";
    }
    echo $sp."  <TR><TD class=\"wnd2_t_l\"></TD><TD class=\"wnd2_t\"></TD><TD class=\"wnd2_t_r\"></TD></TR>\n";
    echo $sp."  <TR>\n";
    echo $sp."    <TD class=\"wnd2_l\"><IMG src=\"".$imagepath_dummy."dummy.gif\" alt=\"\" class=\"wnddummy\"></TD>\n";
    echo $sp."    <TD class=\"wnd2_inner\">\n";
    html_indent(6);
  }
  function wnd2_end() {
    global $sp, $imagepath_dummy;
    html_unindent(6);
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"wnd2_r\"><IMG src=\"".$imagepath_dummy."dummy.gif\" alt=\"\" class=\"wnddummy\"></TD>\n";
    echo $sp."  </TR>\n";
    echo $sp."  <TR><TD class=\"wnd2_b_l\"></TD><TD class=\"wnd2_b\"></TD><TD class=\"wnd2_b_r\"></TD></TR>\n";
    echo $sp."</TABLE>\n";
  }

  /* ======== research tree ======== */
  
  /* head / bottom element for the research category */ 
  function my_tree_head_element($research_cat) {
    global $sp, $l, $lang;
    global $imagepath_resource_cat;
    html_indent(6);
    wnd2_start(true);
    echo $sp."<DIV style=\"text-align: center;\">\n";
    echo $sp."  <IMG width=\"60\" height=\"60\" src=\"".$imagepath_resource_cat.$research_cat.".png\" alt=\"\" class=\"rt_img\"><BR>\n";
    echo $sp."  <SPAN class=\"wnd_gen_txt\" style=\"font-size:15px; font-weight: bold; white-space: nowrap;\">".$l[$research_cat][$lang]."</SPAN>\n";
    echo $sp."</DIV>\n";
    wnd2_end();
    html_unindent(6);
  }

  /* level box */
  function my_tree_level_element($level, $research) {
    global $sp, $imagepath_resource_icon, $l, $lang;
    html_indent(6);
    wnd2_start(true);
    echo $sp."<DIV style=\"text-align: center;\">\n";
    echo $sp."  <SPAN class=\"wnd_gen_txt\" style=\"font-size:18px; font-weight: bold; vertical-align: middle;\">";
    echo "<IMG class=\"ress_icon\" src=\"".$imagepath_resource_icon."".$research.".gif\" alt=\"".$l[$research][$lang]."\" title=\"".$l[$research][$lang]."\" style=\"vertical-align: middle;\">";
    echo " &nbsp; ".$level;
    echo "</SPAN>\n";
    echo $sp."</DIV>\n";
    wnd2_end();
    html_unindent(6);
  }

  /* draws all building elements */
  function my_tree_elements($research, $level, $expand, $on_right=false, $on_bottom=false) {
    global $sp, $imagepath_resource_box, $box_id;
    global $l, $lang;
    global $buildings, $research_abilities, $research_requirements, $research_short, $expand;
    html_indent(6);
    $firstbox = true;
    
    /* on the right popup to the left */
    $rt_e = "rt_e".($on_right?" rt_right":"").($on_bottom?" rt_bottom":"");
    
    /* get requirements for the research level */
    $show_requirements = false;
    foreach ($research_requirements as $rdata) {
      if (($rdata['insertlevel'] == $level) && ($rdata['insertresearch'] == $research)) {
        $show_requirements = $rdata;
        break;
      }
    }
    /* if there is an requirement, show the requirements for the research level */
    if ($show_requirements) {
      if ($research.$level."r" == $expand) {
        my_tree_require_element_large($research, $level, $show_requirements['needed'], $show_requirements['img']) ;
      } else {
        echo $sp."<DIV id=\"e".$box_id."\" class=\"".$rt_e."\" onmouseover=\"ShowThis('e".$box_id."');\" onmouseout=\"HideThis('e".$box_id."');\">\n";
        html_indent(2);
        my_tree_require_element_large($research, $level, $show_requirements['needed'], $show_requirements['img'], true) ;
        html_unindent(2);
        echo $sp."</DIV>\n";
        echo $sp."<DIV id=\"n".$box_id."\" class=\"rt_n\">\n";
        html_indent(2);
        my_tree_require_element_small($research, $level, $show_requirements['needed'], $show_requirements['img']) ;
        html_unindent(2);
        echo $sp."</DIV>\n";
        $box_id++;
      }
      $firstbox = false;
    }
    
    /* get abilities for the research level */
    $show_ability = false;
    foreach ($research_abilities as $adata) {
      if (($adata['insertlevel'] == $level) && ($adata['insertresearch'] == $research)) {
        $show_ability = $adata;
        break;
      }
    }
    /* if there is an ability  show the box */
    if ($show_ability) {
      if (!($firstbox)) {
        echo $sp."<DIV style=\"height: 4px;\"></DIV>\n";
      }
      if ($research.$level."a" == $expand) {
        my_tree_ability_element_large($research, $level, $show_ability['allows'], $show_ability['img']) ;
      } else {
        echo $sp."<DIV id=\"e".$box_id."\" class=\"".$rt_e."\" onmouseover=\"ShowThis('e".$box_id."');\" onmouseout=\"HideThis('e".$box_id."');\">\n";
        html_indent(2);
        my_tree_ability_element_large($research, $level, $show_ability['allows'], $show_ability['img'], true) ;
        html_unindent(2);
        echo $sp."</DIV>\n";
        echo $sp."<DIV id=\"n".$box_id."\" class=\"rt_n\">\n";
        html_indent(2);
        my_tree_ability_element_small($research, $level, $show_ability['allows'], $show_ability['img']) ;
        html_unindent(2);
        echo $sp."</DIV>\n";
        $box_id++;
      }
      $firstbox = false;
    }

    /* show all buildings */       
    foreach ($buildings as $building => $bdata) {
      if (($bdata['insertlevel'] == $level) && ($bdata['insertresearch'] == $research_short[$research])) {
        if (!($firstbox)) {
          echo $sp."<DIV style=\"height: 4px;\"></DIV>\n";
        }
        if ($building == $expand) {
          my_tree_building_element_large($building, $bdata) ;
        } else {
          echo $sp."<DIV id=\"e".$box_id."\" class=\"".$rt_e."\" onmouseover=\"ShowThis('e".$box_id."');\" onmouseout=\"HideThis('e".$box_id."');\">\n";
          html_indent(2);
          my_tree_building_element_large($building, $bdata, true) ;
          html_unindent(2);
          echo $sp."</DIV>\n";
          echo $sp."<DIV id=\"n".$box_id."\" class=\"rt_n\">\n";
          html_indent(2);
          my_tree_building_element_small($building, $bdata) ;
          html_unindent(2);
          echo $sp."</DIV>\n";
          $box_id++;
        }
        $firstbox = false;
      }
    }
    
    if ($firstbox) {
      my_tree_empty_box ($research, $level); 
    }
    html_unindent(6);
  }

  /* empty box */
  function my_tree_empty_box ($research, $level) {
    global $sp, $l, $lang;
    global $imagepath_resource_box;
    wnd2_start();
    $title = $l[$research][$lang]." ".$level;
    echo $sp."<DIV class=\"rt_box\">\n";
    echo $sp."  <IMG width=\"110\" height=\"70\" src=\"".$imagepath_resource_box."box".$research.".gif"."\" alt=\"\" title=\"\" class=\"rt_img\"><BR>\n";
    echo $sp."  <DIV class=\"wnd_header_txt\" style=\"text-align: center;\">".$title."</DIV>\n";
    echo $sp."</DIV>\n";
    wnd2_end();
  }

  /* box for a normal building box with image and name */
  function my_tree_building_element_small(&$building, &$bdata) {
    global $sp, $lang, $box_id;
    global $treepath, $imagepath_builds;
    wnd2_start();
    $title_nb = nobreak($bdata[$lang]);
    $title = $bdata[$lang];
    $desc = nobreak($bdata[$lang])." - ".$bdata['description_'.$lang];
    echo $sp."<DIV class=\"rt_box\">\n";
    echo $sp."  <A name=\"".$building."\" href=\"".$treepath."&amp;exp=".$building."#".$building."\" class=\"wnd_header_txt\">\n";
    echo $sp."    <IMG width=\"110\" height=\"70\" src=\"".$imagepath_builds.$bdata['img']."\" alt=\"".$title_nb."\" title=\"".$desc."\" class=\"rt_img\"><BR>\n";
    echo $sp."  </A>\n";
    echo $sp."  <DIV style=\"text-align: center; width: 110px;\">\n";
    //echo $sp."    <A href=\"".$treepath."&amp;exp=".$building."#".$building."\" title=\"".$desc."\" class=\"wnd_header_txt\" onmouseover=\"ShowHide('e".$box_id."', 'n".$box_id."');\">".$title."</A>\n";
    echo $sp."    <A href=\"".$treepath."&amp;exp=".$building."#".$building."\" title=\"".$desc."\" class=\"wnd_header_txt\" onmouseover=\"ShowThisHideOthers('e".$box_id."');\">".$title."</A>\n";
    echo $sp."  </DIV>\n";
    echo $sp."</DIV>\n";
    wnd2_end();
  }

  /* box for a large building box with image, name, needed research and needed buildings */
  function my_tree_building_element_large(&$building, &$bdata, $as_pop_up=false) {
    global $sp, $l, $lang;
    global $treepath, $imagepath_builds;
    wnd2_start($as_pop_up);
    $title_nb = nobreak($bdata[$lang]);
    $title = $bdata[$lang];
    $desc = nobreak($bdata[$lang])." - ".$bdata['description_'.$lang];
    if ($as_pop_up) {
      echo $sp."<TABLE border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width: 100%;\">\n";
    } else {
      echo $sp."<TABLE border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
    }      
    echo $sp."  <TR>\n";
    /* image and name */
    echo $sp."    <TD style=\"vertical-align: top;\">\n";
    echo $sp."      <DIV class=\"rt_in_box\">\n";
    echo $sp."        <A name=\"".$building."\" href=\"".$treepath."#".$building."\" class=\"wnd_header_txt\">\n";
    echo $sp."          <IMG width=\"110\" height=\"70\" src=\"".$imagepath_builds.$bdata['img']."\" alt=\"".$title_nb."\" title=\"".$desc."\" class=\"rt_img\"><BR>\n";
    echo $sp."        </A>\n";
    echo $sp."        <DIV style=\"text-align: center;\">\n";
    if ($as_pop_up) {
      echo $sp."          <A href=\"".$treepath."&amp;exp=".$building."#".$building."\" title=\"\" class=\"wnd_header_txt\">".$title."</A>\n";
    } else  {
      echo $sp."          <A href=\"".$treepath."#".$building."\" title=\"".$desc."\" class=\"wnd_header_txt\">".$title."</A>\n";
    }
    echo $sp."        </DIV>\n";
    echo $sp."      </DIV>\n";
    echo $sp."    </TD>\n";
    echo $sp."    <TD style=\"vertical-align: top;\">\n";
    html_indent(6);
    /* required research */
    my_tree_needed_research($bdata);
    html_unindent(6);
    echo $sp."    </TD>\n";
    echo $sp."  </TR>\n";
    echo $sp."  <TR>\n";
    echo $sp."    <TD colspan=2>\n";
    html_indent(6);
    /* required buildings */
    my_tree_needed_buildings($bdata['needed']); 
    html_unindent(6);
    echo $sp."    </TD>\n";
    echo $sp."  </TR>\n";
    if ($bdata['description_'.$lang]) {
      echo $sp."  <TR>\n";
      echo $sp."    <TD colspan=2>\n";
      echo $sp."      <DIV style=\"height: 4px;\"></DIV>\n";
      html_indent(6);
      /* description */
      wnd2_start(true);
      echo $sp."<DIV class=\"wnd_gen_txt\" style=\"text-align: center;\">\n";
      echo $sp."  ".$l['description'][$lang]."<BR><BR>\n";
      echo $sp."  ".$bdata['description_'.$lang]."\n";
      echo $sp."</DIV>\n";
      wnd2_end();
      html_unindent(6);
      echo $sp."    </TD>\n";
      echo $sp."  </TR>\n";
    }
    echo $sp."</TABLE>\n";
    wnd2_end();
  }
  
  /* ======== needed research box ======== */

  /* needed research for a bulding */
  function my_tree_needed_research(&$bdata) {
    global $sp, $l, $lang;
    global $imagepath_resource_icon;
    wnd2_start(true);
    echo $sp."<DIV class=\"wnd_gen_txt\" style=\"text-align: center;\">\n";
    echo $sp."  ".$l['needed_research'][$lang]."<BR><BR>\n";
    echo $sp."  <TABLE width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n";
    echo $sp."    <TR>\n";
    echo $sp."      <TD><IMG class=\"ress_icon\" src=\"".$imagepath_resource_icon."kaderium.gif\" alt=\"".$l['kaderium'][$lang]."\" title=\"".$l['kaderium'][$lang]."\"></TD>\n";
    echo $sp."      <TD rowspan=3><IMG width=5 height=55 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
    echo $sp."      <TD><IMG class=\"ress_icon\" src=\"".$imagepath_resource_icon."nano.gif\" alt=\"".$l['nano'][$lang]."\" title=\"".$l['nano'][$lang]."\"></TD>\n";
    echo $sp."      <TD rowspan=3><IMG width=5 height=55 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
    echo $sp."      <TD><IMG class=\"ress_icon\" src=\"".$imagepath_resource_icon."energia.gif\" alt=\"".$l['energia'][$lang]."\" title=\"".$l['energia'][$lang]."\"></TD>\n";
    echo $sp."      <TD rowspan=3><IMG width=5 height=55 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
    echo $sp."      <TD><IMG class=\"ress_icon\" src=\"".$imagepath_resource_icon."eda.gif\" alt=\"".$l['eda'][$lang]."\" title=\"".$l['eda'][$lang]."\"></TD>\n";
    echo $sp."      <TD rowspan=3><IMG width=5 height=55 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
    echo $sp."      <TD><IMG class=\"ress_icon\" src=\"".$imagepath_resource_icon."prodium.gif\" alt=\"".$l['prodium'][$lang]."\" title=\"".$l['prodium'][$lang]."\"></TD>\n";
    echo $sp."      <TD rowspan=3><IMG width=5 height=55 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
    echo $sp."      <TD><IMG class=\"ress_icon\" src=\"".$imagepath_resource_icon."trade.gif\" alt=\"".$l['trade'][$lang]."\" title=\"".$l['trade'][$lang]."\"></TD>\n";
    echo $sp."      <TD rowspan=3><IMG width=5 height=55 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
    echo $sp."      <TD><IMG class=\"ress_icon\" src=\"".$imagepath_resource_icon."spy.gif\" alt=\"".$l['spy'][$lang]."\" title=\"".$l['spy'][$lang]."\"></TD>\n";
    echo $sp."    </TR>\n";
    echo $sp."    <TR>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$bdata['research'][0]."</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$bdata['research'][1]."</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$bdata['research'][2]."</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$bdata['research'][3]."</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$bdata['research'][4]."</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$bdata['research'][5]."</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$bdata['research'][6]."</TD>\n";
    echo $sp."    </TR>\n";
    $needed_research = $bdata['research'];
    get_max_research($bdata['needed'], $needed_research);
    echo $sp."    <TR>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['indirect_needed'][$lang]."\">(".$needed_research[0].")</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['indirect_needed'][$lang]."\">(".$needed_research[1].")</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['indirect_needed'][$lang]."\">(".$needed_research[2].")</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['indirect_needed'][$lang]."\">(".$needed_research[3].")</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['indirect_needed'][$lang]."\">(".$needed_research[4].")</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['indirect_needed'][$lang]."\">(".$needed_research[5].")</TD>\n";
    echo $sp."      <TD class=\"wnd_gen_txt\" title=\"".$l['indirect_needed'][$lang]."\">(".$needed_research[6].")</TD>\n";
    echo $sp."    </TR>\n";
    echo $sp."  </TABLE>\n";
    echo $sp."</DIV>\n";
    wnd2_end();
  }

  /* ======== calculates the needed research levels with regard to all needed buildings ======== */
  /* function get's the needed level for the building in $needed_research and a list of needed   */
  /* buildings in $direct_needed_buildings. For each of the needed buildings we rise the         */
  /* $needed_research if neccessary. And we will also take a look at their needded buildings     */
  function get_max_research(&$direct_needed_buildings, &$needed_research) {
    global $buildings, $l, $lang;
    // Array aller direkten Vorraussetzungen
    $needed_buildings = $direct_needed_buildings;
    // Sicherheitscounter
    $counter = 0;
    // solange noch Vorraussetzungen existieren 
    while ((sizeof($needed_buildings) > 0) && ($counter < 100)) {
      // Nimm ein vorrausgesetzes Gebäude
      $building = array_pop($needed_buildings);
      // Gehe durch alle Forschungenstypen
      for ($i = 0; $i <= 7; $i++) {
        // Wenn die benötigte Forschungsstufe größer ist, wie die letzte notierte benötigte Forschungsstufe ...
        if ($buildings[$building]['research'][$i] > $needed_research[$i]) {
          // ... dann nimm diese Forschungsstufe          
          $needed_research[$i] = $buildings[$building]['research'][$i];
        }
      }
      // füge alle Vorraussetzungen dieses Gebäudes hinzu.
      $needed_buildings = array_merge($needed_buildings, $buildings[$building]['needed']);
      // Sicherheitscounter erhöhen
      $counter++;
    }
    // Sicherheitscounter überprüfen
    if ($counter >= 100) {
      echo "<TR><TD colspan=7>".$l['buildings_error'][$lang]."</TD></TR>\n";
    }
  }

  /* ======== needed building box ======== */

  /* needed building (for a building or a research level) with small image and needed research */
  function my_tree_needed_buildings(&$buildinglist, $forresearch = false) {
    global $sp, $buildings, $l, $lang;
    global $treepath, $imagepath_builds_small, $imagepath_resource_icon;
    wnd2_start(true);
    echo $sp."<DIV class=\"wnd_gen_txt\" style=\"text-align: center;\">\n";
    if ($forresearch) {
      echo $sp."  ".$l['builds_for_research'][$lang]."<BR>\n";
    } else {
      echo $sp."  ".$l['needed_building'][$lang]."<BR>\n";
    }
    echo $sp."  <DIV style=\"height: 4px;\"></DIV>\n";
    if (sizeof($buildinglist) == 0) {
      echo $sp."  ".$l['no_buildings'][$lang]."<BR>\n";
    } else {
      echo $sp."  <TABLE width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
      $firstneededbuilding = true;
      foreach ($buildinglist as $bneedbuilding) {
        if (! $firstneededbuilding){
          echo $sp."    <TR>\n";
          echo $sp."      <TD colspan=2 class=\"my_line\">\n";
          echo $sp."        \n";
          echo $sp."      </TD>\n";
          echo $sp."    </TR>\n";
        }
        $buildingname = nobreak($buildings[$bneedbuilding][$lang]);
        echo $sp."    <TR>\n";
        echo $sp."      <TD class=\"my_building_needed\"><A name=\"".$bneedbuilding."\" href=\"".$treepath."&amp;exp=".$bneedbuilding."#".$bneedbuilding."\" class=\"wnd_header_txt\"><IMG width=\"50\" height=\"50\" class= \"rt_img\" src=\"".$imagepath_builds_small.$buildings[$bneedbuilding]['imgsmall']."\" alt=\"".$buildingname."\" title=\"".$buildingname."\"></A></TD>\n";
        echo $sp."      <TD class=\"my_building_needed\">\n";
        echo $sp."        <A name=\"".$bneedbuilding."\" href=\"".$treepath."&amp;exp=".$bneedbuilding."#".$bneedbuilding."\" class=\"wnd_header_txt\">".$buildingname."</A>\n";
        echo $sp."        <TABLE width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n";
        echo $sp."          <TR>\n";
        echo $sp."            <TD><IMG src=\"".$imagepath_resource_icon."kaderium.gif\" alt=\"".$l['kaderium'][$lang]."\" title=\"".$l['kaderium'][$lang]."\" class=\"ress_icon\"></TD>\n";
        echo $sp."            <TD rowspan=3><IMG width=5 height=40 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
        echo $sp."            <TD><IMG src=\"".$imagepath_resource_icon."nano.gif\" alt=\"".$l['nano'][$lang]."\" title=\"".$l['nano'][$lang]."\" class=\"ress_icon\"></TD>\n";
        echo $sp."            <TD rowspan=3><IMG width=5 height=40 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
        echo $sp."            <TD><IMG src=\"".$imagepath_resource_icon."energia.gif\" alt=\"".$l['energia'][$lang]."\" title=\"".$l['energia'][$lang]."\" class=\"ress_icon\"></TD>\n";
        echo $sp."            <TD rowspan=3><IMG width=5 height=40 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
        echo $sp."            <TD><IMG src=\"".$imagepath_resource_icon."eda.gif\" alt=\"".$l['eda'][$lang]."\" title=\"".$l['eda'][$lang]."\" class=\"ress_icon\"></TD>\n";
        echo $sp."            <TD rowspan=3><IMG width=5 height=40 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
        echo $sp."            <TD><IMG src=\"".$imagepath_resource_icon."prodium.gif\" alt=\"".$l['prodium'][$lang]."\" title=\"".$l['prodium'][$lang]."\" class=\"ress_icon\"></TD>\n";
        echo $sp."            <TD rowspan=3><IMG width=5 height=40 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
        echo $sp."            <TD><IMG src=\"".$imagepath_resource_icon."trade.gif\" alt=\"".$l['trade'][$lang]."\" title=\"".$l['trade'][$lang]."\" class=\"ress_icon\"></TD>\n";
        echo $sp."            <TD rowspan=3><IMG width=5 height=40 src=\"img/graph/line02.gif\" alt=\"\"></TD>\n";
        echo $sp."            <TD><IMG src=\"".$imagepath_resource_icon."spy.gif\" alt=\"".$l['spy'][$lang]."\" title=\"".$l['spy'][$lang]."\" class=\"ress_icon\"></TD>\n";
        echo $sp."          </TR>\n";
        echo $sp."          <TR>\n";
        echo $sp."            <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$buildings[$bneedbuilding]['research'][0]."</TD>\n";
        echo $sp."            <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$buildings[$bneedbuilding]['research'][1]."</TD>\n";
        echo $sp."            <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$buildings[$bneedbuilding]['research'][2]."</TD>\n";
        echo $sp."            <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$buildings[$bneedbuilding]['research'][3]."</TD>\n";
        echo $sp."            <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$buildings[$bneedbuilding]['research'][4]."</TD>\n";
        echo $sp."            <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$buildings[$bneedbuilding]['research'][5]."</TD>\n";
        echo $sp."            <TD class=\"wnd_gen_txt\" title=\"".$l['direct_needed'][$lang]."\">".$buildings[$bneedbuilding]['research'][6]."</TD>\n";
        echo $sp."          </TR>\n";
        echo $sp."        </TABLE>\n";
        echo $sp."      </TD>\n";
        echo $sp."    </TR>\n";
        $firstneededbuilding = false;
      }
      echo $sp."  </TABLE>\n";
    }
    echo $sp."</DIV>\n";
    wnd2_end();
  }

  /* ======== requirements ======== */

  /* box for a normal require box with image and name */
  function my_tree_require_element_small($research, $level, &$show_requirements, &$img) {
    global $sp, $l, $lang, $box_id;
    global $treepath, $imagepath_resource_box;
    wnd2_start();
    $title = $l[$research][$lang]." ".$level;
    echo $sp."<DIV class=\"rt_box\">\n";
    echo $sp."  <A name=\"".$research.$level."r"."\" href=\"".$treepath."&amp;exp=".$research.$level."r"."#".$research.$level."r"."\" class=\"wnd_header_txt\">\n";
    echo $sp."    <IMG width=\"110\" height=\"70\" src=\"".$imagepath_resource_box.$img."\" alt=\"".$title."\" title=\"".$title."\" class=\"rt_img\"><BR>\n";
    echo $sp."  </A>\n";
    echo $sp."  <DIV style=\"text-align: center; width: 110px;\">\n";
    //echo $sp."    <A href=\"".$treepath."&amp;exp=".$research.$level."r"."#".$research.$level."r"."\" class=\"wnd_header_txt\" onmouseover=\"ShowHide('e".$box_id."', 'n".$box_id."');\">".$title." ".$l['requirements'][$lang]."</A>\n";
    echo $sp."    <A href=\"".$treepath."&amp;exp=".$research.$level."r"."#".$research.$level."r"."\" class=\"wnd_header_txt\" onmouseover=\"ShowThisHideOthers('e".$box_id."');\">".$title."<BR>".$l['requirements'][$lang]."</A>\n";
    echo $sp."  </DIV>\n";
    echo $sp."</DIV>\n";
    wnd2_end();
  }
  
  /* box for a large require box with image, name and requirements */
  function my_tree_require_element_large($research, $level, &$show_requirements, &$img, $as_pop_up=false) {
    global $sp, $l, $lang;
    global $treepath, $imagepath_resource_box;
    wnd2_start($as_pop_up);
    $title = $l[$research][$lang]." ".$level;
    if ($as_pop_up)  {
      echo $sp."<TABLE border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width: 100%;\">\n";
    } else {
      echo $sp."<TABLE border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
    }
    echo $sp."  <TR>\n";
    echo $sp."    <TD style=\"vertical-align: top;\">\n";
    echo $sp."      <DIV class=\"rt_in_box\">\n";
    echo $sp."        <A name=\"".$research.$level."r"."\" href=\"".$treepath."#".$research.$level."r"."\" class=\"wnd_header_txt\">\n";
    echo $sp."          <IMG width=\"110\" height=\"70\" src=\"".$imagepath_resource_box.$img."\" alt=\"".$title."\" title=\"".$title."\" class=\"rt_img\"><BR>\n";
    echo $sp."        </A>\n";
    echo $sp."        <DIV style=\"text-align: center;\">\n";
    if ($as_pop_up) {
      echo $sp."          <A href=\"".$treepath."&amp;exp=".$research.$level."r"."#".$research.$level."r"."\" class=\"wnd_header_txt\">".$title."<BR>".$l['requirements'][$lang]."</A>\n";
    } else {
      echo $sp."          <A href=\"".$treepath."#".$research.$level."r"."\" class=\"wnd_header_txt\">".$title."<BR>".$l['requirements'][$lang]."</A>\n";
    }
    echo $sp."        </DIV>\n";
    echo $sp."      </DIV>\n";
    echo $sp."    </TD>\n";
    echo $sp."  </TR>\n";
    echo $sp."  <TR>\n";
    echo $sp."    <TD>\n";
    html_indent(6);
    my_tree_needed_buildings($show_requirements, true); 
    html_unindent(6);
    echo $sp."    </TD>\n";
    echo $sp."  </TR>\n";
    echo $sp."</TABLE>\n";
    wnd2_end();
  }

  /* ======== abilities ======== */

  /* box for a normal require box with image and name */
  function my_tree_ability_element_small($research, $level, &$show_ability, &$img) {
    global $sp, $l, $lang, $box_id;
    global $treepath, $imagepath_resource_box;
    wnd2_start();
    $title = $l[$research][$lang]." ".$level;
    echo $sp."<DIV class=\"rt_box\">\n";
    echo $sp."  <A name=\"".$research.$level."a"."\" href=\"".$treepath."&amp;exp=".$research.$level."a"."#".$research.$level."a"."\" class=\"wnd_header_txt\">\n";
    echo $sp."    <IMG width=\"110\" height=\"70\" src=\"".$imagepath_resource_box.$img."\" alt=\"".$title."\" title=\"".$title."\" class=\"rt_img\"><BR>\n";
    echo $sp."  </A>\n";
    echo $sp."  <DIV style=\"text-align: center; width: 110px;\">\n";
    //echo $sp."    <A href=\"".$treepath."&amp;exp=".$research.$level."a"."#".$research.$level."a"."\" class=\"wnd_header_txt\" onmouseover=\"ShowHide('e".$box_id."', 'n".$box_id."');\">".$title." ".$l['abilities'][$lang]."</A>\n";
    echo $sp."    <A href=\"".$treepath."&amp;exp=".$research.$level."a"."#".$research.$level."a"."\" class=\"wnd_header_txt\" onmouseover=\"ShowThisHideOthers('e".$box_id."');\">".$title."<BR>".$l['abilities'][$lang]."</A>\n";
    echo $sp."  </DIV>\n";
    echo $sp."</DIV>\n";
    wnd2_end();
  }
  
  /* box for a large require box with image, name and requirements */
  function my_tree_ability_element_large($research, $level, &$show_ability, &$img, $as_pop_up=false) {
    global $sp, $l, $lang;
    global $treepath, $imagepath_resource_box;
    wnd2_start($as_pop_up);
    $title = $l[$research][$lang]." ".$level;
    if ($as_pop_up)  {
      echo $sp."<TABLE border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width: 100%;\">\n";
    } else {
      echo $sp."<TABLE border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
    }
    echo $sp."  <TR>\n";
    echo $sp."    <TD style=\"vertical-align: top;\">\n";
    echo $sp."      <DIV class=\"rt_in_box\">\n";
    echo $sp."        <A name=\"".$research.$level."a"."\" href=\"".$treepath."#".$research.$level."a"."\" class=\"wnd_header_txt\">\n";
    echo $sp."          <IMG width=\"110\" height=\"70\" src=\"".$imagepath_resource_box.$img."\" alt=\"".$title."\" title=\"".$title."\" class=\"rt_img\"><BR>\n";
    echo $sp."        </A>\n";
    echo $sp."        <DIV style=\"text-align: center;\">\n";
    if ($as_pop_up) {
      echo $sp."          <A href=\"".$treepath."&amp;exp=".$research.$level."a"."#".$research.$level."a"."\" class=\"wnd_header_txt\">".$title."<BR>".$l['abilities'][$lang]."</A>\n";
    } else {
      echo $sp."          <A href=\"".$treepath."#".$research.$level."a"."\" class=\"wnd_header_txt\">".$title."<BR>".$l['abilities'][$lang]."</A>\n";
    }
    echo $sp."        </DIV>\n";
    echo $sp."      </DIV>\n";
    echo $sp."    </TD>\n";
    echo $sp."  </TR>\n";
    echo $sp."  <TR>\n";
    echo $sp."    <TD>\n";
    html_indent(6);
    wnd2_start(true);
    echo $sp."<DIV class=\"wnd_gen_txt\" style=\"text-align: center;\">\n";
    echo $sp."  ".$l['provides'][$lang]."<BR><BR>\n";
    echo $sp."  ".$show_ability[$lang]."\n";
    echo $sp."</DIV>\n";
    wnd2_end();
    html_unindent(6);
    echo $sp."    </TD>\n";
    echo $sp."  </TR>\n";
    echo $sp."</TABLE>\n";
    wnd2_end();
  }

  /* ======== main ======== */

  function techtree() {  
    global $sp, $l, $lang, $defaultlang, $race, $box_id;
    global $buildings, $research_short, $stylepath_dsstyle, $stylepath_mystyle;
  
    $box_id = 0;
  
    /* HTML head */
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">\n";
    echo "<HTML>\n";
    echo "  <HEAD>\n";
    echo "    <TITLE>".$l['researchtree'][$lang]."</TITLE>\n";
    echo "    <META http-equiv=\"Content-Type\" content=\"text/HTML; charset=iso-8859-1\">\n";
    echo "    <LINK rel=\"stylesheet\" href=\"".$stylepath_dsstyle."dsstyle.css\" type=\"text/css\">\n";
    echo "    <LINK rel=\"stylesheet\" href=\"".$stylepath_mystyle."techtree.css\" type=\"text/css\">\n";
    echo "    <LINK rel=\"stylesheet\" href=\"".$stylepath_mystyle."wnd.css\" type=\"text/css\">\n";
    echo "  </HEAD>\n";
    echo "\n";
    echo "  <BODY>\n";
    echo "\n";
    echo "    <SCRIPT type=\"text/javascript\">\n";
    echo "    <!--\n";
    /*
    echo "      function ShowHide (showthis, hidethis) {\n";
    //echo "        alert('JavaScript aktiv.');\n";
    echo "        document.getElementById(showthis).style.display = 'block';\n";
    echo "        document.getElementById(hidethis).style.display = 'none';\n";
    echo "      }\n";
    */
    /*
    echo "      function ShowFirst (showthis) {\n";
    echo "        document.getElementById(showthis).style.display = 'block';\n";
    echo "        window.setTimeout( \"HideAgain( '\"+showthis+\"')\", 1000);\n";
    echo "      }\n";
    */
    echo "      function ShowThis (showthis) {\n";
    echo "        document.getElementById(showthis).style.display = 'block';\n";
    echo "      }\n";
    echo "      function HideThis (hidethis) {\n";
    echo "        document.getElementById(hidethis).style.display = 'none';\n";
    //echo "        window.setTimeout( \"HideAgain( '\"+hidethis+\"')\", 1000);\n";
    echo "      }\n";
    /*
    echo "      function HideAgain (hidethis) {\n";
    echo "        document.getElementById(hidethis).style.display = 'none';\n";
    echo "      }\n";
    */
    /*
    echo "      function HideAll () {\n";
    echo "        for (var i = 0; i <= 200; i++) {\n";
    echo "          document.getElementById(\"e\" + i).style.display = 'none';\n";
    echo "        }\n";
    echo "      }\n";
    */
    echo "      function ShowThisHideOthers (showthis) {\n";
    echo "        document.getElementById(showthis).style.display = 'block';\n";
    echo "        for (var i = 0; document.getElementById(\"e\" + i) && i <= 1000; i++) {\n";
    echo "          if (\"e\" + i != showthis) {\n";
    echo "            document.getElementById(\"e\" + i).style.display = 'none';\n";
    echo "          }\n";
    echo "        }\n";
    echo "      }\n";
    
    echo "    //-->\n";
    echo "    </SCRIPT>\n";
    

    /* prepare indention */
    $sp = "";
    html_indent(4);

    /* main frame with caption, start */
    wnd_start($l['researchtree'][$lang]);

    /* selection for all three races */
    echo $sp."<DIV style=\"text-align: center;\">\n";
    echo $sp."  <HR class=\"wnd_ruler\">\n";
    echo $sp."  <SPAN class=\"wnd_header_txt_large\">".$l[$race][$lang]."</SPAN>\n";
    echo $sp."  <HR class=\"wnd_ruler\">\n";
    echo $sp."  <SPAN class=\"wnd_header_txt\">\n";
    if ($lang == $defaultlang) {
      echo $sp."    <A href=\"?race=voraner\" class=\"wnd_header_txt\">".$l['voraner'][$lang]."</A>&nbsp;|&nbsp;\n";
      echo $sp."    <A href=\"?race=liensu\" class=\"wnd_header_txt\">".$l['liensu'][$lang]."</A>&nbsp;|&nbsp;\n";
      echo $sp."    <A href=\"?race=psolao\" class=\"wnd_header_txt\">".$l['psolao'][$lang]."</A>\n";
    } else {
      echo $sp."    <A href=\"?lang=".$lang."&amp;race=voraner\" class=\"wnd_header_txt\">".$l['voraner'][$lang]."</A>&nbsp;|&nbsp;\n";
      echo $sp."    <A href=\"?lang=".$lang."&amp;race=liensu\" class=\"wnd_header_txt\">".$l['liensu'][$lang]."</A>&nbsp;|&nbsp;\n";
      echo $sp."    <A href=\"?lang=".$lang."&amp;race=psolao\" class=\"wnd_header_txt\">".$l['psolao'][$lang]."</A>\n";
    }
    echo $sp."  </SPAN>\n";
    echo $sp."</DIV>\n";
    echo $sp."<BR>\n";
    echo $sp."\n";

    html_unindent(16); /* make transmission faster (don't indent too much) */

    /* start the research tree table */
    echo $sp."<!-- research table -->\n";
    echo $sp."\n";
    echo $sp."<TABLE class=\"rt\" border=0 cellpadding=0 cellspacing=4>\n";
    echo $sp."\n";

    /* COLGROUP might be usefull, if width of wnd is enlarged by setting it to 100% */

    echo $sp."  <COLGROUP>\n";
    echo $sp."    <COL>\n";
    echo $sp."    <COL>\n";
    echo $sp."    <COL>\n";
    echo $sp."    <COL>\n";
    echo $sp."    <COL>\n";
    echo $sp."    <COL>\n";
    echo $sp."    <COL>\n";
    echo $sp."  </COLGROUP>\n";
    echo $sp."\n";


    /* research tree table - head row start */
    echo $sp."  <TR>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('kaderium');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('nano');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('energia');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('eda');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('prodium');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('trade');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('spy');
    echo $sp."    </TD>\n";
    echo $sp."  </TR>\n";
    echo $sp."\n";
    /* research tree table - head row end */

    /* research tree table - for ech of the 13 research level ... */
    $maxlevel = 13;
    for ($level = 1; $level <= $maxlevel; $level++) {

      /* research tree table - research level start */
      echo $sp."  <TR>\n";
      echo $sp."    <TD class=\"rt\">\n";
      echo $sp."      <DIV style=\"height: 12px;\"></DIV>\n";
      my_tree_level_element($level, "kaderium");
      echo $sp."      <DIV style=\"height: 4px;\"></DIV>\n";
      my_tree_elements("kaderium", $level, false, false, ($level >= $maxlevel));
      echo $sp."    </TD>\n";
      echo $sp."    <TD class=\"rt\">\n";
      echo $sp."      <DIV style=\"height: 12px;\"></DIV>\n";
      my_tree_level_element($level, "nano");
      echo $sp."      <DIV style=\"height: 4px;\"></DIV>\n";
      my_tree_elements("nano", $level, false, false, ($level >= $maxlevel));
      echo $sp."    </TD>\n";
      echo $sp."    <TD class=\"rt\">\n";
      echo $sp."      <DIV style=\"height: 12px;\"></DIV>\n";
      my_tree_level_element($level, "energia");
      echo $sp."      <DIV style=\"height: 4px;\"></DIV>\n";
      my_tree_elements("energia", $level, false, false, ($level >= $maxlevel));
      echo $sp."    </TD>\n";
      echo $sp."    <TD class=\"rt\">\n";
      echo $sp."      <DIV style=\"height: 12px;\"></DIV>\n";
      my_tree_level_element($level, "eda");
      echo $sp."      <DIV style=\"height: 4px;\"></DIV>\n";
      my_tree_elements("eda", $level, false, false, ($level >= $maxlevel));
      echo $sp."    </TD>\n";
      echo $sp."    <TD class=\"rt\">\n";
      echo $sp."      <DIV style=\"height: 12px;\"></DIV>\n";
      my_tree_level_element($level, "prodium");
      echo $sp."      <DIV style=\"height: 4px;\"></DIV>\n";
      my_tree_elements("prodium", $level, false, false, ($level >= $maxlevel));
      echo $sp."    </TD>\n";
      echo $sp."    <TD class=\"rt\">\n";
      if ($level <= 10) {
        echo $sp."      <DIV style=\"height: 12px;\"></DIV>\n";
        my_tree_level_element($level, "trade");
        echo $sp."      <DIV style=\"height: 4px;\"></DIV>\n";
        my_tree_elements("trade", $level, false, true, ($level >= $maxlevel));
      }
      echo $sp."    </TD>\n";
      echo $sp."    <TD class=\"rt\">\n";
      if ($level <= 10) {
        echo $sp."      <DIV style=\"height: 12px;\"></DIV>\n";
        my_tree_level_element($level, "spy");
        echo $sp."      <DIV style=\"height: 4px;\"></DIV>\n";
        my_tree_elements("spy", $level, false, true, ($level >= $maxlevel));
      }
      echo $sp."    </TD>\n";
      echo $sp."  </TR>\n";
      echo $sp."\n";
      /* research tree table - research level end */

    }    

    /* research tree table - bottom row start */
    echo $sp."  <TR>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('kaderium');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('nano');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('energia');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('eda');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('prodium');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('trade');
    echo $sp."    </TD>\n";
    echo $sp."    <TD class=\"rt\">\n";
    my_tree_head_element('spy');
    echo $sp."    </TD>\n";
    echo $sp."  </TR>\n";
    echo $sp."\n";
    /* research tree table - bottom row end */

    /* finish the research tree table */
    echo $sp."</TABLE>\n";
    echo $sp."<!-- research table -->\n";
    echo $sp."\n";

    html_indent(16); /* make transmission faster (don't indent too much) */

    /* check integrity of the $buildings array in 'buildings.php' */
    echo checkLevel($buildings, 13);
    echo checkInsert($buildings, 13, $research_short);
    echo checkRequirements($buildings);
    echo checkRace($buildings, $race);

    /* the author and thanks */
    echo $sp."\n";
    echo $sp."<DIV style=\"text-align: center;\">\n";
    echo $sp."  <SPAN class=\"wnd_gen_txt\"><BR>created by <SPAN style=\"color: #FF8800\">/me</SPAN> - thanks to sto&#96;teac for ideas and help.</SPAN>\n";
    echo $sp."</DIV>\n";
    echo $sp."\n";

    /* main frame (with caption), end */
    wnd_end();

    echo "  </BODY>\n";
    echo "\n";
    echo "</HTML>\n";
    
  }

  // show the tree
  techtree();

?>