<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2007/12/27 15:21:12 $';
// обработка события возвращение археологической экспедиции
class EH_ArcheologyTeam extends EH_Event
{
    public $checktime;

    private $id_scope;

    private $scopeBaseProbablility;

    private $found = 0;

    private $messparams;

    private $artefactName;

    private $arch_bonus;

    private $id_prefix;

    private $scopeName;
    
    // Очки за найденные артефарты
    private $scores = 0;

    function EH_ArcheologyTeam()
    {
        EH_Event::EH_Event ();
    }

    function handleEvent($eqid)
    {
        $this->eqid = $eqid;

        // если эвент обрабатывается другим игроком (при нападении)
        if(!$this->checktime)
        {
            $this->checktime = 'now()';
        }

        // забираем параметры эвента
        if(!$this->getEventParams())
        {
            return false;
        }

        // проверяем закончилось ли событие на тот момент, когда произошел рефреш
        if(!$this->_isTimeToHandle())
        {
            return true;
        }

        // возвращаем археологов на соту
        $this->_updateArcheologistCount();

        // вычисление бонуса археологии
        $this->_getArchBonus();
        
        /**************** НАХОЖДЕНИЕ КВЕСТОВЫХ ЮНИТОВ *******************/
        $this->_questSearch();
        /************* END OF НАХОЖДЕНИЕ КВЕСТОВЫХ ЮНИТОВ ***************/

        // нахождение артефакта
        $this->_artefactSearch();
        
        // Если что-нибудь нашли
        if ($this->scores != 0) {
            // Увеличиваем очки за артефакт
            $ranking = DE_Ranking::CreateFromID((int)$this->eventparams["ID_UserInstance"]);    
            $ranking->deltaAtefactRanking($this->scores);       
        }

        // прибиваем эвент
        if(!$this->setTrash())
        {
            return false;
        }

        return true;
    }

    
    /**
     * Поиск новогодней фигни
     *
     */
    private function _questSearch()
    {
        if(!DE_WorldSettings::CreateForCurentWorld()->get('ARCHEOLOGY_CAN_FIND_QUEST_UNITS'))
        {
            return false;
        }
        
        if((int)$this->eventparams["CountOfArcheologist"] <= 0)
        {
            return false;
        }
        
        /*// Если это не нейтралка, выходим
        $dest = (int)$this->eventparams["Destination"];
        if ($dest != 3) {
            return false;
        }*/
        
        // Нахождение в нейтралке (АА)
        $scopeBaseProbablility = 0.0000005;
        
        // нашли ли артефакт (вместо бонуса рассы ставим модифаер)
        if(!GL_ArtefactSearch::isFound((int)$this->eventparams["CountOfArcheologist"], (int)$this->eventparams["TimeToSearch"], $scopeBaseProbablility, DE_WorldSettings::CreateForCurentWorld()->get('ARCHEOLOGY_FIND_QUEST_UNITS_PROBABLILITY')))
        {
            return false;
        }
        
        // определяем размер арта
        srand();
        $range = rand(1, 100);  

        if ($range >=76 && $range <=88) {
            // Могучая сферка (как средний АА )
            $possibleArts = array(
                        'COMMON_ICE_TREE1',
                        'COMMON_ICE_TREE2',
                        );
        } else {
            // Ничего не нашли
            return false;
        }
        
        // Срок жизни артефакта
        $lifetime = 0; // Месяца

        // Выбираем случайный арт
        $key = array_rand($possibleArts);
        $artName = $possibleArts[$key];
    
        if (empty($artName)) {
            return false;
        }
        
        $artefact = DS_Archeology::getArtefactIdByName($artName);
        if(count($artefact) == 0)
        {
            return false;
        }
        assert(key_exists('id_artefact', $artefact));
        assert(key_exists('localizedname', $artefact));
        $idArtefact = (int)$artefact['id_artefact'];
        $localizedartefactName = $artefact['localizedname'];
        assert($idArtefact > 0);

        DS_Archeology::addArtefacToComb((int)$this->eventparams["ID_Comb"], (int)$idArtefact, $lifetime);

        // Логируем нахождение арта
        DS_Archeology::LogQuestSearch($this->eventparams, (float)$range, 1, $localizedartefactName);
        
        // Системное сообщение
        $messageDate = GB_TypeFactory::CreateBase('GB_Date', time());
        $UIID = (int)$_SESSION['uiid'];
        
        $messparams[0] = '{b}'.DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"])->getName().'{/b}';
        $messparams[1] = '{b}'.$localizedartefactName.'{/b}';
                
        // С Новым Годом! Ваши археологи принесли вам в подарок на соту % артефакт %.
        DE_SystemMessages::AddMessageFromMessageCompiler($messageDate, $UIID, 'ARCHEOLOGY_FOUND_QUEST_ART_HAPPY_NEW_YEAR', $messparams, 0, 'BROWN', $accumulatorStatus = 'ACCUMULATE'); 

        return true;
    }

    private function _updateArcheologistCount()
    {
        // удаляем экпедицию
        DS_Archeology::deleteArcheologyTeam((int)$this->eventparams["ID_Comb"], (int)$this->eventparams["TeamNumber"]);

        $combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->eventparams["ID_Comb"]);

        // убираем из бродячих археологов
        $combAttributes->deltaAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST', -(float)$this->eventparams["CountOfArcheologist"]);

