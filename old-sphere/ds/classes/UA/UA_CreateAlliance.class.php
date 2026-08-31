<?php $CVS_Version = '$Revision$ ; $Date$';

// команда создать альянс




class UA_CreateAlliance extends UA_Alliance {

    public $tag;
    public $alliancename;
    public $joinpassword;
    public $errorname;

    function UA_CreateAlliance()
    {
        UA_Alliance::UA_Alliance();
        $this->errorname = array();
    }

    // проверяем параметры
    function checkLocalParams()
    {
        global $common;

        // краткое название альянса
        $ch = $this->root->get_elements_by_tagname("tag");
        $node = $ch[0];
        $this->tag = trim(utf8_decode($node->get_content()));

        if(empty($this->tag))
        {
            $this->errormsg .= " SYSTEM ERROR UA_CreateAlliance::checkLocalParams1";
            return false;
        }

        $from = 'abcdefghijklmnopqrstuvwxyzёйцукенгшщзхъфывапролджэячсмитьбю???';
        $to = 'ABCDEFGHIJKLMNOPQRSTUVWXYZЁЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ???';

        $this->tag = strtr($this->tag, $from, $to);

        // полное название альянса
        $ch = $this->root->get_elements_by_tagname("name");
        $node = $ch[0];
        $this->alliancename = iconv("UTF-8", "windows-1251", utf8_decode($node->selfNode->textContent));
        if ((constant('DEFAULT_LANGUAGE_SHORT') == 'L') && $_SESSION['oauth_token'])
        $odata = MakeOAuthNotification($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'] , array('type' => 'alliance', 'name'=> $this->alliancename));    

    
        if(empty($this->alliancename))
        {
            $this->errormsg .= " SYSTEM ERROR UA_CreateAlliance::checkLocalParams2";
            return false;
        }

        // пароль вступления в альянс
        $ch = $this->root->get_elements_by_tagname("joinpassword");
        $node = $ch[0];
        $this->joinpassword = trim(utf8_decode($node->get_content()));

        //$this->joinpassword = utf8_decode ( $this->root->get_attribute ( "joinpassword" ) );
        if(empty($this->joinpassword))
        {
            $this->errormsg .= " SYSTEM ERROR UA_CreateAlliance::checkLocalParams3";
            return false;
        }

        if(!VD_Alliance::Validate($this->alliancename, $this->tag, $this->joinpassword, $this->world, $common))
        {
            $this->errorname[] = VD_Alliance::FetchErrorCode();
        }

        return true;
    }

    // проверяем допустимость действия
    function checkAction () {

        global $common;
        global $params;

        if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
        {
            $this->_vocationModeAlert();
            return true;
        }

        if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
        {
            $this->_shildModeAlert();
            return true;
        }

        if ( sizeof ( $this->errorname > 0 ) ) return true;

        // состоит ли уже в альянсе
        $sql = sprintf ( "select id_alliance
            from ds_userhasalliance
            where id_userinstance = %d",
        $params["uiid"] );

        $res = $common->getResultArray ( "id_alliance_id_userinstance_".$params["uiid"], $sql, "", $params["uiid"], "LOCAL_CACHE" );
        if ( $res === false ) {
            $this->errormsg .= " SYSTEM ERROR UA_CreateAlliance::checkAction1";
                
            return false;
        }

        if ( sizeof ( $res ) > 0 ) {
            $this->errormsg .= " SYSTEM ERROR UA_CreateAlliance::checkAction2";
                
            return false;
        }


        return true;
    }

    // выполняем действеие
    function performAction () {

        global $params;
        global $common;
        if ( sizeof ( $this->errorname ) == 0 ) {

            // создаем альянс
            $sql = sprintf ( "insert into ds_alliance ( alliancename, id_world, alliancetag, joinpassword, creationdate, members_amount )
                values ( '%s', %d, '%s', '%s', now(), 1 )",
            $this->alliancename,
            $this->world,
                "[".$this->tag."]",
            $this->joinpassword );

            $res = $common->executeQuery ( $sql );
            
            if ( $res === false ) {
                $this->errormsg .= " SYSTEM ERROR UA_CreateAlliance::performAction1";
                return false;
            }

            // получаем его id
            $sql = sprintf ( "select id_alliance
                from ds_alliance
                where alliancetag = '%s'
                and id_world = %d",
                "[".$this->tag."]",
            $this->world );

            $res = $common->getResultArray ( "id_alliance_alliancetag_".$this->tag, $sql, "", "", "" );
            if ( !$res ) {
                $this->errormsg .= " SYSTEM ERROR UA_CreateAlliance::performAction2";

                return false;
            }

            $id_alliance = $res[0]['id_alliance'];

            // записываем юзера в этот альянс
            $sql = sprintf ( "insert into ds_userhasalliance
                ( id_alliance, id_userinstance, joindate )
                values ( %d, %d, now() )",
            $id_alliance, $params["uiid"] );

            $res = $common->executeQuery ( $sql );
            if ( $res === false ) {
                $this->errormsg .= " SYSTEM ERROR UA_CreateAlliance::performAction3";

                return false;
            }

            // даем ему статус лидера
            $sql = sprintf ( "insert into ds_userhasalliancestatus
                ( id_status, id_alliance, id_userinstance )
                values ( (select id_status from ds_useralliancestatus where statusname = 'LEADER'), %d, %d )",
            $id_alliance, $params["uiid"] );

            $res = $common->executeQuery ( $sql );
            if ( $res === false ) {
                $this->errormsg .= " SYSTEM ERROR UA_CreateAlliance::performAction4";

                return false;
            }

            // добавляем альянсовые messagescopes
            $sql = sprintf ( "insert into ds_userallowedmessagescope
                ( id_userinstance, id_messagescope )
                select %d, id_messagescope
                from ds_messagescope
                where messagescopename in ( 'INTERNAL_ALLIANCE', 'EXTERNAL_ALLIANCE' )",
            $params["uiid"] );

            $res = $common->executeQuery ( $sql );
            if ( $res === false ) {
                $this->errormsg .= " SYSTEM ERROR UA_CreateAlliance::performAction5";

                return false;
            }

            //Для профидактики сперва зачищаем старые скоупы если были
            $sql = "DELETE FROM ds_comballowedtradescope
                    WHERE
                        id_comb IN (SELECT id_comb FROM ds_comb WHERE id_userinstance = ".$this->uiid.")
                        AND
                        id_tradescope = (SELECT id_tradescope FROM ds_trade_scope WHERE tradescopename = 'ALLIANCE_TRADING')";
            $res = $common->executeQuery($sql);
            assert(empty($common->errormsg));
                
            //Прописываем всем его сотам альянсовый скоуп
            $sql = "INSERT INTO ds_comballowedtradescope
                        (id_tradescope, id_comb)
                    SELECT ts.id_tradescope, c.id_comb
                    FROM 
                        ds_comb AS c
                        JOIN ds_trade_scope AS ts ON (ts.tradescopename = 'ALLIANCE_TRADING')
                    WHERE c.id_userinstance = ".$params["uiid"];
            $res = $common->executeQuery($sql);
            assert(empty($common->errormsg));
                
            // приделываем альянсовые артефаки к альянсу
            $sql = sprintf ( "insert into ds_artefactattachedalliance
                    ( id_artefact, id_alliance, id_attachedbuilding )
                    select aab.id_artefact,
                    %d, ab.id_attachedbuilding
                    from ds_attachedbuilding ab,
                    ds_comb co,
                    ds_artefactattachedbuilding aab,
                    ds_artefactinstance ai,
                    ds_artefact a,
                    ds_artefactscope ars
                    where ab.id_comb = co.id_comb
                    and co.id_userinstance = %d
                    and ab.id_attachedbuilding = aab.id_attachedbuilding
                    and aab.id_artefact = ai.id_artefactinstance
                    and ai.id_artefact = a.id_artefact
                    and a.id_artefactscope = ars.id_artefactscope
                    and ars.scopename = 'ALLIANCE'",
            $id_alliance,
            $params["uiid"] );

            $res = $common->executeQuery ( $sql );
            if ( $res === false ) {
                $this->errormsg .= " SYSTEM ERROR UA_CreateAlliance::performAction6";

                return false;
            }

            $messparams[0] = "{b} ".$this->alliancename."{/b}";
                
            // посылаем сообщение
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "CREATE_ALLIANCE_MESS", $messparams );
                
            // снимаем защиту нового игрока
            $userInstance = DE_UserInstance::CreateFromID((int)$params['uiid']);
            if($userInstance->haveNewbDefend())
            {
                $userInstance->clearNewbDefend();
                $messparams = array();
                DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_NEWB_DEFEND", $messparams, 0, 'YELLOW');
            }
        } else { // если ошибка - посылаем сообщение и выходим

            $messparams[0] = "";
            for ( $i=0; $i < sizeof ( $this->errorname ); $i++ ) {
                DE_SystemMessages::AddMessageFromAction(__CLASS__, $this->errorname[$i], $messparams);
            }
        }

        $res = "";
        $sql = "";

        //if ( $_SESSION['profi_game'] ){
        $noCellData = true;
        //} else {
        //  $noCellData = false;
        //}

        // минимальный рефреш
        if ( !$this->refresh ( 2, "", "", "", $noCellData ) ) {
            return false;
        }

        return true;
    }
}

?>