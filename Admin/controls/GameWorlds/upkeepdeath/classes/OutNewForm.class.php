<?php
class OutNewForm
{
    //static public function InForm($params = null)
    static public function InForm($params)
    {
        //assert(is_null($params) || is_array($params));

        $a = $params->get('WORLD_NAME');
        $b = $params->get('USER_NAME');
        $d = $params->get('DAY');
        $m = $params->get('MOUTH');
        $y = $params->get('YEAR');
        $d2 = $params->get('DAY2');
        $m2 = $params->get('MOUTH2');
        $y2 = $params->get('YEAR2');

        //$data1=$d.'-'.$m.'-'.$y;
        $data1=$y.'-'.$m.'-'.$d;
        //$data2=$d2.'-'.$m2.'-'.$y2;
        $data2=$y2.'-'.$m2.'-'.$d2;

        self::_out('<h1>Unit death log</h1>');

        self::_out('<form action="'.$_SERVER['PHP_SELF'].'" method="POST">');

        self::_out('<select name="WORLD_NAME">');

        $worldsList = Search::GetWorldList();

        if ($params->get('ACTION') == 'showlist')
        {
            foreach ($worldsList as $k=>$worldData)
            {
                if ($worldData['dbId']."_".$worldData['id_world']==$a)
                {
                    self::_out("<option value=".$worldData['dbId']."_".$worldData['id_world']." selected>");
                    self::_out($worldData['worldname']);
                    self::_out('</option>');
                }
                else
                {
                    self::_out("<option value=".$worldData['dbId']."_".$worldData['id_world'].">");
                    self::_out($worldData['worldname']);
                    self::_out('</option>');
                }
            }
        }

        if ($params->get('ACTION') != 'showlist')
        {
            foreach ($worldsList as $k=>$worldData)
            {

                self::_out("<option value=".$worldData['dbId']."_".$worldData['id_world'].">");
                self::_out($worldData['worldname']);
                self::_out('</option>');
            }
        }
        self::_out('</select> ');

        self::_out('<input type=text name="USER_NAME" value='.$b.'>');

        self::_out('<br>');
        self::_out('<br>');
        self::_out('<h1>');
        self::_out('Choose add date : ');
        self::_out('</h1>');

        self::_out('<font color=white>From:</font>');

        self::_out('<select name="YEAR">');
        if ($params->get('ACTION')==showlist)
        {
            for ($year=(date('Y')-3); $year<=date('Y'); $year++)
            {
                if ($year==$y)
                {
                    self::_out('<option selected>');
                    self::_out($year);
                    self::_out('</option>');
                }
                else
                {
                    self::_out('<option>');
                    self::_out($year);
                    self::_out('</option>');
                }
            }
        }
        else
        {
            self::_out('<option selected>2007</option>');
            self::_out('<option>2008</option>');
            self::_out('<option>2009</option>');
            self::_out('<option>2010</option>');
        }

        self::_out('</select>');

        self::_out('</select>');
        self::_out('<select name="MOUTH">');

        for ($mouth=1; $mouth<=12; $mouth++)
        {
            if ($params->get('ACTION')==showlist AND $mouth==$m)
            {
                self::_out('<option selected>');
                self::_out($mouth);
                self::_out('</option>');
            }
            else
            {
                self::_out('<option>');
                self::_out($mouth);
                self::_out('</option>');
            }
        }

        self::_out('</select>');

        self::_out('<select name="DAY">');

        for ($day=01; $day<=31; $day++)
        {
            if ($params->get('ACTION')==showlist AND $day==$d)
            {
                self::_out('<option selected>');
                self::_out($day);
                self::_out('</option>');
            }
            else
            {
                self::_out('<option>');
                self::_out($day);
                self::_out('</option>');
            }
        }

        self::_out('</select>');

        self::_out(' <font color=white>To:</font> ');

        self::_out('<select name="YEAR2" >');

        if($params->get('ACTION') == 'showlist')
        {
            $select = $y2;
        } else {
            $select = date('Y');
        }

        for($year=(date('Y')-3); $year<=date('Y'); $year++)
        {
            if($year == $select)
            {
                self::_out('<option selected>');
                self::_out($year);
                self::_out('</option>');
            } else {
                self::_out('<option>');
                self::_out($year);
                self::_out('</option>');
            }
        }

        self::_out('</select>');

        self::_out('<select name="MOUTH2">');

        if($params->get('ACTION') == 'showlist')
        {
            $select = $m2;
        } else {
            $select = date('n');
        }
        
        for($mouth=1; $mouth<=12; $mouth++)
        {
            if($mouth == $select)
            {
                self::_out('<option selected>');
                self::_out($mouth);
                self::_out('</option>');
            } else {
                self::_out('<option>');
                self::_out($mouth);
                self::_out('</option>');
            }
        }

        self::_out('</select>');

        self::_out('<select name="DAY2">');

        if($params->get('ACTION') == 'showlist')
        {
            $select = $d2;
        } else {
            $select = date('j');
        }
        
        
        for ($day=01; $day<=31; $day++)
        {
            if ($day == $select)
            {
                self::_out('<option selected>');
                self::_out($day);
                self::_out('</option>');
            } else {
                self::_out('<option>');
                self::_out($day);
                self::_out('</option>');
            }
        }

        self::_out('</select>');

        self::_out('<br>');
        self::_out('<br>');
        self::_out('<input type="hidden" name="ACTION" value="showlist">');
        self::_out('<input class="submit" type="submit" name="SUBMIT" value=" Show ">');
        self::_out('</form>');

        if ($params->get('ACTION') == 'showlist')
        {
            $combid = Search::GetUnit($a, $b, $data1, $data2);

            self::_out("<table border=1>");
            self::_out("<tr>");
            self::_out("<td><center><b>");
            self::_out("Unitname:");
            self::_out("</b></center></td>");

            self::_out("<td><center><b>");
            self::_out("add_date:");
            self::_out("</b></center></td>");

            self::_out("<td><center><b>");
            self::_out("check_date:");
            self::_out("</b></center></td>");

            self::_out("<td><center><b>");
            self::_out("Combname:");
            self::_out("</b></center></td>");;

            self::_out("<td><center><b>");
            self::_out("before_amount:");
            self::_out("</b></center></td>");

            self::_out("<td><center><b>");
            self::_out("death_amount:");
            self::_out("</b></center></td>");

            self::_out("<td><center><b>");
            self::_out("reason_name:");
            self::_out("</b></center></td>");

            self::_out("</tr>");

            for ($i=0; $i<count($combid); $i++ )
            {

                self::_out("<tr>");

                self::_out("<td>");
                self::_out($combid[$i]['unitname']);
                self::_out("</td>");

                self::_out("<td>");
                self::_out($combid[$i]['add_date']);
                self::_out("</td>");

                self::_out("<td>");
                self::_out($combid[$i]['check_date']);
                self::_out("</td>");

                self::_out("<td>");
                self::_out($combid[$i]['combname']);
                self::_out("</td>");

                self::_out("<td>");
                self::_out($combid[$i]['before_amount']);
                self::_out("</td>");


                self::_out("<td>");
                self::_out($combid[$i]['death_amount']);
                self::_out("</td>");


                self::_out("<td>");
                self::_out($combid[$i]['reason_name']);
                self::_out("</td>");

                self::_out("</tr>");

            }
            self::_out('</table>');
        }
    }

    private static function _out($par)
    {
        echo $par."\n";
    }
}

?>