        // определяем текущее кол-во археологов в наличии
        $current = floor($combAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', true));

        // определяем текущее кол-во бродячих археологов
        $awayAmount = floor($combAttributes->getAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST', true));

        // всего
        $current += $awayAmount;

        // максимальное кол-во археологов
        $max = floor($combAttributes->getAttribute('MAX_AMOUNT', 'ARCHEOLOGIST', true));

        // если текущее кол-во + кол-во в экспедиции больше максимального - урезаем экпедицию
        if(($current + $this->eventparams["CountOfArcheologist"]) > $max)
        {
            $this->eventparams["CountOfArcheologist"] = $max - $current;
        }

        // добавляем ученых из экспедиции к текущему кол-ву на соте
        $combAttributes->deltaAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', (float)$this->eventparams["CountOfArcheologist"]);
    }

    private function _getArchBonus()
    {
        // только бонус расы к археологии
        $this->arch_bonus = DS_Archeology::getRaceBonus((int)DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstance"])->getRaceID());
    }

    private function _artefactSearch()
    {
        // Археологи могут убраться если на соте пока они ходили быди удалены институты
        if((int)$this->eventparams["CountOfArcheologist"] <= 0)
        {
            return;
        }

        // какие артефакты можно найти в этом celltype (scope)
        $scopes = DS_Archeology::getArtefactScopeByCellType((int)$this->eventparams["Destination"]);

        $foundArtefacts = array();
        $found = 0;

        foreach ($scopes as $k => $scope)
        {
            $this->id_scope = (int)$scope['id_artefactscope'];
            $this->scopeBaseProbablility = (float)$scope['baseprobability'];
            $this->scopeName = $scope['scopename'];

            $this->_searchForArtefactScope();

            if($this->found == 1)
            {
                $foundArtefacts[] = array(
                'NAME' => $this->artefactName,
                'PREFIX' => $this->id_prefix,
                'SCOPE' => $this->id_scope
                );
                $found = 1;
            } elseif($this->found == 2) {
                $found = 2;
                break;
            }
        }

        MC_CompilerArcheologyTeam::GetSingl()->addData(DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"]), (int)$this->eventparams["Destination"], $found, $foundArtefacts);
    }

    private function _searchForArtefactScope()
    {
        $id_scope = $this->id_scope;

        // нашли ли артефакт
        if(!GL_ArtefactSearch::isFound((int)$this->eventparams["CountOfArcheologist"], (int)$this->eventparams["TimeToSearch"], $this->scopeBaseProbablility, $this->arch_bonus))
        {
            $this->found = 0;
            return;
        }

        // Проверяем ограничение на количество артефактов
        if(DS_Archeology::getCountArefacts((int)$this->comb) > $this->worldSettings->get('MAX_COMB_ARTEFACT_COUNT'))
        {
            // найтись то артефакт нашелся, а места уже нет
            $this->found = 2;
            return;
        }

        // определяем его размер
        $science = DS_Science::GetScienceBranchLevel('TRADE', $this->uiid);
        $size = DS_Archeology::getArtefactSizeByScopeAndScience($id_scope, $science);
        // в базе нет артефактов соответствующих заданной вероятности
        if(!$size)
        {
            $this->found = 0;
            return;
        }

        // определяем что за артефакт (типы могут быть специфичны для каждого скопа)
        $this->id_prefix = (int) $size;

        // Temporary block for GIANT WAR
        if ($this->id_prefix==7 && $this->id_scope==5)
            $this->id_prefix = 6;

        //Если размер найденного артефакта меньше чем минимальные размер искомого арта, то ставим флаг
        //что ничего не нашли
        if(key_exists("MinSearchArtSize", $this->eventparams))
        {
            $minSize = (int)$this->eventparams["MinSearchArtSize"];
            if($this->id_prefix < $minSize && $this->scopeName != 'QUEST') // квестовые арты под фильтр не попадают
            {
                $this->found = 0;
                return;
            }
        }

        $types = DS_Archeology::getArtefactTypeByScopeAndPrefix($id_scope, $this->id_prefix);
        if(count($types) == 0)
        {
            $this->found = 0;
            return;
        }

        // определили
        $id_type = (int)$types[array_rand($types)];

        $artefact = DS_Archeology::getArtefactIdByScopPrefixAndType($id_scope, $this->id_prefix, $id_type);
        if(count($artefact) == 0)
        {
            $this->found = 0;
            return;
        }
        assert(key_exists('id_artefact', $artefact));
        assert(key_exists('localizedname', $artefact));
        $id_artefact = $artefact['id_artefact'];
        $this->artefactName = $artefact['localizedname'];

        // добавляем artefactinstance в базу (не см хранимые процедуры, инстанс создается в методе commit()), получаем его id
        $artefactInstance = DE_Artefact::CreateInDB((int)$id_artefact);

        // Логируем нахождение квестовых артефактов
        if($this->scopeName == 'QUEST' || $this->scopeName == 'ICE')
        {
            $localizedartefactName = $artefactInstance->getLocalizedName();
            DS_Archeology::LogQuestSearch($this->eventparams, $this->scopeBaseProbablility, 1, $localizedartefactName);
            //DS_Archeology::LogQuestArtefactSearch($this->eventparams, $artefactInstance, $this->scopeBaseProbablility);
        }

        // добавляем его в хранилище
        $id = $artefactInstance->getID()."_".$this->eventparams["ID_Comb"];
        $combArtefact = DE_CombHasArtefact::CreateInDB($id);
        
        
        // Для археологического рейтинга
        $this->scores += $artefactInstance->getScore();

        $this->found = 1;
    }

    public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $chekDate)
    {
        return false;
    }
}

?>