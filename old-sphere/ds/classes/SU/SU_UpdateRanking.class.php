<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_UpdateRanking
{
    /**
     * С какой базой работаем
     *
     * @var int
     */
    private $dbId;
    
    /**
     * Максимальное кол-во транзакций. 
     * Если текущее кол-во больше, то рейтинг не считаем
     *
     * @var int
     */
    private $maxTransactionsCount = 40;
    
    /**
     * @var DS_Common
     */
    private $common;
    
    /**
     * Максимальное время выполнения запросов
     *
     * @var int
     */
    private $dbTimeOut = 1200000;// 20 минут
    
    public function __construct($dbId)
    {
        assert(is_int($dbId) && $dbId >= 0);
        
        $this->dbId = $dbId;
    }
    
    public function Update()
    {
        $this->_log('============================');
        $this->_log('    START UPDATE RANKING    ');
        $this->_log('============================');
        
        //Проверям не закрыта ли база
        if(GameDown($this->dbId))
        {
            $this->_log('======== DB CLOSED =========');
            $this->_log('');
            return;
        }
        $_SESSION['dbId'] = $this->dbId;
        $this->common = DS_Common::CreateForCurentDB($this->dbId);
        
        // проверяем загрузку базы
        if(!$this->_checkDBLoad())
        {
            return;
        }
        
        // Если мы запускаемся по крону и у нас запрещено обновление рейтинга
        $sapi = php_sapi_name();
        if(strstr($sapi, "apache") === false && $this->_updateRankingDisable())
        {
            return;
        }
        
        $this->_updateRanking();
        
        $this->_log('players ranking updated');
        
        $this->_updateAllianceRanking();
        
        $this->_log('alliance ranking updated');
        
        $this->_log('RANKING UPDATE DONE');
        $this->_log('');
    }
    
    private function _checkDBLoad()
    {
        $currentTransactions = RD_RefreshDemonSearch::GetTransactionsCount($this->common, $this->dbId);
        
        if($currentTransactions > $this->maxTransactionsCount)
        {
            $this->_log('=== DB OVERLOADED ['.$currentTransactions.'] ===');
            $this->_log('');
            return false;
        }
        
        return true;
    }
    
    private function _updateAllianceRanking()
    {       
        $sql = "
            set STATEMENT_TIMEOUT to ".$this->dbTimeOut.";
            BEGIN;
            
            TRUNCATE ds_allianceranking;
            
            INSERT INTO ds_allianceranking 
                (members, alliancetag, id_world, alliancename, totalpoints, averagepoints, id_alliance)
            SELECT
                a.members_amount,
                a.alliancetag,
                a.id_world,
                a.alliancename,
                (
                    SELECT
                        sum(fr.thefinal)
                    FROM
                        ds_finalranking AS fr
                        INNER JOIN ds_userhasalliance AS uha USING (id_userinstance)
                    WHERE
                        uha.id_alliance = a.id_alliance
                ),
                (
                    SELECT
                        avg(fr.thefinal)
                    FROM
                        ds_finalranking AS fr
                        INNER JOIN ds_userhasalliance AS uha USING (id_userinstance)
                    WHERE
                        uha.id_alliance = a.id_alliance
                ),
                a.id_alliance
            FROM
                ds_alliance AS a
            WHERE
                a.members_amount > 1;
                
            COMMIT;
            ";
        
        $this->common->executeQuery($sql);
        assert(empty($this->common->errormsg));
    }
    
    private function _updateRanking()
    {
        /*
        modifiername 'DAYS_TO_STORE_RANKING_HISTORY'
        Слегка криво, так как может быть несколько миров в базе.
        Поэтому берется max()
        */ 

        $sql = "
            set STATEMENT_TIMEOUT to ".$this->dbTimeOut.";
            begin;

            DELETE FROM ds_finalrankinghistory
            WHERE
                adddate < 
                (
                    now() - 
                    (
                        SELECT
                            (max(value::integer)||' DAY')::interval
                        FROM
                            ds_gamemodifier
                        WHERE
                            modifiername = 'DAYS_TO_STORE_RANKING_HISTORY'
                    )
                );

            INSERT INTO ds_finalrankinghistory
                (thefinal, id_userinstance)
            SELECT
                thefinal,
                id_userinstance
            FROM ds_finalranking;

            TRUNCATE ds_finalranking;

insert into ds_finalranking SELECT ds_user.username,
 ds_combname.combname, ds_alliance.alliancetag,  (ds_ring.ringnumber || '.'
 || ds_combcomplex.complexnumber || '.' || ds_comb.combnumber) as coords,
 ds_localizedtextlanguage.text as racename, qry_overallranking.thefinal,
 ds_userinstance.id_world, ds_userinstance.id_race, ds_user.id_language,
 ds_user.id_user, ds_userinstance.id_userinstance, ds_alliance.id_alliance
 FROM (((((ds_alliance RIGHT JOIN ds_userhasalliance ON
  ds_alliance.id_alliance = ds_userhasalliance.id_alliance
        AND ds_alliance.alliancetag <> '[MIMICRY]' ) RIGHT JOIN
  (((qry_overallranking INNER JOIN (ds_user INNER JOIN ds_userinstance ON
  ds_user.id_user = ds_userinstance.id_user) ON
  qry_overallranking.id_userinstance = ds_userinstance.id_userinstance) INNER
 JOIN ds_race ON
 ds_userinstance.id_race = ds_race.id_race) INNER JOIN
  (ds_localizedtext INNER JOIN ds_localizedtextlanguage ON
  ds_localizedtext.id_localizedtext =
  ds_localizedtextlanguage.id_localizedtext) ON (ds_race.id_localizedname =
  ds_localizedtext.id_localizedtext) AND (ds_user.id_language =
 ds_localizedtextlanguage.id_language)) ON ds_userhasalliance.id_userinstance
 = ds_userinstance.id_userinstance) INNER JOIN ds_comb ON
 ds_userinstance.id_userinstance = ds_comb.id_userinstance) INNER JOIN
 ds_combcomplex ON ds_comb.id_combcomplex = ds_combcomplex.id_combcomplex)
 INNER JOIN ds_ring ON ds_combcomplex.id_ring = ds_ring.id_ring) INNER JOIN
 ds_combname ON ds_comb.id_comb = ds_combname.id_comb WHERE 
     ds_combcomplex.id_complextype=1 AND ds_user.username NOT IN ('Mimicry');
    
            COMMIT;
        ";
        $this->common->executeQuery($sql);
        assert(empty($this->common->errormsg));
        
        $sql = "
            TRUNCATE ds_detailedranking;
            
            INSERT INTO ds_detailedranking
                (id_userinstance, armyranking, artefactranking, buildingranking, productionranking, scienceranking)
            SELECT
                ui.id_userinstance,
                COALESCE(ar.armysum, 0),
                COALESCE(art.artefactsum, 0),
                COALESCE(b.buildingsum, 0),
                COALESCE(pr.finalranking, 0),
                COALESCE(sc.sciencesum, 0)
            FROM
                ds_userinstance AS ui
                LEFT JOIN subqry_armyranking_final AS ar USING (id_userinstance)
                LEFT JOIN subqry_artefactranking_final AS art USING (id_userinstance)
                LEFT JOIN subqry_buildingranking_final AS b USING (id_userinstance)
                LEFT JOIN subqry_productionranking_final AS pr USING (id_userinstance)
                LEFT JOIN subqry_scienceranking_final AS sc USING (id_userinstance)
            ";
        $this->common->executeQuery($sql);
        assert(empty($this->common->errormsg));
    }
    
    private function _updateRankingDisable()
    {       
        $sql = "
            SELECT
                            value
                        FROM
                            ds_gamemodifier
                        WHERE
                            modifiername = 'SHOW_RANKING'
            ";
        $res = $this->common->getResultArray("", $sql);
        assert(empty($this->common->errormsg));

        assert(count($res) > 0);
        
        if ($res[0]['value'] == 1) {
            return false;
        }
        
        return true;
    }
    
    private function _log($msg)
    {
        $msg = date('Y-d-m H:i:s')." - ".$msg."\n";
        SU_DebugTools::WriteToLog($msg, 'RankingUpdate_'.$GLOBALS['dbWorldAccess'][$this->dbId]['server'].'.log');
    }
}

?>