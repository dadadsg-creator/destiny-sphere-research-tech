<?
# Version 4.2   16.07.2002

if (ini_get("magic_quotes_gpc") == 0)
{
    while (list($key,$val)=each($_POST))
    {
        $_POST[$key] = addslashes($val);
    }
}

function UploadFile(&$Field,&$Data,&$Error,&$Result,&$MimeType)
{
    $ret = false;
    $filename = $_FILES['Field'.$Field]['tmp_name'];
    $userfilename = $_FILES['Field'.$Field]['name'];
    $mimetype = $_FILES['Field'.$Field]['type'];
    $filesize= $_FILES['Field'.$Field]['size'];

    if ($filename && $filename != "none")
    {
        if ($Data['Type']['MimeType'] && (!in_array($mimetype,$Data['Type']['MimeType'])))
        {
            $Error .= local('ADMIN_NOT_CORRECT_FORMAT_DATA')." <b>\"".$Data[Caption]."\"</b> - ".$mimetype."<br>";
        } else {
            if ($Data['Type']['Name'] == "UploadFile")
            {
                $upload_dir = $Data['Type']['UploadDir'];
                if ($Data['Type']['ReplaceDuplicate'])
                {
                    $localfilename = $userfilename;
                } else {
                    do  { $localfilename = substr($userfilename,0,strlen($userfilename)-4) ."_". substr(md5(time()),0,4) . substr($userfilename,-4); } while (file_exists("$upload_dir/$localfilename"));
                }
                //              if (file_exists("$upload_dir/$localfilename")) chmod ("$upload_dir/$localfilename",0755);
                if(!file_exists($filename)){
                    $Error .= "<li>Не найден закачанный файл\"<b>".$filename."</b>\"<br>";
                }
                
                if (copy($filename,"$upload_dir/$localfilename"))
                {
                    $ret = true;
                    $Result = $localfilename;
                    if (file_exists($upload_dir."/".$localfilename))
                    {
                        chmod ($upload_dir."/".$localfilename,0755);
                    }
                } else {
                    $Error .= "<li>Ошибка закачки файла (1) \"<b>".$userfilename."</b> в ".$upload_dir."/".$localfilename." <br>";
                }
            }
            if ($Data[Type][Name] == "Blob")
            {
                $fd = fopen($filename, "r");
                if ($fd)
                {
                    $buf = "";
                    while (!feof($fd)) $buf .= fread($fd, 512);
                    fclose($fd);
                    $buf = file_get_contents ($filename);
                    $Result =  AddSlashes($buf);
                    $ret = true;
                } else {
                    $Error .= "<li>Ошибка закачки файла (2) \"<b>".$userfilename."</b>\"<br>";
                }
            }
            $MimeType = $mimetype;
        }
    }
    return $ret;
}

function DeleteFile($filename)
{
    if (file_exists($filename))
    {
        $ret = unlink($filename);
        return $ret;
    }
}

function Is_Email($email)
{
    $ret = (strval($email)=="") ? true : false;
    if(strstr($email, '@') && strstr($email, '.'))
    {
        if(eregi("^([_a-z0-9]+([\\._a-z0-9-]+)*)@([a-z0-9]{2,}(\\.[a-z0-9-]{2,})*\\.[a-z]{2,3})$", $email))
        {
            $ret=true;
        }
    }
    return $ret;
}

class SimpleEdit
{
    var $conn_id;
    var $Table = array();
    var $Fields = array();
    var $SearchFields = array();
    var $Lookup = array();
    var $Bind = array();
    var $Options = array(); // forbiddelite
    var $Params = array();
    var $FormEnctype;
    var $NEW_ID_VALUE;
    var $OUTPUT_STR = "";

    // groub by pages
    var $RecordOnPage = 30;
    var $PagesOnNavigationLine = 10;
    var $Page = 1;
    var $imos;
    var $CountRecord,$CountPage,$StartPage,$EndPage;
    var $tpl; //Object Templates Master
    var $assign;
    var $Error,$InsertError,$UpdateError;

    function SimpleEdit (&$Table,&$Fields,&$Options)
    {
        $this->conn_id = getMysqlConnection();

        if ($Options[Theme][Name] && $Options[Theme][Dir])
        {
            //$_SERVER['DOCUMENT_ROOT'].'/templates/Admin/'
            //$this->tpl = new FastTemplate ($Options[Theme][Dir].$Options[Theme][Name].'/');
            $this->tpl = new FastTemplate ($_SERVER['DOCUMENT_ROOT'].'/templates/Admin/default/');

        }
        $this->InitSimpleEdit($Table,$Fields,$Options);

    }

    function InitSimpleEdit (&$Table,&$Fields,&$Options)
    {
        global $_GET,$_POST;

        $this->Table = $Table;
        $this->Fields = $Fields;
        $this->Options = $Options;
        $this->OUTPUT_STR = "";
        while (list($Field,$Data) = each($Fields))
        {
            if ($Data[Search])
            {
                //$this->SearchFields[$Field] = ($_POST["Search"]) ? $_POST["Part$Field"] : $_GET["Part$Field"];
                if ($_POST["Search"])
                {
                    $this->SearchFields[$Field] = $_POST["Part$Field"];
                } else {
                    $this->SearchFields[$Field] = ($_POST["Part$Field"]) ? $_POST["Part$Field"] : $_GET["Part$Field"];
                }
            }
            if ($Data[Lookup])
            {
                if ($Data[Lookup][Type] == "Master")
                {
                    $Query = "SELECT ".$Data[Lookup][ParentColumn].",".$Data[Lookup][LookupColumn]." FROM ".$Data[Lookup][ParentTable];
                    if ($Data['Lookup']['Where']) $Query .= " WHERE ".$Data['Lookup']['Where'];
                    if ($Data['Lookup']['Order']) $Query .= " ORDER BY ".$Data[Lookup][Order];
                    //echo $Query;
                    $result = mysql_db_query(DBName,$Query,$this->conn_id);
                    if ($result) {
                        while ($row = mysql_fetch_row($result)) $this->Lookup[$Field][$row[0]] = getLocalize($row[1]);
                    }  else {
                        $this->Error .= $this->getMysqlError($Query);
                    }
                }
                if ($Data[Lookup][Type] == "Local")
                {
                    $this->Lookup[$Field] = $Data[Lookup][Map];
                }
            }
            if (in_array($Data[Type][Name],array("Blob","UploadFile"))) $this->FormEnctype = "multipart/form-data";
        }
    }

