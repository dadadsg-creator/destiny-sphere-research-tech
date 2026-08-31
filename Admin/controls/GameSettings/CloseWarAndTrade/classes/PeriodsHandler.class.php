<?php

require_once constant('INCLUDE_PATH') . 'Smarty/Smarty.class.php';
//require_once 'Smarty.class.php';

require_once 'classes/Period.class.php';
require_once 'classes/World.class.php';


class PeriodsHandler extends Smarty
{
    private $action;
    private $worlds = array();
    private $error;
    private $worldName;
    private $worldId;
    private $dbId;

    public function handleRequest()
    {
        if (array_key_exists('action', $_GET)) {
            $this->action = $_GET['action'];
        } elseif (array_key_exists('action', $_POST)) {
            $this->action = $_POST['action'];
        } else {
            $this->action = 'view';
        }

        switch ($this->action) {
            case 'delete':
                if (array_key_exists('dbid', $_GET) && is_numeric($_GET['dbid'])) {
                    $dbId = intval($_GET['dbid']);
                } else {
                    $dbId = false;
                }

                if (array_key_exists('worldid', $_GET) && is_numeric($_GET['worldid'])) {
                    $worldId = intval($_GET['worldid']);
                } else {
                    $worldId = false;
                }

                if (array_key_exists('periodid', $_GET) && is_numeric($_GET['periodid'])) {
                    $periodId = intval($_GET['periodid']);
                } else {
                    $periodId = false;
                }

                if (array_key_exists('type', $_GET) && !empty($_GET['type'])) {
                    $periodType = $_GET['type'];
                } else {
                    $periodType = false;
                }

                $world = new World($dbId, $worldId);

                $start = $world->getStartByPeriodID($periodId);
                $end = $world->getEndByPeriodID($periodId);

                $world->deletePeriod($periodId, $periodType);

                $worldName = $world->getWorldName();

                //логируем действие
                actionLog( "Delete $periodType period in world '$worldName'", "Start date $start<br>End date $end");


                break;
            case 'add':
                if (array_key_exists('worldid', $_GET) && is_numeric($_GET['worldid'])) {
                    $this->worldId = intval($_GET['worldid']);
                } else {
                    $this->worldId = false;
                }

                if (array_key_exists('dbid', $_GET) && is_numeric($_GET['dbid'])) {
                    $this->dbId = intval($_GET['dbid']);
                } else {
                    $this->dbId = false;
                }

                if (false !== $this->worldId && false !== $this->dbId) {
                    $world = new World($this->dbId, $this->worldId);
                    $this->worldName = $world->getWorldName();
                } else {
                    $this->worldName = false;
                }

                break;
            case 'save':
                $log = "";
                if (array_key_exists('world_id', $_POST)/* && !empty($_POST['world_id'])*/) {
                    $worldId = intval($_POST['world_id']);
                } else {
                    $worldId = false;
                }

                if (array_key_exists('db_id', $_POST)/* && !empty($_POST['db_id'])*/) {
                    $dbId = intval($_POST['db_id']);
                } else {
                    $dbId = false;
                }

                if (array_key_exists('type', $_POST) && !empty($_POST['type'])) {
                    $periodType = $_POST['type'];

                    if (!is_array($periodType) || count($periodType) < 1) {
                        $periodType = false;
                    }
                } else {
                    $periodType = false;
                }

                // construct date-time:
                if (array_key_exists('start_date', $_POST)) {
                    $startDate = $_POST['start_date'];//

                    if (false === checkDateFormat($startDate)) {
                        $this->error = 'Wrong date format (start date: ' . $startDate . ')';
                        $startDate = false;
                    }
                } else {
                    $this->error = 'No start date given';
                    //$startDate = date('Y-m-d H:i:s');
                }

                if (array_key_exists('end_date', $_POST)) {
                    $endDate = $_POST['end_date'];//

                    if (false === checkDateFormat($endDate)) {
                        $this->error = 'Wrong date format (end date: ' . $endDate . ')';
                        $endDate = false;
                    }
                } else {
                    $this->error = 'No start date given';
                    //$endDate = date('Y-m-d H:i:s');
                }

                if (false !== $worldId && false !== $dbId && false !== $periodType &&
                false !== $startDate && false !== $endDate) {

                    if (0 === $worldId) {
                        // add to all worlds
                        foreach ($GLOBALS['dbWorldAccess'] as $dbId => $serverData) {

                            // ѕытаемс€ приконектитьс€
                            $conn_string = "host=".$serverData['host']." dbname=".$serverData['dbname']." user=".$serverData['user']." password=".$serverData['password']."";
                            $conn = @pg_connect($conn_string);

                            if(!is_resource($conn))
                            {
                                echo "<font color='red' size='5'>WARNING: Can't connect to DB ".$serverData['server']."!</f1></font><br>";
                                continue;
                            }

                            // ѕолучаем списов всех миров
                            $worlds = World::getAllWorldsInDb($dbId);

                            foreach ($worlds as $w) {
                                foreach ($periodType as $pt) {
                                    $w->addPeriod($pt, $startDate, $endDate);
                                }
                            }
                        }
                    } else {
                        // add to this world
                        $world = new World($dbId, $worldId);
                        $worldName = $world->getWorldName();

                        foreach ($periodType as $pt) {
                            $world->addPeriod($pt, $startDate, $endDate);
                            //логируем действие
                            $log .= "Add $pt period in world '$worldName'<br> with start date = '$startDate'<br>and end date='$endDate'<br>";
                        }
                    }
                } else {
                    if (!isset($this->error)) {
                        $this->error = 'Wrong parameters gives';
                    }
                }

                actionLog($log);

                break;
            case 'update':
                if (array_key_exists('period_id', $_POST) && is_numeric($_POST['period_id'])) {
                    $periodId = intval($_POST['period_id']);
                } else {
                    $periodId = false;
                }

                if (array_key_exists('type', $_POST) && !empty($_POST['type'])) {
                    $periodType = $_POST['type'];
                } else {
                    $periodType = false;
                }

                if (array_key_exists('db_id', $_POST) && is_numeric($_POST['db_id'])) {
                    $dbId = intval($_POST['db_id']);
                } else {
                    $dbId = false;
                }

                if (array_key_exists('world_id', $_POST) && is_numeric($_POST['world_id'])) {
                    $worldId = intval($_POST['world_id']);
                } else {
                    $worldId = false;
                }

                if (array_key_exists('start_date', $_POST) && !empty($_POST['start_date'])) {
                    $startDate = $_POST['start_date'];

                    if (false === checkDateFormat($startDate)) {
                        $this->error = 'Wrong date format (start date: ' . $startDate . ')';
                    }
                } else {
                    $this->error = 'No start date given';
                    $startDate = date('Y-m-d H:i:s');
                }

                if (array_key_exists('end_date', $_POST) && !empty($_POST['end_date'])) {
                    $endDate = $_POST['end_date'];

                    if (false === checkDateFormat($endDate)) {
                        $this->error = 'Wrong date format (end date: ' . $endDate . ')';
                    }
                } else {
                    $this->error = 'No end date given';
                    $endDate = date('Y-m-d H:i:s');
                }

                if (!isset($this->error) || empty($this->error)) {
                    $world = new World($dbId, $worldId);
                    $worldName = $world->getWorldName();
                    $res = $world->updatePeriod($periodId, $periodType, $startDate, $endDate);

                    //логируем действие
                    actionLog( "Update $periodType period in world '$worldName'", "Set start date='$startDate',<br>end date='$endDate'");
        

                    if (false === $res) {
                        $this->error = 'Sorry, was unable to update';
                    }
                }

            default:
                // the same as view
                $this->action = 'view';

                // for Smarty
                $aWorlds = array();

                foreach ($GLOBALS['dbWorldAccess'] as $dbId => $serverData) {

                    // ѕытаемс€ приконектитьс€
                    $conn_string = "host=".$serverData['host']." dbname=".$serverData['dbname']." user=".$serverData['user']." password=".$serverData['password']."";
                    $conn = @pg_connect($conn_string);

                    if(!is_resource($conn))
                    {
                        echo "<font color='red' size='5'>WARNING: Can't connect to DB ".$serverData['server']."!</f1></font><br>";
                        continue;
                    }

                    // ѕолучаем списов всех миров
                    $worlds = World::getAllWorldsInDb($dbId);

                    foreach ($worlds as $w) {
                        $world = array();
                        $world['worldname'] = $w->getWorldName();
                        $world['id'] = $w->getId();
                        $world['db_id'] = $w->getDbId();
                        $peacePeriods = $w->getPeacePeriods();

                        $world['peaceperiods'] = array();

                        foreach ($peacePeriods as $p) {
                            $world['peaceperiods'][$p->getId()] = array('id' => $p->getId(), 'start_date' => $p->getStartDate(), 'end_date' => $p->getEndDate());
                        }

                        $world['peaceperiods_count'] = count($peacePeriods);

                        $notradePeriods = $w->getNotradePeriods();

                        $world['notradeperiods'] = array();

                        foreach ($notradePeriods as $p) {
                            $world['notradeperiods'][$p->getId()] = array('id' => $p->getId(), 'start_date' => $p->getStartDate(), 'end_date' => $p->getEndDate());
                        }

                        $world['notradeperiods_count'] = count($notradePeriods);

                        $aWorlds[] = $world;
                    }
                }

                $this->worlds = $aWorlds;
        }
    }

