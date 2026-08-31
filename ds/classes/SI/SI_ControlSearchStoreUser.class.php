<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_ControlSearchStoreUser
{
    private $common = false;
    
    private $tpl = false;
    
    private $srchName = false;
    
    private $srchReason = false;
    
    private $transferType = false;
    
    private $transferTypes = array( 'all' => 'ALL', 'auto' => 'auto', 'manual' => 'manual');
    
    private $offset = 0;
    
    private $limit = 20;
    
    private $db_from = '';
    
    private $world_from = 0;
    
    private $db_to = '';
    
    private $world_to = '';
    
    public function __construct($data)
    {
        $this->common = DS_Common::CreateForCurentDB(0);
        
        $this->getParams($data);
        
        $this->common->setDbId(0);
        SI_SearchInterface::SetCommon($this->common);
        
        $this->tpl = new Smarty();
        $this->tpl->template_dir = "./templates/";
        $this->tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $this->tpl->compile_check = true;
        $this->tpl->debugging = false;
    }
    
    private function _outUserStoredList($res)
    {
        assert($this->tpl);
        
        $this->tpl->assign("REASON", SI_SearchInterface::GetReasonList());
        $this->tpl->assign("SRCH_RESULTS", $res);
        $this->tpl->assign("PAGES", $this->getPageList());
        $this->tpl->assign("OFFSET", $this->offset);
        $this->tpl->assign("WORLD_FROM", $this->getWorldList(true));
        $this->tpl->assign("WORLD_TO", $this->getWorldList(false));
        $this->tpl->assign("TYPES", $this->transferTypes);
        
        $str = '';
        
        if($this->srchName)
        {
            $str .= "&name=".$this->srchName;
            $this->tpl->assign('SRCH_NAME', $this->srchName);
        }
        
        if($this->srchReason)
        {
            $str .= "&reason=".$this->srchReason;
            $this->tpl->assign('SRCH_REASON', $this->srchReason);
        }
        
        if(!empty($this->db_from))
        {
            $str .= "&world_from=".$this->db_from."_".$this->world_from;
            $this->tpl->assign('SRCH_WORLD_FROM', $this->db_from."_".$this->world_from);
        }
        
        if(!empty($this->db_to))
        {
            $str .= "&world_to=".$this->db_to."_".$this->world_to;
            $this->tpl->assign('SRCH_WORLD_TO', $this->db_to."_".$this->world_to);
        }
        
        if(!empty($this->transferType))
        {
            $str .= "&type=".$this->transferType;
            $this->tpl->assign('SRCH_TYPE', $this->transferType);
        }
        
        $this->tpl->assign("SRCH", $str);
        
        $this->tpl->display("UserList.tpl");
    }
    
    public function search()
    {
        $sql = $this->_getSrchQuery();
        
        $this->common->SetDbId(0);
        $res = $this->common->getResultArray("", $sql, "", "", "");
        
        assert(empty($this->common->errormsg));
        
        foreach($res as $k => $val)
        {
            foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
            {
                if($db['server'] == $val['db_name_from'])
                {
                    $res[$k]['dbId'] = $dbId;
                    break;
                }
            }
        }
        
        $this->_outUserStoredList($res);
    }
    
    private function getParams($data)
    {
        if(isset($data['name']))
        {
            $this->srchName = $data['name'];
        }
        
        if(isset($data['reason']))
        {
            $this->srchReason = $data['reason'];
        }
        
        if(isset($data['offset']))
        {
            $this->offset = (int)$data['offset'];
        }
        
        if(isset($data['world_from']))
        {
            $arr = explode("_", $data['world_from']);
            $this->db_from = $arr[0];
            $this->world_from = $arr[1];
        }
        
        if(isset($data['world_to']))
        {
            $arr = explode("_", $data['world_to']);
            $this->db_to = $arr[0];
            $this->world_to = $arr[1];
        }
        
        if(isset($data['type']))
        {
            $this->transferType = $data['type'];
        }
    }
    
    private function getPageList()
    {
        if($this->transferType == 'manual' || $this->transferType == 'all')
        {
            $where1 = $this->_getWhereQueryManualTransfer();
            $sql1 = "
                SELECT 
                    count(*) 
                FROM 
                    ds_storeuserdata AS sud
                    INNER JOIN ds_user AS u ON u.id_user = sud.id_user
                ".$where1;
            $res = $this->common->getResultArray("", $sql1, "", "", "");
            assert(empty($this->common->errormsg));
            
            $count1 = (int)$res[0]['count'];
        } else {
            $count1 = 0;
        }
            
        if($this->transferType == 'auto'  || $this->transferType == 'all')
        {
            $where2 = $this->_getWhereQueryAutoTransfer();
            $sql2 = "
                SELECT 
                    count(*) 
                FROM 
                    ds_worldtransfer_stat AS wts
                    INNER JOIN ds_user AS u ON u.id_user = wts.id_user
                ".$where2;
            $res = $this->common->getResultArray("", $sql2, "", "", "");
            assert(empty($this->common->errormsg));
            
            $count2 = (int)$res[0]['count'];
        } else {
            $count2 = 0;
        }   
            
        $pageCount = ceil(($count1 + $count2) / $this->limit);

        for($i=0; $i<$pageCount; $i++)
        {
            $page[$i+1] = $i*$this->limit;
        }
        
        return $page;
    }
    
    
    private function _getWhereQueryManualTransfer()
    {
        $query = '';
        
        if($this->srchName)
        {
            $query .= " AND u.username LIKE '%".$this->srchName."%' ";
        }   
        
        if($this->srchReason)
        {
            $query .= " AND sud.id_storeuserreason = ".$this->srchReason." ";
        }   
        
        if(!empty($this->db_from))
        {
            $query .= " AND db_name_from = '".$this->db_from."' AND id_world_from = ".$this->world_from;
        }
        
        if($this->db_to == 'NO')
        {
            $query .= " AND db_name_to IS NULL";
        } elseif(!empty($this->db_to)) {
            $query .= " AND db_name_to = '".$this->db_to."' AND id_world_to = ".$this->world_to;
        }
        
        return $query;
    }
    
    private function _getWhereQueryAutoTransfer()
    {
        $query = '';
        
        if($this->srchName)
        {
            $query .= " AND u.username LIKE '%".$this->srchName."%' ";
        }   
            
        if(!empty($this->db_from))
        {
            $query .= " AND db_from = '".$this->db_from."' AND id_world_from = ".$this->world_from;
        }
        
        if(!empty($this->db_to) && $this->db_to != 'NO') 
        {
            $query .= " AND db_to = '".$this->db_to."' AND id_world_to = ".$this->world_to;
        }
        
        return $query;
    }
    
    private function _getSrchQuery()
    {
        switch ($this->transferType)
        {
            case 'auto':
                $query = $this->_getSrchQueryAutoTransfer();
                break;
        
            case 'manual':
                $query = $this->_getSrchQueryManualTransfer();
                break;
                
            default:
                $query1 = $this->_getSrchQueryAutoTransfer();
                $query2 = $this->_getSrchQueryManualTransfer();
                
                $query = "
                    SELECT
                        *
                    FROM
                        (
                            $query1
                        ) as q1
                        UNION ALL
                        (
                            $query2
                        )
                    ";
                break;
        }
        
        $query .= "
            ORDER BY 
                store_date DESC
            LIMIT 
                ".$this->limit." 
            OFFSET 
                ".$this->offset."
            ";
        
        return $query;
    }
    
    private function _getSrchQueryManualTransfer()
    {
        $where = $this->_getWhereQueryManualTransfer();
        
        $query = "
            SELECT 
                u.id_user,
                u.username,
                
                sud.db_name_from,
                sud.id_world_from,
                sud.world_from_name,
                sud.store_admin_name,
                
                sud.db_name_to,
                sud.id_world_to,
                sud.world_to_name,
                sud.restore_admin_name,
                
                sud.store_date,
                sud.restore_date,
                
                sur.reason_name,
                sud.id_storeuserdata,
                sud.comments
            FROM 
                ds_storeuserdata AS sud
                INNER JOIN ds_user AS u ON u.id_user = sud.id_user
                INNER JOIN ds_storeuserreason AS sur ON sur.id_storeuserreason = sud.id_storeuserreason
            WHERE
                TRUE
                ".$where."
            ";
        return $query;
    }
    
    private function _getSrchQueryAutoTransfer()
    {
        $where = $this->_getWhereQueryAutoTransfer();
                
        $query = "
            SELECT 
                wts.id_user,
                u.username,
                
                wts.db_from AS db_name_from,
                wts.id_world_from,
                wc_from.worldname AS world_from_name,
                cast('auto' as varchar) AS store_admin_name,
                
                wts.db_to AS db_name_to,
                wts.id_world_to,
                wc_to.worldname AS world_to_name,
                cast('auto' as varchar) AS restore_admin_name,
                
                wts.transfer_date AS store_date,
                wts.transfer_date AS restore_date,
                
                cast('auto' as varchar) AS reason_name,
                0 AS id_storeuserdata,
                cast('auto' as varchar) AS comments
                
            FROM 
                ds_worldtransfer_stat AS wts
                INNER JOIN ds_user AS u ON wts.id_user = u.id_user
                
                INNER JOIN ds_worldcollection AS wc_from ON (wc_from.dbname = wts.db_from AND wc_from.id_world = wts.id_world_from)
                INNER JOIN ds_worldcollection AS wc_to ON (wc_to.dbname = wts.db_to AND wc_to.id_world = wts.id_world_to)
            WHERE
                TRUE
                ".$where."
            ";
        return $query;
    }
    
    private function getWorldList($from)
    {
        $worldList = array();

        $worldList[] = 'ALL';
        
        foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
        {
            $this->common->setDbId($dbId);
            $sql = "
                SELECT
                    id_world,
                    worldname
                FROM
                    ds_world
                ";
            $res = $this->common->getResultArray("", $sql, "", "", "");
            assert(empty($this->common->errormsg));
            
            foreach($res as $k => $world)
            {
                $worldList[$db['server'].'_'.$world['id_world']] = $world['worldname'];
            }
        }
        
        if(!$from)
        {
            $worldList['NO_0'] = 'not ended';
        }
        
        return $worldList;
    }
}

?>