    function ProcessEvent($Params=NULL,$InitFields=NULL)
    {
        global $SERVER_NAME,$_POST,$_GET;

        $this->Params = $Params;
        if (!$this->Params[Page]) $this->Params[Page] = $_GET[page];
        if ($this->Params[Page]) $this->Page = $this->Params[Page];
        switch (strval($this->Params[action]))
        {
            case "new":
            if ($this->Options[ForbidInsert])
            {
                $ErrMsg = ($this->Options[ForbidInsert][ErrMsg]) ? $this->Options[ForbidInsert][ErrMsg] : ShowCMSMessage(local('ADMIN_NOT_RIGHTS_ADD_DATA'), 2);
                $this->Output("<font color=red><span class=error>".$ErrMsg."</span></font><br>");
            } else {
                $this->InitBind();
                reset($this->Fields);
                while (list($Field,$Data) = each($this->Fields))
                $this->Fields[$Field][Value] = $this->Fields[$Field][DefaultValue];
                $result = $this->EditTable("add");
                $this->Output($result);
            }
            break;
            case "edit":
            if ($this->Options[ForbidEdit])
            {
                $ErrMsg = ($this->Options[ForbidEdit][ErrMsg]) ? $this->Options[ForbidEdit][ErrMsg] : ShowCMSMessage(local('ADMIN_NOT_RIGHTS_VIEW_DATA'), 2);
                $this->Output("<font color=red><span class=error>".$ErrMsg."</span></font><br>");
            } else {
                $this->Init($Params['ID']);
                //if ($this->Error) $this->Output($this->Error);
                $result = $this->EditTable("update");
                $this->Output($result);
            }
            break;
            case "apply":
            case "update":
            if ($this->Options[ForbidUpdate])
            {
                $ErrMsg = ($this->Options[ForbidUpdate][ErrMsg]) ? $this->Options[ForbidUpdate][ErrMsg] : ShowCMSMessage(local('ADMIN_NOT_RIGHTS_EDIT_DATA'), 2);
                $this->Output("<font color=red><span class=error>".$ErrMsg."</span></font><br>");
            } else {
                $ListFields = array();
                reset($this->Fields);
                while (list($Field,$Data) = each($this->Fields))
                {
                    if ($Data[Edit][Caption]) $Data[Caption] = $Data[Edit][Caption];
                    if ($Data[Type][Mandatory] && ($_POST["Field$Field"]=="")) $this->UpdateError .= ShowCMSMessage(local('ADMIN_FIELD_NOT_FILLED'), 2)." <b>\"".$Data[Caption]."\"</b><br>";
                    if ($Data[Edit] && !$Data[ForbidUpdate])
                    {
                        if ($Data[Unique] && ($_POST["Field$Field"]))
                        {
                            $Query = "SELECT count(*) FROM ".$this->Table[Name]." WHERE ".$Field."='".$_POST["Field$Field"]."' AND " . $this->Table[PrimaryKey] ."<>'".$this->Params['ID']."'";
                            //                              $this->Output($Query."<br>");
                            $result = mysql_db_query(DBName,$Query,$this->conn_id);
                            if ($result)
                            {
                                $row = mysql_fetch_row($result);
                                if ($row[0] != 0)
                                {
                                    $this->UpdateError .= ShowCMSMessage(local('ADMIN_VALUE_NOT_UNIQUE',$Data['Caption']), 2)."<br>";
                                }
                            } else $this->Error .= $this->getMysqlError($Query);
                        }
                        switch ($Data[Type][Name])
                        {
                            case "Float":
                            $string_value = str_replace(",",".",$_POST["Field$Field"]);
                            $double_value = (double) $string_value;
                            if (( ((string) $double_value) != $string_value) && ($string_value != ""))
                            {
                                $this->UpdateError .= ShowCMSMessage(local('ADMIN_NOT_CORRECT_FORMAT_DATA'), 2)." <b>\"".$Data[Caption]."\"</b><br>";
                            }
                            array_push($ListFields,"$Field='".$double_value."'");
                            break;
                            case "Blob":
                            case "UploadFile":
                            if ($_POST["Replace$Field"]=="Delete")
                            {
                                array_push($ListFields,"$Field=null");
                                if ( ($Data[Type][Name]=="UploadFile") &&  (!$Data[Type][ReplaceDuplicate]) )
                                {
                                    $Query = "SELECT ".$Field." FROM ".$this->Table[Name]." WHERE ".$this->Table[PrimaryKey]."='".$this->Params['ID']."'";
                                    //echo $Query;
                                    $result = mysql_db_query(DBName,$Query,$this->conn_id);
                                    if ($result) {
                                        $row = mysql_fetch_row($result);
                                        DeleteFile($Data[Type][UploadDir].$row[0]);
                                    } else $this->Error .= $this->getMysqlError($Query);
                                }
                            } else {
                                if ($_POST["Replace$Field"]=="Change")
                                {
                                    if (UploadFile($Field,$Data,$this->UpdateError,$UploadData,$UploadMimeType))
                                    {
                                        array_push($ListFields,"$Field='".$UploadData."'");
                                        if ($Data[SaveMimeType]) array_push($ListFields,$Data[SaveMimeType]."='".$UploadMimeType."'");
                                        if ($Data[SaveImageSize])
                                        {
                                            eval("global \$Field".$Field.";");
                                            eval("\$filename=\$Field".$Field.";");
                                            if ($ImageSize = GetImageSize($filename))
                                            {
                                                array_push($ListFields,$Data[SaveImageSize][Width]."='".$ImageSize[0]."'");
                                                array_push($ListFields,$Data[SaveImageSize][Height]."='".$ImageSize[1]."'");
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                            case "Date":
                            $stamp = mktime(0, 0, 0, $_POST["Field".$Field."_month"], $_POST["Field".$Field."_day"],  $_POST["Field".$Field."_year"]);
                            $date_str = ($stamp == mktime(0, 0, 0, 0, 0, 0)) ? "0000-00-00" : date('Y', $stamp) ."-". date('m', $stamp) ."-".  date('d', $stamp);
                            array_push($ListFields,"$Field='".$date_str."'");
                            break;
                            case "Datetime":
                            $stamp = mktime($_POST["Field".$Field."_hour"], $_POST["Field".$Field."_minute"], 0, $_POST["Field".$Field."_month"], $_POST["Field".$Field."_day"],  $_POST["Field".$Field."_year"]);
                            $date_str = ($stamp == mktime(0, 0, 0, 0, 0, 0)) ? "0000-00-00 00:00:00" : date('Y', $stamp) ."-". date('m', $stamp) ."-".  date('d', $stamp) . " " . date('H', $stamp) . ":" . date('i', $stamp) . ":" . date('s', $stamp);
                            array_push($ListFields,"$Field='".$date_str."'");
                            break;
                            case "Checkbox":
                            $value = ($_POST["Field$Field"]) ? $Data[Values][Checked] : $Data[Values][UnChecked];
                            array_push($ListFields,"$Field='".$value."'");
                            break;
                            case "Password":

                            if (!empty($_POST["Field$Field"]))
                            {
                                $value = md5($_POST["Field$Field"]);
                                array_push($ListFields,"$Field='".$value."'");
                            }
                            break;
                            default:
                            if ($Data[Type][Name]=="HTML")
                            {
                                $value = $_POST["Field$Field"];
                                $value = preg_replace("/src=(\\\\\")?(http:\/\/".$SERVER_NAME."(:[0-9]+)?)\/(.+?.(gif|jpg))(\\\\\"|\s)/i","src=\"\\4\"",$value);
                            } else {
                                $value = $_POST["Field$Field"];
                            }
                            $value = str_replace("#textarea#","textarea",$value);
                            array_push($ListFields,"$Field='".$value."'");
                            if ($Data[Type][Name]=="Email") if (!Is_Email($value)) $this->UpdateError .= "<li>".local('ADMIN_EMAIL_NOT_CORRECT')." <b>\"".$Data[Caption]."\"</b><br>";
                        }
                    }
                }

                if (!$this->UpdateError)
                {
                    $Query = "UPDATE " . $this->Table[Name] ." SET " . join(",",$ListFields) . " WHERE  " . $this->Table[PrimaryKey] ."='".$this->Params['ID']."'";
                    //UPDATE
                    //$this->Output($Query)."<br>";
                    $result = mysql_db_query(DBName,$Query,$this->conn_id);
                    if ($result)
                    {

                        if ($this->Table[BindTable])
                        {
                            $BindError = 0;
                            while (list($BindTable,$Data) = each($this->Table[BindTable]))
                            {
                                if ($Data[Options][BindMethod] == "CheckBoks")
                                {
                                    $Query = "DELETE FROM $BindTable WHERE $Data[ChildField] = '".$this->Params['ID']."'";

                                    $result = mysql_db_query(DBName,$Query,$this->conn_id);
                                    if (!$result)
                                    {
                                        $BindError++;
                                    }
                                    //$BindArray = $_POST["Bind_$BindTable"];
                                    $BindArray = $_POST["Bind_$BindTable"];

                                    if (is_array($BindArray)) while (list($key,$val)=each($BindArray))
                                    {
                                        $Query = "INSERT INTO $BindTable ($Data[ChildField],$Data[BindField]) VALUES ('".$this->Params['ID']."','$key')";
                                        $result = mysql_db_query(DBName,$Query,$this->conn_id);
                                        if (!$result)
                                        {
                                            $BindError++;
                                        }
                                    }
                                }
                            }
                        }

                        if (! $BindError)
                        {
                            if (!$this->Options[ForbidMessage]) $this->Output(ShowCMSMessage(local('ADMIN_INFO_UPDATE_COMPLITE'), 1)."<br><br>");
                        } else {
                            $this->Output(ShowCMSMessage(local('ADMIN_ERROR_UPDATE_PROCESS'), 2)."<br><br>");
                        }

                        if ($this->Table[Events][AfterUpdate][Query])
                        {
                            while (list($key,$Query) = each ($this->Table[Events][AfterUpdate][Query]))
                            {
                                $Query = str_replace("#ID#",$this->Params['ID'],$Query);
                                if ($this->Options[QueryStrReplace]) {
                                    while (list($str,$Field)=each($this->Options[QueryStrReplace]))
                                    {
                                        $Query = str_replace($str,$_POST["Field".$Field],$Query);
                                    }
                                }
                                //echo $Query;
                                $result = mysql_db_query(DBName,$Query,$this->conn_id);
                                if (! $result) $this->Error .= $this->getMysqlError($Query);
                            }
                        }

                    }  else {
                        $this->Error .= $this->getMysqlError($Query);
                    }
                    if ($this->Params[action]=="apply")
                    {
                        $this->Init($this->Params['ID']);
                        $result = $this->EditTable("update");
                    } else {
                        $result = $this->ListTable();
                    }
                } else {
                    $this->Output("<font color=red>". $this->UpdateError ."</font><br>");
                    $this->Init($this->Params['ID'],"_POST");
                    $result = $this->EditTable("update");
                }
                //if ($this->Error) $this->Output($this->Error);
                $this->Output($result);
            }
            break;
            case "add":
            if ($this->Options[ForbidInsert])
            {
                $ErrMsg = ($this->Options[ForbidInsert][ErrMsg]) ? $this->Options[ForbidInsert][ErrMsg] : local('ADMIN_NOT_RIGHTS_ADD_DATA');
                $this->Output("<font color=red><span class=error>".$ErrMsg."</span></font><br>");
            } else {
                $DoubleData = false;
                if ($this->Options[DoubleDataProtection] && $_POST[$this->Table[Name]."PostFormID"])
                {
                    $Query = "SELECT count(*) FROM ".$this->Table[Name]." WHERE ".$this->Options[DoubleDataProtection][PostFormIdField]."='".$_POST[$this->Table[Name]."PostFormID"]."'";
                    $result = mysql_db_query(DBName,$Query,$this->conn_id);
                    if ($result)
                    {
                        $row = mysql_fetch_row($result);
                        if ($row[0] != 0)
                        {
                            $DoubleData = true;
                        }
                    }
                }
                if ($this->Options[DoubleDataProtection_simple])
                {
                    $Where = array();
                    $Fields = array_keys($this->Fields);
                    reset($Fields);
                    while (list($key,$Field) = each($Fields))
                    if ($this->Fields[$Field][Edit] && !$this->Fields[$Field][ForbidInsert])
                    if (!in_array($this->Fields[$Field][Type][Name],array("Blob","UploadFile","Text")))
                    switch (strval($this->Fields[$Field][Type][Name]))
                    {
                        case "Date":
                        $stamp = mktime(0, 0, 0, $_POST["Field".$Field."_month"], $_POST["Field".$Field."_day"],  $_POST["Field".$Field."_year"]);
                        $date_str = ($stamp == mktime(0, 0, 0, 0, 0, 0)) ? "0000-00-00" : date('Y', $stamp) ."-". date('m', $stamp) ."-".  date('d', $stamp);
                        array_push($Where,"DATE_FORMAT(".$this->Fields[$Field][Edit][Field].",'%Y-%m-%d')='".$date_str."'");
                        break;
                        case "Datetime":
                        $stamp = mktime($_POST["Field".$Field."_hour"], $_POST["Field".$Field."_minute"], 0, $_POST["Field".$Field."_month"], $_POST["Field".$Field."_day"],  $_POST["Field".$Field."_year"]);
                        $date_str = ($stamp == mktime(0, 0, 0, 0, 0, 0)) ? "0000-00-00 00:00:00" : date('Y', $stamp) ."-". date('m', $stamp) ."-".  date('d', $stamp) . " " . date('H', $stamp) . ":" . date('i', $stamp) . ":" . date('s', $stamp);
                        array_push($Where,"DATE_FORMAT(".$this->Fields[$Field][Edit][Field].",'%Y-%m-%d %H:%i%s')='".$date_str."'");
                        break;
                        case "Checkbox":
                        $CheckValue = ($_POST["Field$Field"]) ? $this->Fields[$Field][Values][Checked] : $this->Fields[$Field][Values][UnChecked];
                        array_push($Where,$this->Fields[$Field][Edit][Field]."='".$CheckValue."'");
                        break;
                        default:
                        array_push($Where,$this->Fields[$Field][Edit][Field]."='".($_POST["Field$Field"])."'");
                    }
                    if (sizeof($Where) > 0)
                    {
                        $Query = "SELECT count(*) FROM ".$this->Table[Name]." WHERE ".join($Where,"AND ");
                        //$this->Output($Query)."<br>";
                        $result = mysql_db_query(DBName,$Query,$this->conn_id);
                        if ($result) {
                            $row = mysql_fetch_row($result);
                            if ($row[0] != 0)
                            {
                                $DoubleData = true;
                                if (! $this->Options[ForbidMessage]) $this->Output("<font color=red>".local('ADMIN_RECORD_ALREADY')."</font><br><br>");
                            }
                        } else $this->Error .= $this->getMysqlError($Query);
                    }
                }
                if (!$DoubleData) {
                    $ListFields = array();
                    $ListValues = array();
                    if ($this->Options[DoubleDataProtection])
                    {
                        array_push($ListFields,$this->Options[DoubleDataProtection][PostFormIdField]);
                        array_push($ListValues,"'".$_POST[$this->Table[Name]."PostFormID"]."'");
                    }
                    reset($this->Fields);
                    while (list($Field,$Data) = each($this->Fields))
                    {
                        if ($Data[Edit][Caption]) $Data[Caption] = $Data[Edit][Caption];
                        if ($Data[Type][Mandatory] && ($_POST["Field$Field"]=="")) $this->InsertError .= "<li>".local('ADMIN_FIELD_NOT_FILLED')." <b>\"".$Data[Caption]."\"</b><br>";
                        if ($Data[Edit] && !$Data[ForbidInsert]) {
                            if ($Data[Unique] && ($_POST["Field$Field"]))
                            {
                                $Query = "SELECT count(*) FROM ".$this->Table[Name]." WHERE ".$Field."='".$_POST["Field$Field"]."'";
                                //echo $Query."<br>";
                                $result = mysql_db_query(DBName,$Query,$this->conn_id);
                                if ($result)
                                {
                                    $row = mysql_fetch_row($result);
                                    if ($row[0] != 0)
                                    {
                                        $this->InsertError .= ShowCMSMessage(local('ADMIN_VALUE_NOT_UNIQUE', $Data['Caption']), 2)."<br>";
                                    }
                                } else $this->Error .= $this->getMysqlError($Query);
                            }
                            switch ($Data[Type][Name])
                            {
                                case "Float":
                                $string_value = str_replace(",",".",$_POST["Field$Field"]);
                                $double_value = (double) $string_value;
                                if (( ((string) $double_value) != $string_value) && ($string_value != "") )
                                {
                                    $this->InsertError .= "<li>".local('ADMIN_NOT_CORRECT_FORMAT_DATA')." <b>\"".$Data[Caption]."\"</b><br>";
                                }
                                array_push($ListFields,$Field);
                                array_push($ListValues,"'$double_value'");
                                break;
                                case "Blob":
                                case "UploadFile":
                                if (UploadFile($Field,$Data,$this->UpdateError,$UploadData,$UploadMimeType))
                                {
                                    array_push($ListFields,$Field);
                                    array_push($ListValues,"'".$UploadData."'");
                                    if ($Data[SaveMimeType]) {
                                        array_push($ListFields,$Data[SaveMimeType]);
                                        array_push($ListValues,"'".$UploadMimeType."'");
                                    }
                                    if ($Data[SaveImageSize])
                                    {
                                        eval("global \$Field".$Field.";");
                                        eval("\$filename=\$Field".$Field.";");
                                        if ($ImageSize = GetImageSize($filename))
                                        {
                                            array_push($ListFields,$Data[SaveImageSize][Width]);
                                            array_push($ListValues,"'".$ImageSize[0]."'");
                                            array_push($ListFields,$Data[SaveImageSize][Height]);
                                            array_push($ListValues,"'".$ImageSize[1]."'");
                                        }
                                    }
                                }
                                break;
                                case "Date":
                                $stamp = mktime(0, 0, 0, $_POST["Field".$Field."_month"], $_POST["Field".$Field."_day"],  $_POST["Field".$Field."_year"]);
                                $date_str = ($stamp == mktime(0, 0, 0, 0, 0, 0)) ? "0000-00-00" : date('Y', $stamp) ."-". date('m', $stamp) ."-".  date('d', $stamp);
                                array_push($ListFields,$Field);
                                array_push($ListValues,"'".$date_str."'");
                                break;
                                case "Datetime":
                                $stamp = mktime($_POST["Field".$Field."_hour"], $_POST["Field".$Field."_minute"], 0, $_POST["Field".$Field."_month"], $_POST["Field".$Field."_day"],  $_POST["Field".$Field."_year"]);
                                $date_str = ($stamp == mktime(0, 0, 0, 0, 0, 0)) ? "0000-00-00 00:00:00" : date('Y', $stamp) ."-". date('m', $stamp) ."-".  date('d', $stamp) . " " . date('H', $stamp) .":". date('i', $stamp) .":". date('s', $stamp);
                                array_push($ListFields,$Field);
                                array_push($ListValues,"'".$date_str."'");
                                break;
                                case "Checkbox":
                                $value = ($_POST["Field$Field"]) ? $Data[Values][Checked] : $Data[Values][UnChecked];
                                array_push($ListFields,$Field);
                                array_push($ListValues,"'$value'");
                                break;
                                case "Password":
                                if (!empty($_POST["Field$Field"]))
                                {
                                    $value = md5($_POST["Field$Field"]);
                                    array_push($ListFields,$Field);
                                    array_push($ListValues,"'".$value."'");
                                }
                                break;
                                default:
                                if ($Data[Type][Name]=="HTML")
                                {
                                    $value = $_POST["Field".$Field];
                                    $value = preg_replace("/src=(\\\\\")?(http:\/\/".$SERVER_NAME."(:[0-9]+)?)\/(.+?.(gif|jpg))(\\\\\"|\s)/i","src=\"\\4\"",$value);
                                } else {
                                    $value = $_POST["Field$Field"];
                                }
                                $value = str_replace("#textarea#","textarea",$value);
                                array_push($ListFields,$Field);
                                array_push($ListValues,"'$value'");
                                if ($Data[Type][Name]=="Email") if (!Is_Email($value)) $this->InsertError .= "<li>".local('ADMIN_EMAIL_NOT_CORRECT')." <b>\"".$Data[Caption]."\"</b><br>";
                            }
                        }
                        if ($Data[Hidden] && !$Data[ForbidInsert])
                        {
                            $DefaultValue = $Data[DefaultValue];
                            if (!in_array($DefaultValue,array("now()","null"))) $DefaultValue = "'".$DefaultValue."'";
                            array_push($ListFields,$Field);
                            array_push($ListValues,$DefaultValue);
                        }
                    }
                    if (!$this->InsertError)
                    {
                        $ListFields[] = 'BodyFlat';
                        $ListValues[] = "''";
            
                        $Query = "INSERT INTO  " . $this->Table[Name] ."(" . join(",",$ListFields) . ") VALUES(" . join(",",$ListValues) . ")";
                        //INSERT
                        //echo $Query;
                        $result = mysql_db_query(DBName,$Query,$this->conn_id);

                        if ($result)
                        {
                            $this->NEW_ID_VALUE =  mysql_insert_id($this->conn_id);

                            if (! $this->Options[ForbidMessage])
                            {
                                $this->Output(ShowCMSMessage(local('ADMIN_RECORD_PROCESSED', mysql_affected_rows($this->conn_id)), 1));
                            }
                            if ($this->Table[BindTable]) {
                                $BindError = 0;
                                while (list($BindTable,$Data) = each($this->Table[BindTable]))
                                {
                                    if ($Data[Options][BindMethod] == "CheckBoks" && !$Data[ForbidInsert])
                                    {
                                        $BindArray = $_POST["Bind_$BindTable"];
                                        if (is_array($BindArray)) while (list($key,$val)=each($BindArray))
                                        {
                                            $Query = "INSERT INTO $BindTable ($Data[ChildField],$Data[BindField]) VALUES (".$this->NEW_ID_VALUE.",$key)";
                                            $result = mysql_db_query(DBName,$Query,$this->conn_id);
                                            //echo mysql_error($this->conn_id)."<br>";
                                            if (! $result) $BindError++;
                                        }
                                    }
                                }
                                if ($BindError)
                                {
                                    $this->Output("<font color=red>".local('ADMIN_ERROR_UPDATE_PROCESS')."</font><br><br>");
                                }
                            }

                            //                          reset($this->SearchFields);
                            //                          while (list($Field,$PartValue)=each($this->SearchFields)) $this->SearchFields[$Field] = "";
                            if ($this->Table[Events][AfterInsert][Query])
                            {
                                while (list($key,$Query) = each ($this->Table[Events][AfterInsert][Query]))
                                {
                                    $Query = str_replace("#NEW_ID#",$this->NEW_ID_VALUE,$Query);
                                    if ($this->Options[QueryStrReplace]) {
                                        while (list($str,$Field)=each($this->Options[QueryStrReplace]))
                                        {
                                            $Query = str_replace($str,$_POST["Field".$Field],$Query);
                                        }
                                    }
                                    //echo $Query;
                                    $result = mysql_db_query(DBName,$Query,$this->conn_id);
                                    if (!$result) $this->Error .= $this->getMysqlError($Query);
                                }
                            }
                        }  else {
                            $this->Error .= $this->getMysqlError($Query);
                            print_r($Query);
                            print_r($this->Error);
                        }
                        if ($_POST[EditBindAfterInsert])
                        {
                            $this->Init($this->NEW_ID_VALUE);
                            $result = $this->EditTable("edit");
                        } else {
                            $result = $this->ListTable();
                        }
                    } else {
                        $this->Output("<font color=red><span class=error>". $this->InsertError ."</span></font><br>");
                        $this->Init(NULL,"_POST");
                        $result = $this->EditTable("add");
                    }
                } else {
                    $result = $this->ListTable();
                }
                //if ($this->Error) $this->Output($this->Error);
                $this->Output($result);
            }
            break;
            case "delete":
            if ($this->Options[QueryStrReplace])
            {
                while (list($str,$Field)=each($this->Options[QueryStrReplace]))
                {
                    $Query = "SELECT ". $Field ." FROM ". $this->Table[Name] ." WHERE ". $this->Table[PrimaryKey] ."='". $this->Params['ID']."'";
                    $result = mysql_db_query(DBName,$Query,$this->conn_id);
                    //echo $Query;
                    if ($result)
                    {
                        $row = mysql_fetch_row($result);
                        $this->Options[QueryStrReplace][$str] = addslashes($row[0]);
                    }
                }
            }
            $Query = "DELETE FROM " . $this->Table[Name] ." WHERE " . $this->Table[PrimaryKey] . "='" . $this->Params[ID]."'";
            $result = mysql_db_query(DBName,$Query,$this->conn_id);
            if ($result)
            {
                $this->Output(ShowCMSMessage(local('ADMIN_RECORD_REMOVED', mysql_affected_rows($this->conn_id)), 1));
            } else {
                $this->Error .= $this->getMysqlError($Query);
            }
            if ($this->Table[Events][AfterDelete][Query]) {
                while (list($key,$Query) = each ($this->Table[Events][AfterDelete][Query]))
                {
                    $Query = str_replace("#ID#",$this->Params[ID],$Query);
                    if ($this->Options[QueryStrReplace])
                    {
                        reset($this->Options[QueryStrReplace]);
                        while (list($str,$FieldValue)=each($this->Options[QueryStrReplace]))
                        {
                            $Query = str_replace($str,$FieldValue,$Query);
                        }
                    }
                    $result = mysql_db_query(DBName,$Query,$this->conn_id);
                    if (! $result) $this->Error .= $this->getMysqlError($Query);
                }
            }
            $result = $this->ListTable();
            //if ($this->Error) $this->Output($this->Error);
            $this->Output($result);
            break;
            case "addbind":
            case "remotebind":
            $BindTable = $_GET[bindtable];
            $ChildField = $this->Table[BindTable][$BindTable][ChildField];
            $BindField = $this->Table[BindTable][$BindTable][BindField];
            if ($this->Params[action] == "addbind")
            {
                $Query = "INSERT INTO $BindTable ($ChildField,$BindField) VALUES(".$Params[ID].",".$_GET[$BindTable."_bind_id"].")";
            } else {
                $Query = "DELETE FROM $BindTable WHERE $ChildField=$Params[ID] AND $BindField=".$_GET[$BindTable."_bind_id"];
            }
            $result = mysql_db_query(DBName,$Query,$this->conn_id);
            if (! $result) $this->Error .= $this->getMysqlError($Query);
            $this->Init($Params[ID]);
            //if ($this->Error) $this->Output($this->Error);
            $result = $this->EditTable("update");
            $this->Output($result);
            break;
            default:
            $result = $this->ListTable();
            //if ($this->Error) $this->Output($this->Error);
            $this->Output($result);
        }
        return $this->OUTPUT_STR;
    }

    function getURL($action="",$ID="",$page="")
    {
        global $PHP_SELF;

        $ret = $PHP_SELF . "?action=$action";

        if ($ID!="") $ret .= "&ID=$ID";
        if (sizeof($this->SearchFields) > 0 )
        {
            reset($this->SearchFields);
            while (list($Field,$PartValue)=each($this->SearchFields))  $ret .= "&Part$Field=".urlencode($PartValue);
        }
        if (sizeof($this->Options[HiddenParams]))
        {
            reset($this->Options[HiddenParams]);
            while (list($key,$val)=each($this->Options[HiddenParams])) if ($key!="page" && !$this->SearchFields[$key]) $ret .= "&".$key."=".$val;
        }
        $ret .= ($page!="") ? "&page=$page" : "&page=".$this->Page;
        if (($action == "delete") && $this->Table[Actions][delete][Confirmation]) {
            $ret = "javascript: if (confirm('".addslashes(str_replace("\"","'",strip_tags($this->Table[Actions][delete][Confirmation][Message])))."')) document.location.href='".$ret."'";
        }
        return $ret;
    }

    function getUrlPage($page)
    {
        return $this->getURL(null,null,$page);
    }

    function Init($ID,$INIT_FROM="DATABASE")
    {
        global $SERVER_NAME,$_POST;

        $ListFields = array();
        reset($this->Fields);
        $this->Fields[PrimaryKey][Value] = $ID;

        switch ($INIT_FROM) {
            case "_POST":
            while (list($Field,$Data) = each($this->Fields))
            {
                if ($Data[Edit]) {
                    switch (strval($Data[Type][Name]))
                    {
                        case "UploadFile":
                        case "Blob":
                        $this->Fields[$Field][Value] = stripslashes($_POST["HiddenField$Field"]);
                        break;
                        case "Date":
                        $stamp = mktime(0, 0, 0, $_POST["Field".$Field."_month"], $_POST["Field".$Field."_day"],  $_POST["Field".$Field."_year"]);
                        $date_str = ($stamp == mktime(0, 0, 0, 0, 0, 0)) ? "0000-00-00" : date('Y', $stamp) ."-". date('m', $stamp) ."-".  date('d', $stamp);
                        $this->Fields[$Field][Value] = $date_str;
                        break;
                        case "Datetime":
                        $stamp = mktime($_POST["Field".$Field."_hour"], $_POST["Field".$Field."_minute"], 0, $_POST["Field".$Field."_month"], $_POST["Field".$Field."_day"],  $_POST["Field".$Field."_year"]);
                        $date_str = ($stamp == mktime(0, 0, 0, 0, 0, 0)) ? "0000-00-00 00:00:00" : date('Y', $stamp) ."-". date('m', $stamp) ."-".  date('d', $stamp) . " " . date('H', $stamp) . ":" . date('i', $stamp) . ":" . date('s', $stamp);
                        $this->Fields[$Field][Value] = $date_str;
                        break;
                        case "Checkbox":
                        $this->Fields[$Field][Value] = ($_POST["Field$Field"]) ? $this->Fields[$Field][Values][Checked] : $this->Fields[$Field][Values][UnChecked];
                        break;
                        default:
                        if ($Data[Type][Name]=="HTML")
                        {
                            $Value = stripslashes($_POST["Field$Field"]);
                        } else {
                            $Value = stripslashes($_POST["Field$Field"]);
                        }
                        //$Value = preg_replace("/(\&)([A-Za-z0-9_]*);/","&amp;\\2;",$Value);
                        /*Временная заплатка. Замена &[any] на &amp;*/
                        $this->Fields[$Field][Value] = $Value;
                    }
                }
            }
            break;
            default:
            while (list($Field,$Data) = each($this->Fields))
            {
                //if ($Data[Edit] && !$Data[ForbidUpdate]) {
                if ($Data[Edit] && !$Data[ForbidInit])
                {
                    //if ($Data[Edit]) {
                    $ListField = ($Data[Edit][Field]) ? $Data[Edit][Field]." as ".$Field : $Field;
                    array_push($ListFields,$ListField);
                } elseif ($Data[Edit] && $Data[DefaultValue]) $this->Fields[$Field][Value] = $Data[DefaultValue];
            }
            $Query = "SELECT " . $this->Table[PrimaryKey] .", " . join(",",$ListFields) . " FROM " . $this->Table[Name] ." WHERE " . $this->Table[PrimaryKey] . "='".$ID."'";
            //echo $Query;
            //  $this->Output("query : $Query <br>");
            $result = mysql_db_query(DBName,$Query,$this->conn_id);
            if ($result)
            {
                if ($res = mysql_fetch_array($result))
                {
                    reset($this->Fields);
                    while (list($Field,$Data) = each($this->Fields))
                    {
                        $Value = $res[$Field];
                        $Value = str_replace("textarea","#textarea#",$Value);
                        //$Value = preg_replace("/(\&)([A-Za-z0-9_]*);/","&amp;\\2;",$Value);
                        /*Временная заплатка. Замена &[any] на &amp;*/
                        if ($Data[Type][Name]=="HTML") $Value = preg_replace("/src=\"(.+?\.(gif|jpg))\"/i","src=\"http://".$SERVER_NAME."/\\1\"",$Value);
                        //if ($Data[Edit] && !$Data[ForbidUpdate]) $this->Fields[$Field][Value] = $Value;
                        //if ($Data[Edit]) $this->Fields[$Field][Value] = $Value;
                        if ($Data[Edit] && !$Data[ForbidInit]) $this->Fields[$Field][Value] = $Value;
                    }
                } else $this->Error .= "<font color=red>".local('ADMIN_NOT_FOUND_RECORD_WITH_ID')." \"$ID\"</font><br><br>";
            } else {
                $this->Error .= "<table><tr><td colspan=2><font color=red><b>Error:</b></font></td></tr>
                                           <tr><td valign=top><b>Query:</b></td><td>$Query</td></tr>
                                           <tr><td valign=top><b>Mysql:</b></td><td>".mysql_error($this->conn_id)."</td></tr></table>";
            }
        }
        $this->InitBind($INIT_FROM);
    }

    function InitBind($INIT_FROM="DATABASE")
    {
        global $_POST;

        if ($this->Table[BindTable])
        {
            reset($this->Table[BindTable]);
            while (list($BindTable,$Data) = each($this->Table[BindTable]))
            {
                $Query = "SELECT $Data[BindParentColumn], $Data[BindLookupColumn], $BindTable.$Data[ChildField]
                                FROM $Data[BindLookupTable]
                                LEFT JOIN $BindTable ON $Data[BindParentColumn] = $BindTable.$Data[BindField] AND $BindTable.$Data[ChildField]='".$this->Fields[PrimaryKey][Value]."'
                                ".$Data[Options][BindLookupTableWhere]."
                                ".$Data[Options][BindLookupColumnOrder];
                $result = mysql_db_query(DBName,$Query,$this->conn_id);
                //echo $Query;
                if ($result)
                {
                    while ($row = mysql_fetch_row($result))
                    {
                        if (($this->Table[BindTable][$BindTable][Options][BindMethod] == "CheckBoks") && ($INIT_FROM == "_POST")) {
                            if ($_POST["Bind_$BindTable"][$row[0]])
                            {
                                $isChecked = true;
                            } else {
                                $isChecked = false;
                            }
                        } else {
                            $isChecked = ($row[2]) ? true : false;
                        }
                        $this->Bind[$BindTable][$row[0]][Caption] = $row[1];
                        $this->Bind[$BindTable][$row[0]][isChecked] = $isChecked;
                        //$this->Output("\$this->Bind[".$BindTable."][".$row[0]."][Caption] = ".$row[1].";<br>");
                    }
                }  else {
                    $this->Error .= $this->getMysqlError($Query);
                    $ret .= $this->Error;
                }
            }
        }
    }

    function EditTable($act)
    {
        global $SERVER_NAME,$_POST,$DOCUMENT_ROOT;

        if ($this->Options[DoubleDataProtection] && !$this->Fields[PrimaryKey][Value])
        $DoubleDataProtectionFormID = ($_POST[$this->Table[Name]."PostFormID"]) ? $_POST[$this->Table[Name]."PostFormID"] : md5(time());

        if (!$this->Options[ForbidListTable]) $ret .= "<a href=\"".$this->getURL()."\"><img src=\"/Admin/img/button-back-to-list.gif\" border=\"0\"></a><br>&nbsp;";
        $Enctype = ($this->FormEnctype) ? "ENCTYPE=\"$this->FormEnctype\"" : "";
        //$imos
        $ret .= "<form name=\"".$this->Table[Name]."EditForm\" $Enctype action=\"".$this->getURL($act,$this->Fields[PrimaryKey][Value])."\" method=post onsubmit=\"\">\n";

        if ($this->Options[DoubleDataProtection] && !$this->Fields[PrimaryKey][Value])
        $ret .= "<input type=hidden name=\"".$this->Table[Name]."PostFormID\" value=\"".$DoubleDataProtectionFormID."\">\n";

        $table_border = (isset($this->Table[Style][Edit][Table][Border])) ? $this->Table[Style][Edit][Table][Border] : 0;
        $ret .= "<table cellpadding=10 width=\"100%\" cellspacing=0 border=\"".$table_border."\" class=\"".$this->Table[Style][Edit][Table]['Class']."\">\n";
        if (sizeof($this->Options[HiddenParams]))
        {
            reset($this->Options[HiddenParams]);
            while (list($key,$val)=each($this->Options[HiddenParams])) $ret .= "<input type=hidden name=\"$key\" value=\"$val\">\n";
        }
        reset($this->Fields);
        while (list($Field,$Data) = each($this->Fields))
        {
            if ($Data[Edit]) {
                if ($Data['Edit']['Caption']) $Data[Caption] = $Data[Edit][Caption];
                $ret .= "<tr>\n";
                if ($Data[Type][Name]!="HTML") {
                    $ret .= "   <td class=td  align=right valign=top class=\"".$this->Table[Style][Edit][Table][Td]['Class']."\">";
                    if ($Data[Type][Mandatory]) $ret .= "<font color=red>*</font>&nbsp;";
                    $ret .=     $Data[Caption]."&nbsp;&nbsp;</td>\n";
                    $ret .= "   <td class=td  class=\"".$this->Table[Style][Edit][Table][Td]['Class']."\">";
                }
                if ($Data[Lookup]) {
                    $ret .= "<select name=\"Field".$Field."\" class=\"".$Data[Type]['Class']."\">\n";
                    if ($Data[Lookup][AllowEmpty]) $ret .= "<option></option>\n";
                    if ($this->Lookup[$Field]) {
                        reset($this->Lookup[$Field]);
                        while (list($key,$val)=each($this->Lookup[$Field]))
                        {
                            $selected = ($key == $Data[Value]) ? " selected" : "";
                            $ret .= "<option value=\"$key\" $selected>$val</option>\n";
                        }
                    }
                    $ret .= "</select>\n";
                } else {
                    switch ($Data[Type][Name])
                    {
                        case "Float":
                        case "Varchar":
                        case "Email":
                        case "Varchar":
                        if (in_array($this->Params[action],array("edit","update")) && $Data[ForbidUpdate])
                        {
                            $ret .= str_replace("\"","'",$Data[Value]);
                            $ret .= "<input type=hidden name=\"Field".$Field."\" value=\"".str_replace("\"","'",$Data[Value])."\" class=\"".$Data[Type]['Class']."\">";
                        } else {
                            $ret .= "<input type=text name=\"Field".$Field."\" size=\"".$Data[Type][Size]."\" value=\"".str_replace("\"","'",$Data[Value])."\" class=\"".$Data[Type]['Class']."\">";
                        }
                        break;
                        case "Password":
                        $ret .= "<input type=password name=\"Field".$Field."\" size=\"".$Data[Type][Size]."\" class=\"".$Data[Type]['Class']."\">";
                        break;
                        case "Text":
                        $ret .= "<textarea name=\"Field".$Field."\" cols=\"".$Data[Type][Cols]."\" rows=\"".$Data[Type][Rows]."\" class=\"".$Data[Type]['Class']."\">".$Data[Value]."</textarea>";
                        break;
                        case "HTML":

                        $ret .= "<td colspan=2 class=maintxt>".$Data[Caption]."\n";
                        /* Блок работы с типом редактируемого поля HTML*/
                        require_once($_SERVER['DOCUMENT_ROOT']."/fck_editor/fckeditor.php");
                        $sBasePath = "/fck_editor/";
                        $oFCKeditor = new FCKeditor("Field".$Field) ;
                        $oFCKeditor->BasePath   = $sBasePath ;
                        $oFCKeditor->Value      = $Data['Value'];
                        $oFCKeditor->Height = 400;
                        $ret .= $oFCKeditor->CreateHtml() ;

                        break;
                        case "Blob":
                        case "UploadFile":
                        $ret .= $Data[Value];
                        if (!$this->Options[ForbidUpdate])
                        {
                            if ($this->Fields[PrimaryKey][Value]!="")
                            {
                                $ret .= "<br><input type=radio name=\"Replace".$Field."\" class=\"check".$Data[Type]['Class']."\" checked >&nbsp;".local('ADMIN_NOT_CHANGE')." &nbsp;&nbsp;";
                                $ret .= "&nbsp;&nbsp;<input type=radio name=\"Replace".$Field."\" value=\"Change\" class=\"check".$Data[Type]['Class']."\">&nbsp;".local('DS_ACTION_EDIT')." &nbsp;&nbsp;";
                                $ret .= "&nbsp;&nbsp;<input type=radio name=\"Replace".$Field."\" value=\"Delete\" class=\"check".$Data[Type]['Class']."\">&nbsp;".local('ADMIN_DELETE')." &nbsp;&nbsp;<br>";
                            }
                            $ret .= "<input type=file name=\"Field".$Field."\" class=\"".$Data[Type]['Class']."\">&nbsp;";
                            $ret .= "<input type=button value=\"".local('ADMIN_LOOK')."\" class=submit onclick=\"javascript: if (document.".$this->Table[Name]."EditForm.Field".$Field.".value) {open(document.".$this->Table[Name]."EditForm.Field".$Field.".value, 'lefile','scrollbars=no,resizable=no,menubar=no,status=no');} else {alert('".local('ADMIN_NEED_SELECT_FILE')."');}\" class=\"".$Data[Type]['Class']."\">";
                            $ret .= "<input type=hidden name=\"HiddenField".$Field."\" value=\"".$Data[Value]."\">&nbsp;";
                        }
                        break;
                        case "Date":
                        if (!$Data[Value] && $Data[DefaultValue]) $Data[Value] = $Data[DefaultValue];
                        if ($Data[Value])
                        {
                            $tm_stamp = mktime(0, 0, 0, substr($Data[Value], 5, 2), substr($Data[Value], 8, 2),  substr($Data[Value], 0, 4));
                        } else {
                            $tm_stamp = "now";
                        }
                        $ret .= "<table><tr>";
                        $ret .= $this->getDateTimeForm("d", $tm_stamp, "Field".$Field, $Data[Type][AllowEmpty], $Data[Type][YearFrom], $Data[Type][YearTo], $Data[Type]['Class']);
                        $ret .= "</tr></table>";
                        break;
                        case "Datetime":
                        if (!$Data[Value] && $Data[DefaultValue]) $Data[Value] = $Data[DefaultValue];
                        if ($Data[Value])
                        {
                            $tm_stamp = mktime(substr($Data[Value], 11, 2), substr($Data[Value], 14, 2), 0, substr($Data[Value], 5, 2), substr($Data[Value], 8, 2),  substr($Data[Value], 0, 4));
                        } else {
                            $tm_stamp = "now";
                        }
                        $ret .= "<table><tr>";
                        $ret .= $this->getDateTimeForm("dt", $tm_stamp, "Field".$Field, $Data[Type][AllowEmpty], $Data[Type][YearFrom], $Data[Type][YearTo], $Data[Type]['Class']);
                        $ret .= "</tr></table>";
                        break;
                        case "Checkbox":
                        $checked = ($Data[Value] == $Data[Values][Checked]) ? "checked" : "";
                        $ret .= "<input type=checkbox class=check name=\"Field".$Field."\" class=\"".$Data[Type]['Class']."\" $checked value=\"".$Data[Values][Checked]."\">";
                        break;

                        default: $ret .= "<font color=red>".local('ADMIN_UNKNOWN_TYPE_DATA').": <b>".$Data[Type][Name]."</b></font>";
                    }
                }
                if ($Data[Edit][Description]) $ret .= "<br>".$Data[Edit][Description];
                $ret .= "   </td>\n";
                $ret .= "</tr>\n";
            }
        }

        // Bind begin
        reset($this->Bind);
        if (sizeof($this->Bind) > 0) while (list($BindTable,$Data) = each($this->Bind))
        {
            $ret .= "<tr><td class=td align=right valign=top class=". $this->Table[Style][Show][Table][Td]['Class'].">".$this->Table[BindTable][$BindTable][Options][Caption]."&nbsp;&nbsp;</td><td class=td>\n";
            if ($this->Table[BindTable][$BindTable][Options][BindMethod] == "CheckBoks")
            {
                while (list($BindLookup_id,$BindLookup_data) = each($Data)) {
                    $checked = ($BindLookup_data[isChecked]) ? "checked" : "";
                    $ret .= "<input type=checkbox class=check name=\"Bind_$BindTable"."[".$BindLookup_id."]\" value=\"Y\" $checked>&nbsp; ".$BindLookup_data[Caption]."<br>\n";
                }
            }
            if ($this->Table[BindTable][$BindTable][Options][BindMethod] == "Select")
            {
                if ($this->Fields[PrimaryKey][Value]!="") {
                    $ret .= "<table width=100% border=0>";
                    $count = 0;
                    while (list($BindLookup_id,$BindLookup_data) = each($Data))
                    {
                        if ($BindLookup_data[isChecked]) {
                            $count++;
                            $ret .= "<tr><td>".$BindLookup_data[Caption]."</td><td>
                                        <a class=sitemapa href=\"".$this->getURL(remotebind,$this->Fields[PrimaryKey][Value])."&bindtable=$BindTable&".$BindTable."_bind_id=$BindLookup_id#".$BindTable."\">".local('ADMIN_DELETE')."</a>
                                    </td></tr>\n";
                        }
                    }
                    if (!$count) $ret .= "<tr><td colspan=2>".local('ADMIN_BINDINGS_ABSENT')."</td></tr>";
                    $ret .= "   <tr><td colspan=2><hr><a </td></tr>";
                    $ret .= "   <tr><td><a name=\"$BindTable\"><select name=\"".$BindTable."_bind_id\">";
                    reset($Data);
                    while (list($BindLookup_id,$BindLookup_data) = each($Data))
                    {
                        if (! $BindLookup_data[isChecked])
                        $ret .= "<option value=\"$BindLookup_id\">$BindLookup_data[Caption]</option>\n";
                    }
                    $ret .= "   </select></td>
                                <td>
                                    <a class=sitemapa href=\"javascript: document.location.href='".$this->getURL(addbind,$this->Fields[PrimaryKey][Value])."&bindtable=$BindTable&".$BindTable."_bind_id='+document.".$this->Table[Name]."EditForm.".$BindTable."_bind_id.value+'#".$BindTable."';\">".local('ADMIN_ADD')."</a>
                                </td></tr>";
                    $ret .= "</table>";
                } else {
                    $checked = ($_POST[EditBindAfterInsert]) ? " checked" : "";
                    $ret .= "<input type=checkbox class=check name=EditBindAfterInsert value=Y".$checked."> &nbsp; ".local('ADMIN_EDIT_AFTER_ADDITION  ')."<br>";
                }
            }
            if ($this->Table[BindTable][$BindTable][Options][Description]) $ret .= "<br>&nbsp;".$this->Table[BindTable][$BindTable][Options][Description];
            $ret .= "&nbsp;</td></tr>\n";
        }
        // Bind end
        $buttons_align = ($this->Table[Style][Edit][Buttons][Align]) ? $this->Table[Style][Edit][Buttons][Align] : "right";
        if (!$this->Options[ForbidUpdate])
        {
            if ($this->imos == '1'){
                if ($act=="update" && !$this->Options[ForbidApplyButton])
                {
                    $ret .= "<tr><td>&nbsp;</td><td align=\"".$buttons_align."\"><br><input type=submit class=Submit value=\"".local('ADMIN_APPLY_CLOSE')."\" class=\"".$this->Table[Style][Edit][Buttons]['Class']."\">&nbsp;&nbsp;&nbsp;<input type=reset value=\"".local('ADMIN_CLEAR')."\" class=\"submit".$this->Table[Style][Edit][Buttons]['Class']."\">&nbsp;&nbsp;&nbsp;<input type=button value=\"".local('ADMIN_APPLY')."\" class=\"submit".$this->Table[Style][Edit][Buttons]['Class']."\" onclick=\"javascript: document.".$this->Table[Name]."EditForm.action='".$this->getURL("apply",$this->Fields[PrimaryKey][Value])."';onsubmit();submit();\"></td></tr>\n";
                } else {
                    $ret .= "<tr><td>&nbsp;</td><td align=\"".$buttons_align."\"><br><input type=submit class=Submit value=\"".local('ADMIN_APPLY')."\" class=\"".$this->Table[Style][Edit][Buttons]['Class']."\">&nbsp;&nbsp;&nbsp;<input type=reset value=\"".local('ADMIN_CLEAR')."\" class=\"submit".$this->Table[Style][Edit][Buttons]['Class']."\"></td></tr>\n";
                }
            } else {
                if ($act=="update" && !$this->Options[ForbidApplyButton]) {
                    $ret .= "<tr><td>&nbsp;</td><td align=\"".$buttons_align."\"><br><input type=submit class=Submit value=\"".local('ADMIN_APPLY_CLOSE')."\" class=\"".$this->Table[Style][Edit][Buttons]['Class']."\">&nbsp;&nbsp;&nbsp;<input type=reset value=\"".local('ADMIN_CLEAR')."\" class=\"submit".$this->Table[Style][Edit][Buttons]['Class']."\">&nbsp;&nbsp;&nbsp;<input type=button value=\"".local('ADMIN_APPLY')."\" class=\"submit".$this->Table[Style][Edit][Buttons]['Class']."\" onclick=\"javascript: document.".$this->Table[Name]."EditForm.action='".$this->getURL("apply",$this->Fields[PrimaryKey][Value])."';onsubmit();submit();\"></td></tr>\n";
                } else {
                    $ret .= "<tr><td>&nbsp;</td><td align=\"".$buttons_align."\"><br><input type=submit class=Submit value=\"".local('ADMIN_APPLY')."\" class=\"".$this->Table[Style][Edit][Buttons]['Class']."\">&nbsp;&nbsp;&nbsp;<input type=reset value=\"".local('ADMIN_CLEAR')."\" class=\"submit".$this->Table[Style][Edit][Buttons]['Class']."\"></td></tr>\n";
                }
            }
        }

        $ret .= "</form>\n";
        $ret .= "</table>\n";
        if ($this->Table['Events']['AfterEdit']['SetFocus']) $ret .= "<script language=Javascript>document.".$this->Table[Name]."EditForm.Field".$this->Table['Events']['AfterEdit']['SetFocus'].".focus();</script>";
        return $ret;
    }

    function ListTable()
    {
        if ($this->Options[ForbidListTable])
        {
            $ErrMsg = ($this->Options[ForbidListTable][ErrMsg]) ? $this->Options[ForbidListTable][ErrMsg] : "Не достаточно прав для просмотра данных";
            $this->Output("<font color=red><span class=error>".$ErrMsg."</span></font><br>");
            return;
        }

        if (is_object($this->tpl))
        {
            $template_mode = true;
        } else {
            $template_mode = false;
        }

        $ret .= "<p><table border=0 bgcolor=444E6C cellpadding=8 cellspacing=1><tr><td class=maintxt bgcolor=#444E6C>\n";
        $ListFields = array();
        array_push($ListFields,$this->Table[PrimaryKey]);

        if (sizeof($this->SearchFields) > 0 )
        {
            $ret .= "<form name=FormSearch action=\"".$this->getURL(null,null,1)."\" method=post>\n";
            $ret .= "<input type=hidden name=Search value=yes>\n";
            $this->assign[SEARCH][SEARCH_FORM_URL] = $this->getURL(null,null,1);

            reset($this->SearchFields);
            while (list($Field,$PartValue)=each($this->SearchFields))
            {
                $ret .= '</td><td class=maintxt bgcolor=#444E6C>&nbsp;&nbsp;&nbsp;';
                if ($this->Fields[$Field][Search][Caption])
                {
                    $caption = $this->Fields[$Field][Search][Caption];
                } else {
                    $caption = $this->Fields[$Field][Caption];
                }

                $ret .= $caption."&nbsp;";
                $ret .= '</td><td class=maintxt bgcolor=#444E6C>';
                if ($this->Fields[$Field][Lookup])
                {
                    $output .= "<select name=\"Part".$Field."\">"; //\n
                    if ($this->Lookup[$Field]) {
                        if (!$this->Fields[$Field][Search][NotEmpty])
                        {
                            $output .= "<option></option>"; //\n
                        } else {
                            if (!isset($this->Fields[$Field][Search][DefaultValue]) || (strval($this->Fields[$Field][Search][DefaultValue]) == ""))
                            {
                                reset($this->Lookup[$Field]);
                                $this->Fields[$Field][Search][DefaultValue] = key($this->Lookup[$Field]);
                            }
                        }
                        if (!$this->SearchFields[$Field] && isset($this->Fields[$Field][Search][DefaultValue]))
                        {
                            $this->SearchFields[$Field] = $this->Fields[$Field][Search][DefaultValue];
                            $PartValue = $this->SearchFields[$Field];
                        }
                        reset($this->Lookup[$Field]);
                        while (list($key,$val)=each($this->Lookup[$Field]))
                        {
                            $selected = ($key == $PartValue) ? " selected" : "";
                            $output .= "<option value=\"".$key."\" ".$selected.">".$val."</option>"; //\n
                        }
                    }
                    $output .= "</select>"; //\n
                } else {

                    $output .= "<input type=text size=".($caption = $this->Fields[$Field][Search][Size] ? $this->Fields[$Field][Search][Size] : "10")." name=\"Part".$Field."\" value=\"".$PartValue."\">\n";
                }
                $ret .= $output;
                $this->assign[SEARCH][row][] = array('SEARCH_ELEMENT_TITLE'=>$caption, 'SEARCH_ELEMENT'=>$output);
                unset($output);
            }

            $ret .= "</td><td bgcolor=#444E6C> <input type=submit class=Submit value=\"".local('ADMIN_SEARCH_IT')."\">\n";
            $ret .= "</td><td bgcolor=#444E6C></td><td bgcolor=#444E6C>";
            $this->assign[SEARCH][SEARCH_SUBMIT_TEXT] = local('ADMIN_SEARCH_IT');

            if ($template_mode == true)
            {
                $this->tpl->define( array( 'units' => 'show/search.tpl.html' ) );
                $this->tpl->defineDynamic('row', 'units');
                foreach($this->assign[SEARCH][row] as $key => $assign )
                {
                    $this->tpl->assign( $assign );
                    $this->tpl->parse('UNIT', '.row');
                }
                $this->tpl->assign( $this->assign[SEARCH] );
                $this->tpl->parse('MAIN', 'units');
                $this->assign[OUTPUT][SEARCH_CONTENT] = $this->tpl->fetch('MAIN');
            }
        }

        if (!$this->Options[ForbidInsert])
        {
            //Собираем данные по кнопке Add для темплейта
            if ($template_mode == true)
            {
                $this->tpl->define( array( 'add' => 'show/add.tpl.html' ) );
                $this->assign[ADD][ADD_URL] = $this->getURL("new");
                $this->tpl->assign( $this->assign[ADD] );
                $this->tpl->parse('MAIN', 'add');
                $this->assign[OUTPUT][ADD_CONTENT] = $this->tpl->fetch('MAIN');
            }

            $ret .= "<a class=sitemapa href=\"".$this->getURL("new")."\">".(($this->Options[Buttons][Add][Caption]) ? $this->Options[Buttons][Add][Caption] : '<img src="/Admin/img/button-add.gif" border="0"></a></td>')."";
        }

        $ret .= '</form>';

        $table_border = (isset($this->Table[Style][Show][Table][Border])) ? $this->Table[Style][Show][Table][Border] : 0;
        $table_cellpadding = (isset($this->Table[Style][Show][Table][Cellpadding])) ? $this->Table[Style][Show][Table][Cellpadding] : 5;
        $table_cellspacing = (isset($this->Table[Style][Show][Table][Cellspacing])) ? $this->Table[Style][Show][Table][Cellspacing] : 1;

        $output = "<SCRIPT language=JavaScript> <!--
                    var preloadFlag = false;
                    function newImage(arg) {if (document.images) {  rslt = new Image(); rslt.src = arg; return rslt; } }
                    function changeImages() {
                        if (document.images && (preloadFlag == true)) {
                            for (var i=0; i<changeImages.arguments.length; i+=2) {
                                document[changeImages.arguments[i]].src = changeImages.arguments[i+1];
                            }
                        }
                    }
                //--></SCRIPT>";


        if ($template_mode == true)
        {
            $this->assign[OUTPUT][PREFETCH_CONTENT] = $output;
            unset($output);
        }

        $ret .= $output;
        $ret .="</tr></table><p>";

        $ret .= "<table width=100% border=0 cellpadding=0 cellspacing=0 bgcolor=#040D23><tr><td><table width=\"100%\" border=".$table_border." cellpadding=".$table_cellpadding." cellspacing=".$table_cellspacing.">\n";
        $header_bgcolor = (isset($this->Table['Style']['Show']['Table']['Header']['Bgcolor'])) ? $this->Table[Style][Show][Table]['Header']['Bgcolor'] : "#636E88";
        $ret .="<tr>\n";
        reset($this->Fields);
        $Colspan = 0;
        while (list($Field,$Data) = each($this->Fields))
        {
            if ($Data['Show'])
            {
                $ListField = ($Data['Show']['Field']) ? $Data['Show']['Field']." as ".$Field : $Field;
                array_push($ListFields,$ListField);
                $ret .= "<td nowrap class=th_2 class=\"th".$this->Table['Style']['Show']['Table']['Header']['Class']."\" width=\"".$Data[Show][Width]."\" bgcolor=#444E6C>".$Data[Caption]."</td>\n";
                $Colspan++;


            if ($template_mode == true)
            {
                $this->assign[ROW_TITLE][] = array('ROW_TITLE'=>$Data[Caption], 'ROW_CLASS'=>'a', 'COL_SPAN'=>1 );
            }


            }
        }

        if (is_array($this->Table['Actions']))
        {
            $output = '';
            reset($this->Table['Actions']); $CountActions = 0;
            while (list($key,$ActData)=each($this->Table['Actions'])) if ($ActData)
            {
                $CountActions++;
                if ($ActData['IMG'])
                {
                    $ret .= $output .= "<SCRIPT language=JavaScript>newImage(\"".$ActData['IMG']['Active']."\"); newImage(\"".$ActData['IMG']['Down']."\"); </SCRIPT>\n";
                }

            }
            if ($CountActions)
            {
                $ret .= "<td nowrap class=th_2 class=\"th".$this->Table['Style']['Show']['Table']['Header']['Class']."\" colspan=\"".$CountActions."\" width=\"20\" align=center bgcolor=#444E6C>".local('ADMIN_ACTIONS')."</td>";
                $ret .= $output = "<SCRIPT language=JavaScript> preloadFlag = true; </SCRIPT>";
            }
            $Colspan += $CountActions;

            if ($template_mode == true)
            {
                $this->assign[ROW_TITLE][] = array('ROW_TITLE'=>local('ADMIN_ACTIONS'), 'TITLE_CLASS'=>$this->Table['Style']['Show']['Table']['Header']['Class'], 'COL_SPAN'=>sizeof($this->Table['Actions']));
                $this->assign[OUTPUT][PREFETCH_CONTENT] .= $output;
                unset($output);
            }

        }

        $ret .="</tr>\n";


        // select
        $q[SELECT] = "SELECT " . join(",",$ListFields);
        if ($this->Options[ExtensionQuery]) $q[SELECT] .= ", ".$this->Options[ExtensionQuery];
        // from, where
        $q[FROM] .= " FROM " . ((isset($this->Table[SelectFrom])) ? $this->Table[SelectFrom] : $this->Table[Name]);
        if ($this->Options[Where]) $q[FROM] .= " WHERE " .$this->Options[Where];
        if (sizeof($this->SearchFields) > 0 )
        {
            $Prefix .= ($this->Options[Where]) ? " AND " : " WHERE ";
            reset($this->SearchFields);
            while (list($Field,$PartValue)=each($this->SearchFields))
            {
                $q[FROM] .= $Prefix;
                $q[FROM] .= str_replace("#Value#",$PartValue,$this->Fields[$Field][Search][Template]);
                $Prefix = " AND ";
            }
        }
        // group, having
        if ($this->Options[Group])
        {
            $q[GROUP] = " GROUP BY " .$this->Options[Group];
        }

        // order by
        if ($this->Options[Order])
        {
            $q[ORDER] = " ORDER BY " .$this->Options[Order];
        }
        // limit
        $q[LIMIT] = " LIMIT ". strval((($this->Page-1)*$this->RecordOnPage)).", ".$this->RecordOnPage;


        // Подсчитываем кол-во страниц
        if ($this->Options[Group])
        {
            $Query = $q[SELECT].$q[FROM].$q[GROUP];
            //echo $Query;
            $result = mysql_db_query(DBName,$Query,$this->conn_id);
            if ($result)
            {
                $this->CountRecord = mysql_num_rows($result);
            } else $this->MySQLerror .= $this->getMysqlError($Query);
        } else {
            $Query = "SELECT count(*) ".$q[FROM];
            $result = mysql_db_query(DBName,$Query,$this->conn_id);
            //$this->Output($Query."<br>");
            if ($result)
            {
                $row = mysql_fetch_row($result);
                $this->CountRecord = $row[0];
            } else $this->MySQLerror .= $this->getMysqlError($Query);
        }

        if ($this->CountRecord)
        {
            $this->CountPage = floor(($this->CountRecord-1) / $this->RecordOnPage) + 1;
            $this->StartPage = floor(($this->Page-1) / $this->PagesOnNavigationLine) * $this->PagesOnNavigationLine + 1;
            $this->EndPage = (($this->StartPage + $this->PagesOnNavigationLine - 1) < $this->CountPage) ? ($this->StartPage + $this->PagesOnNavigationLine - 1) : $this->CountPage;
        }

        $Query = $q[SELECT].$q[FROM].$q[GROUP].$q[ORDER].$q[LIMIT];



        /* Вот тут выдается селект которые показывает все на экран*/
        //SELECT
        //$this->Output("query : $Query <br>");
        $result = mysql_db_query(DBName,$Query,$this->conn_id);
        if ($result)
        {
            unset($assign);
            if (mysql_num_rows($result) > 0)
            {
                while ($res = mysql_fetch_array($result))
                {
                    //print_r ($res);
                    $ret .= "<tr>";
                    reset($this->Fields);
                    while (list($Field,$Data) = each($this->Fields))
                    {
                        if ($Data[Show])
                        {
                            $FieldValue = ($Data[Lookup]) ? $this->Lookup[$Field][$res[$Field]] : $res[$Field];
                            $ret .= "<td bgcolor=#444E6C class=td class=\"".$this->Table[Style][Show][Table][Td]['Class']."\" nowrap>".getLocalize($FieldValue)."&nbsp</td>\n";

                            if ($template_mode == true)
                            {
                                $assign[] = array('COL_ELEMENT'=>getLocalize($FieldValue), 'COL_CLASS'=>$this->Table[Style][Show][Table][Td]['Class'], 'COL_SPAN'=>1);
                            }
                        }
                    }

                    if ($this->Table[Actions])
                    {

                        reset($this->Table[Actions]);
                        $output = '';
                        while (list($act,$Data) = each ($this->Table[Actions]))
                        {

                            if ($this->Table[Actions][delete][Confirmation][Template])
                            {
                                $Template = $this->Table[Actions][delete][Confirmation][Template];
                                $T = strstr($Template,"[#");
                                $F = substr($T,2,strpos($T,"#]")-2);
                                $this->Table[Actions][delete][Confirmation][Message] = str_replace("[#".$F."#]",$res[$F],$Template);
                                //$this->Output($this->Table[Actions][delete][Confirmation][Message] ."<br>");
                            }

                            //$URL = ($Data[URL]) ? str_replace("#ID#",$res[$this->Table[PrimaryKey]],$Data[URL]) : $this->getURL($act,$res[$this->Table[PrimaryKey]]);
                            $URL = ($Data[URL]) ? str_replace("#ID#",$res[0],$Data[URL]) : $this->getURL($act,$res[0]);
                            if ($Data)
                            {

                                $ret .= "<td bgcolor=#444E6C class=td  class=\"".$this->Table[Style][Show][Table][Td]['Class']."\" width=\"".$Data[Width]."\" align=center>";
                                $output = '';
                                if ($this->imos == '1')
                                {
                                    if ($Data[IMG])
                                    {
                                        $output .= "<a class=sitemapa href=\"".$URL."\" ONMOUSEOVER=\"changeImages('img_".$act."_".$res[0]."', '".$Data[IMG][Active]."'); return true;\" ONMOUSEOUT=\"changeImages('img_".$act."_".$res[0]."', '".$Data[IMG][Inactive]."'); return true;\" ONMOUSEDOWN=\"changeImages('img_".$act."_".$res[0]."', '".$Data[IMG][Down]."'); return true;\" ONMOUSEUP=\"changeImages('img_".$act."_".$res[0]."', '".$Data[IMG][Inactive]."'); return true;\" target=\"_blank\"> <IMG NAME=\"img_".$act."_".$res[0]."\" SRC=\"".$Data[IMG][Inactive]."\" WIDTH=\"".$Data[IMG][Width]."\" HEIGHT=\"".$Data[IMG][Height]."\" BORDER=0 ALT=\"".$Data[Caption]."\"> </a>";
                                    } else {
                                        $output .= "<a class=sitemapa href=\"".$URL."\" target=\"_blank\">".$Data[Caption]."</a>";
                                    }
                                } else {
                                    if ($Data[IMG])
                                    {
                                        $output .= "<a class=sitemapa href=\"".$URL."\" ONMOUSEOVER=\"changeImages('img_".$act."_".$res[0]."', '".$Data[IMG][Active]."'); return true;\" ONMOUSEOUT=\"changeImages('img_".$act."_".$res[0]."', '".$Data[IMG][Inactive]."'); return true;\" ONMOUSEDOWN=\"changeImages('img_".$act."_".$res[0]."', '".$Data[IMG][Down]."'); return true;\" ONMOUSEUP=\"changeImages('img_".$act."_".$res[0]."', '".$Data[IMG][Inactive]."'); return true;\"> <IMG NAME=\"img_".$act."_".$res[0]."\" SRC=\"".$Data[IMG][Inactive]."\" WIDTH=\"".$Data[IMG][Width]."\" HEIGHT=\"".$Data[IMG][Height]."\" BORDER=0 ALT=\"".$Data[Caption]."\"> </a>";
                                    } else {
                                        $output .= "<a class=sitemapa href=\"".$URL."\">".$Data[Caption]."</a>";
                                    }
                                }
                                $ret .= $output;
                                $ret .= "</td>";
                            }
                        }
                        if ($template_mode == true)
                        {
                            $assign[] = array('COL_ELEMENT'=>$output, 'COL_CLASS'=>$this->Table[Style][Show][Table][Td]['Class'], 'COL_SPAN'=>(int)sizeof($this->Table[Actions]));
                        }


                    }
                    $ret .= "</tr>";

                    $this->assign[ROW_ELEMENT][] = $assign;
                    unset($assign);
                }


                if ($template_mode == true)
                {

                    $this->tpl->define( array( 'unitsS' => 'show/row_element.tpl.html' ) );
                    $this->tpl->defineDynamic('title', 'unitsS');
                    foreach($this->assign[ROW_TITLE] as $key => $assign )
                    {
                        if (key_exists('COL_SPAN', $assign) && $assign['COL_SPAN'] > 1)
                        {
                            $assign['COL_SPAN'] = 'colspan="'.$assign['COL_SPAN'].'"';
                        } else {
                            $assign['COL_SPAN'] = '';
                        }
                        $this->tpl->assign( $assign );
                        $this->tpl->parse('UNITZ', '.title');
                    }
                    unset($assign);





                    $this->tpl->defineDynamic('row_element', 'unitsS');
                    foreach($this->assign[ROW_ELEMENT] as $key => $assign )
                    {

                        $this->tpl->define( array( 'unitsZ' => 'show/col_element.tpl.html' ) );
                        $this->tpl->defineDynamic('col_element', 'unitsZ');
                        foreach($assign as $assign_col )
                        {

                            if (key_exists('COL_SPAN', $assign_col) && $assign_col['COL_SPAN'] > 1)
                            {
                                $assign_col['COL_SPAN'] = 'colspan="'.$assign_col['COL_SPAN'].'"';
                            } else {
                                $assign_col['COL_SPAN'] = '';
                            }

                            $this->tpl->assign( $assign_col );
                            $this->tpl->parse('MAINA', '.col_element');
                        }
                        $assignZ[COL_ELEMENTS] = $this->tpl->fetch('MAINA');
                        $this->tpl->clear_parse('MAINA');


                        if (key_exists('COL_SPAN', $assignZ) && $assignZ['COL_SPAN'] > 1)
                        {
                            $assignZ['COL_SPAN'] = 'colspan="'.$assignZ['COL_SPAN'].'"';
                        } else {
                            $assignZ['COL_SPAN'] = '';
                        }
                        $this->tpl->assign( $assignZ );
                        $this->tpl->parse('MAINS', '.row_element');
                    }
                    $this->tpl->parse('MAINS', 'unitsS');
                    $this->assign[OUTPUT][SHOW_CONTENT] = $this->tpl->fetch('MAINS');
                }



            } else $ret .= "<tr><td bgcolor=#444E6C colspan=".$Colspan." class=\"".$this->Table[Style][Show][Table][Td]['Class']."\" align=center><br><b>".local('ADMIN_NO_ENTRIES_FOR_SEARCH')."</b><br><br></td></tr>";
        } else {
            $this->Error .= $this->getMysqlError($Query);
        }
        if ($this->CountPage > 1)
        {
            $ret .= "<tr><td bgcolor=#444E6C colspan=".$Colspan." bgcolor=\"".$header_bgcolor."\" align=center>".$this->getPageNavigationLine()."</td></tr>";
            $this->assign[OUTPUT][PAGES_CONTENT] = $this->getPageNavigationLine();
        } else {
            $this->assign[OUTPUT][PAGES_CONTENT] = '&nbsp;';
        }


        $ret .="</table></td></tr></table>\n";

        if ($template_mode == true)
        {

            $this->tpl->define( array( 'main' => 'show/main.tpl.html' ) );
            $this->tpl->assign( $this->assign[OUTPUT] );
            $this->tpl->parse('MAIN1', 'main');
            $ret = $this->tpl->fetch('MAIN1');

            return $ret;
            //echo "<xmp>";
            //print_r($ret);
            //echo "</xmp>";
        } else {
            return $ret;
        }


    }



    function getMysqlError($Query)
    {
        if (!$this->Options[ForbidMessage])
        {
            $ret = "<table><tr><td colspan=2><font color=red><b>Error:</b></font></td></tr>
                           <tr><td valign=top><b>Query:</b></td><td>".$Query."</td></tr>
                           <tr><td valign=top><b>Mysql:</b></td><td>".mysql_error($this->conn_id)."</td></tr></table>";
        } else {
            $ret = "<font color=red>".local('ADMIN_ERROR_ACCESS_DB')."</font><br>";
        }
        return $ret;
    }

    function getDateTimeForm($type, $stamp, $prefix, $allow_empty=false, $year_from=null, $year_to=null, $class=null) {
        $month = array("Jan"=>local('ADMIN_MONTH_JAN'), "Feb"=>local('ADMIN_MONTH_FEB'),"Mar"=>local('ADMIN_MONTH_MAR'),"Apr"=>local('ADMIN_MONTH_APR'),"May"=>local('ADMIN_MONTH_MAY'),"Jun"=>local('ADMIN_MONTH_JUN'),"Jul"=>local('ADMIN_MONTH_JUL'),"Aug"=>local('ADMIN_MONTH_AUG'),"Sep"=>local('ADMIN_MONTH_SEP'),"Oct"=>local('ADMIN_MONTH_OCT'),"Nov"=>local('ADMIN_MONTH_NOV'),"Dec"=>local('ADMIN_MONTH_DEC'));
        if ($stamp == "now") $stamp = mktime(date("H"), date("i"), 0, date("m"), date("d"),  date("Y"));
        if ($stamp != mktime(0, 0, 0, 0, 0, 0)) {
            $yy = date('Y', $stamp);
            $mm = date('m', $stamp);
            $dd = date('d', $stamp);
            $HH = date('H', $stamp);
            $MM = date('i', $stamp);
        }
        if ($this->Options["ListTDClass"]) $td_cl = "class=\"".$this->Options[ListTDClass]."\"";
        $ret = "<td $td_cl><select name=\"".$prefix."_day\" class=\"".$class."\">\n";
        if ($allow_empty) $ret .= "<option value=0></options>";
        for ($c_d = 1; $c_d <= 31; $c_d++) {
            $sel = ($c_d == $dd) ? "selected" : "";
            $ret .= "<option value=\"$c_d\" $sel>$c_d</option>\n";
        }
        $ret .= "</select>-</td>\n";
        $ret .= "<td $td_cl><select name=\"".$prefix."_month\" class=\"".$class."\">\n";
        if ($allow_empty) $ret .= "<option value=0></options>";
        for ($c_m = 1; $c_m <= 12; $c_m++) {
            $sel = ($c_m == $mm) ? "selected" : "";
            $c_md = date('M', mktime(0, 0, 0, $c_m, 1, date('Y')));
            $ret .= "<option value=\"$c_m\" $sel>".$month[$c_md]."</option>\n";
        }
        $ret .= "</select>-</td>\n";
        $ret .= "<td $td_cl><select name=\"".$prefix."_year\" class=\"".$class."\">\n";
        if ($allow_empty) $ret .= "<option value=0></options>";
        for ($c_y = (($year_from)? $year_from : date('Y')-5); $c_y <= (($year_to) ? $year_to : date('Y') + 20); $c_y++) {
            $sel = ($c_y == $yy) ? "selected" : "";
            $ret .= "<option value=\"$c_y\" $sel>$c_y</option>\n";
        }
        $ret .= "</select>&nbsp;&nbsp;&nbsp;</td>\n";
        if ($type == "dt" ) {
            $ret .= "<td $td_cl><select name=\"".$prefix."_hour\" class=\"".$class."\">\n";
            if ($allow_empty) $ret .= "<option value=0></options>";
            for ($c_h = 0; $c_h <= 23; $c_h++) {
                $sel = ($c_h == $HH) ? "selected" : "";
                $ret .= "<option value=\"$c_h\" $sel>$c_h</option>\n";
            }
            $ret .= "</select>:</td>\n";
            $ret .= "<td $td_cl><select name=\"".$prefix."_minute\" class=\"".$class."\">\n";
            if ($allow_empty) $ret .= "<option value=0></options>";
            for ($c_M = 0; $c_M <= 59; $c_M++) {
                $sel = ($c_M == $MM) ? "selected" : "";
                $c_Md = ($c_M < 10) ? "0$c_M" : $c_M;
                $ret .= "<option value=\"$c_M\" $sel>$c_Md</option>\n";
            }
            $ret .= "</select></td>\n";
        }
        return $ret;
    }


    function getPageNavigationLine() {
        if ($this->CountPage) {
            $ret  = "<table width=100% cellspacing=0 cellpadding=0 border=0>
                        <tr>
                          <td align=center class=maintxt>";
            if ($this->StartPage > 1) $ret .= "<a href=\"".$this->getUrlPage($this->StartPage-1)."\">&laquo;&laquo;</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            for ($i=$this->StartPage;$i<=$this->EndPage;$i++) {
                if ($i != $this->StartPage) $ret .= ",&nbsp;&nbsp;&nbsp;";
                if ($i == $this->Page) {
                    $ret .= "<span class=select_txt>[<b>$i</b>]</span>";
                } else {
                    $ret .= "<a href=\"".$this->getUrlPage($i)."\" class=maintxt>$i</a>";
                }
            }
            if ($this->EndPage < $this->CountPage) $ret .= "&nbsp;&nbsp;&nbsp;<a href=\"".$this->getUrlPage($this->EndPage+1)."\" >&raquo;&raquo;</a>";
            $ret .= "
                      </td>
                    </tr>
                  </table>";
        }
        return $ret;
    }

    function Output($str) {
        if ($this->Options['ForbidDirectOutput']) {
            $this->OUTPUT_STR .= $str;
        } else {
            echo $str;
        }
    }
}


?>