    public function display($template = NULL, $cache_id = NULL, $compile_id = NULL, $parent = NULL)
    {
        if ($this->action == 'view')
        {
            echo '
              <h1>Worlds</h1>
              <div><a href="?action=add&worldid=0" class="add">Add a new period to all worlds</a></div>';

            if (isset($this->error))
            {
                echo '
                <br /><div class="mess_error">'.$this->error.'</div>';
            }

            foreach($this->worlds as $key => $world)
            {
                echo '
                    <br />
                    <h1>'.$world['worldname'].' #'.$world['id'].'</h1>
                    <div><a href="?action=add&worldid='.$world['id'].'&dbid='.$world['db_id'].'" class="add">Add a new period to this world</a></div>';

                if ($world['peaceperiods_count'] > 0)
                {
                    echo '
                    <h3 style="font-family: Arial, sans; font-size: 1.1em">Peace periods</h3>';

                    foreach ($world['peaceperiods'] as $pk => $p)
                    {
                        echo '
                        <div style="font-family: Arial,sans">
                        <form action="?" method="POST">
                        <input type="hidden" name="period_id" value="'.$p['id'].'" />
                        <input type="hidden" name="action" value="update" />
                        <input type="hidden" name="type" value="peace" />
                        <input type="hidden" name="world_id" value="'.$world['id'].'" />
                        <input type="hidden" name="db_id" value="'.$world['db_id'].'">
                        <div><div style="font-family: Helvetica,Arial,sans; float: left; font-size: 0.7em; width: 30px; padding: 1px 10px">id: '.$p['id'].'</div>
                        <font color=#F8F8FF>Starts:&nbsp;</font>
                        <input type="text" name="start_date" size="21" value="'.$p['start_date'].'" />
                        <font color=#F8F8FF>&nbsp;&nbsp;Ends:&nbsp;</font>
                        <input type="text" size="21" name="end_date" value="'.$p['end_date'].'" />&nbsp;
                        <input type="submit" class="submit" value="Update" />&nbsp;
                        <a href="?action=delete&dbid='.$world['db_id'].'&worldid='.$world['id'].'&periodid='.$p['id'].'&type=peace" class="alert" {literal}onclick="if(!confirm(\'Really delete?\')) { return false; }"{/literal}>delete</a></div>
                        </form>
                        </div>';
                    }
                }


                if ($world['notradeperiods_count'] > 0)
                {
                    echo '
                    <h3 style="font-family: Arial, sans; font-size: 1.1em">No-trade periods</h3>';

                    foreach ($world['notradeperiods'] as $pk => $p)
                    {
                        echo '
                        <div style="font-family: Arial,sans">
                        <form action="?" method="POST">
                        <input type="hidden" name="period_id" value="'.$p['id'].'" />
                        <input type="hidden" name="action" value="update" />
                        <input type="hidden" name="type" value="notrade" />
                        <input type="hidden" name="world_id" value="'.$world['id'].'" />
                        <input type="hidden" name="db_id" value="'.$world['db_id'].'">
                        <div><div style="font-family: Helvetica,Arial,sans; float: left; font-size: 0.7em; width: 30px; padding: 1px 10px">id: '.$p['id'].'</div>
                        <font color=#F8F8FF>Starts:&nbsp;</font>
                        <input type="text" size="21" name="start_date" value="'.$p['start_date'].'" />
                        <font color=#F8F8FF>&nbsp;&nbsp;Ends:&nbsp;</font>
                        <input type="text" size="21" name="end_date" value="'.$p['end_date'].'" />
                        &nbsp;<input type="submit" class="submit" value="Update" />&nbsp;
                        <a href="?action=delete&dbid='.$world['db_id'].'&worldid='.$world['id'].'&periodid='.$p['id'].'&type=notrade" class="alert" {literal}onclick="if(!confirm(\'Really delete?\')) { return false; }"{/literal}>delete</a></div>
                        </form>
                        </div>';
                    }
                }
            }
        } elseif ($this->action == 'add')
        {
            if (($this->worldId === false || $this->dbId === false) && $this->worldId !== 0)
            {
                echo '
                    <div class="mess_error">Missing required parameters. Aborted</div>
                <div>World id: {$world_id}, DB id: {$db_id}</div>
                <div><a href="?">Back</a></div>';
            } else {
                if ($this->worldId === 0)
                {
                    echo '<h1>Add a new period to all worlds</h1>';
                } else {
                    echo '<h1>Add a new period to the world &ldquo;'.$this->worldName.'&rdquo;</h1>';
                }

                echo '
                <form action="?" method="POST">
                    <input type="hidden" name="action" value="save" />
                    <input type="hidden" name="world_id" value="'.$this->worldId.'" />
                    <input type="hidden" name="db_id" value="'.$this->dbId.'" />
                    <div STYLE="color:#F8F8FF;"><input type="checkbox" name="type[]" id="peaceperiod" value="peace" checked="checked" /><label for="peaceperiod"> Peace period </label>&nbsp;<input type="checkbox" name="type[]" id="notradeperiod" value="notrade" checked="checked" /><label for="notradeperiod"> No-trade period </label></div><br>
                    <div STYLE="color:#F8F8FF;">Start:&nbsp;<input type="text" size="21" name="start_date" value="'.date('Y-m-d H:i:s').'" />&nbsp;&nbsp;End:&nbsp;<input size="21" type="text" name="end_date" value="'.date('Y-m-d H:i:s').'" />&nbsp;<input type="submit" class="submit" value="Add" /></div>
                </form>';

            }
        } elseif ($this->action == 'save')
        {
            if (isset($this->error))
            {
                echo '
                <div class="mess_error">Error: {$error_msg}</div>';
            } else {
                echo '
                <div class="mess_info">Period successfully added</div>';
            }
            echo '
                <div><a href="?">Back</a></div>';
        } elseif ($this->action == 'delete')
        {
            if (isset($this->error))
            {
                echo '
                    <div class="mess_error">Period was not deleted, an error occurred.</div>
                    <div><a href="?">Back</a></div>';
            } else {
                echo '<div class="mess_info">Period was deleted successfully</div>
                    <div><a href="?">Back</a></div>';
            }
        }
    }
}

?>