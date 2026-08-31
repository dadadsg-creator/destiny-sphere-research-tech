<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2008/07/01 08:46:26 $';
/**
 * Объект, который представляет рейтинг игрока
 *
 */

class DE_Ranking extends DE_Base
{
    // Научный рейтинг
    private $scienceRanking;

    // Артефактный рейтинг
    private $artefactRanking;

    // Строительный рейтинг
    private $buildingRanking;

    // Боевой рейтинг
    private $battleRanking;

    // Уровень
    private $level;

    // Юзверь
    private $userNpc;

    function __construct($id)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_int($id));

        parent::__construct($id);

        $data = DS_Ranking::getByID($id);

        assert(is_array($data) && count($data)>0);

        assert(key_exists('science_ranking', $data));
        $this->scienceRanking = (float)$data['science_ranking'];

        assert(key_exists( 'artefact_ranking', $data));
        $this->artefactRanking = (float)$data['artefact_ranking'];

        assert(key_exists( 'building_ranking', $data));
        $this->buildingRanking = (float)$data['building_ranking'];

        assert(key_exists( 'battle_ranking', $data));
        $this->battleRanking = (float)$data['battle_ranking'];

        assert(key_exists( 'id_level', $data));
        $this->level = (float)$data['id_level'];

        $this->userNpc = DE_User::CreateFromID(DE_UserInstance::CreateFromID($id)->getUserID())->getIsUserNpc();
        assert($this->userNpc == 't' || $this->userNpc == 'f');

        $this->_invariant('construct');
    }

    static public function CreateFromID($id)
    {
        assert(is_int($id));
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);

    }

    public function deltaScienceRanking($value)
    {
        // Если игрок npc
        if ($this->userNpc == 't') {
            // Ничего не меняем
            return;
        }

        $this->scienceRanking += $value;

        // Проверка изменения уровня
        $this->_checkLevelCahge();

        $this->isModifyed = true;
    }

    public function deltaAtefactRanking($value)
    {
        // Если игрок npc
        if ($this->userNpc == 't') {
            // Ничего не меняем
            return;
        }

        $this->artefactRanking += $value;

        // Проверка изменения уровня
        $this->_checkLevelCahge();

        $this->isModifyed = true;
    }

    public function deltaBattleRanking($value)
    {
        // Если игрок npc
        if ($this->userNpc == 't') {
            // Ничего не меняем
            return;
        }

        $this->battleRanking += $value;

        // Проверка изменения уровня
        $this->_checkLevelCahge();

        $this->isModifyed = true;
    }

    public function deltaBuildingRanking($value)
    {
        // Если игрок npc
        if ($this->userNpc == 't') {
            // Ничего не меняем
            return;
        }

        $this->buildingRanking += $value;

        // Проверка изменения уровня
        $this->_checkLevelCahge();

        $this->isModifyed = true;
    }

    public function getLevel()
    {
        return $this->level;
    }

    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;

        parent::_invariant($mode);

        assert($this->scienceRanking >= 0);
        assert($this->artefactRanking >= 0);
        assert($this->buildingRanking >= 0);
        assert($this->battleRanking >= 0);
        //assert($this->level >= 0);
    }

    public function _checkLevelCahge($mode = 'public')
    {
        // Смотрим, поменялся ли уровень
        $culLevel = DS_Ranking::GetAccordanceLevel($this->id, $this->scienceRanking, $this->artefactRanking, $this->buildingRanking, $this->battleRanking);
        assert($culLevel >= 0);
        
        // если есть соответствующий уровень, и он не равен текущему
        if ($culLevel > 0 && $culLevel != $this->level) {
            $this->level =  $culLevel;

            // Шлем сообщение в зеленку о смене уровня

            // Если лиги включены
            if (DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_ENABLED') == 1) {


                // Проверяем, не поменялась ли лига у игрока по смене уровня
                // Текущая лига
                $CurLeague = DE_UserInstance::CreateFromID($this->id)->getLeagueId();

                // Лига, соответствующая уровню игрока
                $league = DS_League::GetLeagueByLevel($culLevel);

                // Если есть соответствующая времени жизни лига и она больше от текущей
                if ($league['id_league'] > $CurLeague) {

                    //assert($league['id_league'] > $CurLeague);
                    DE_UserInstance::CreateFromID($this->id)->setLeagueId((int)$league['id_league']);

                    // Сообщение
                    $messparams = array($culLevel, $league['league_name']);
                    $date = GB_TypeFactory::CreateConstant('GB_Date', time());
                    // Командор, стало известно, что Вы обладаете слишком большим количеством мощных армии, дислоцирующихся на Ваших сотах. Принято решение перевести Вас в Лигу %, которой соответствуют такие масштабы! Поздравляем!
                    DE_SystemMessages::AddMessageFromMessageCompiler($date, $this->id, 'NEXT_LEAGUE_LEVEL', $messparams, 0, 'BLUE');
                    //SU_DebugTools::LogAll('$this->id = '.$this->id, 'Annita.log');

                    // Сообщение в зеленку
                    $messageScopeID = DS_Message::GetMessageScopeIDByName('PRIVATE');
                    assert($messageScopeID > 0);
                    $senderName = 'DestinySphere';
                    $subject = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NEXT_LEAGUE', array(), null, null, false);
                    $recieverName = DE_User::CreateFromID(DE_UserInstance::CreateFromID($this->id)->getUserID())->getName();
                    $fromUIID = 0;

                    $message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NEXT_LEAGUE_LEVEL', $messparams, null, null, false);
                    $messageID = DS_Message::CreateMessage($message, $subject, $senderName, $recieverName, $messageScopeID, $fromUIID);

                    DS_Message::AddMessageToUser($messageID, $this->id);

                }
            }
        }
    }

    public function commit()
    {
        parent::commit();

        if($this->isModifyed)
        {
            DS_Ranking::UpdateRanking($this->id, $this->scienceRanking, $this->artefactRanking, $this->buildingRanking, $this->battleRanking, $this->level);

            $this->isModifyed = false;
        }
    }
}//END OF CLASS


?>