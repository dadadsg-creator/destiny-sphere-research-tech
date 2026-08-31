<?php
/**
 * Продажа ресурсов за реальное бабло.
 *
 */
class BG_Artefact extends BG_Common
{
    /**
     * id арта, который рисуем
     *
     * @var int
     */
    private $artefactID;

    /**
     * id игрока, кто купил
     *
     * @var int
     */
    private $whoPay;

    /**
     * id соты, на которую рисуем арт
     *
     * @var int
     */
    private $combID;


    public function __construct($data)
    {
        parent::__construct($data);
    }

    /**
     * Функция проверок возможности вылезания из зада
     * */
    public function check(){
        $this->_check();
        $this->outputAnswer();
        return;
    }

    private function collectData(){

        // id инстанса
        if(!$this->uiid = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID((int)$this->incoming_info['id_user'],
        (int)$this->id_world)){

            $this->error_status = 2;
            return false;
        }

        if(!$userInstance = DE_UserInstance::CreateFromID($this->uiid)){

            $this->error_status = 3;
            return false;
        }

        if(!$combs = $userInstance -> getCombsCollection() ) {

            $this->error_status = 4;
            return false;
        }

        while ($comb = $combs->next()){
            if($comb->getCombRing()->getNumber() != 4){
                $this->combID = $comb->getCombID();
                break;
            }
        }

        if(!$this->combID){
            $this->error_status = 5;
            return false;
        }

        // данные артефакта

        if(!$artefact = DS_Archeology::getArtefactIdByName($this->clear_art))
        {
            $this->error_status = 6;
            return false;
        }

        $this->artefactID = $artefact['id_artefact'];

        return true;
    }


    /**
     * Проверка возможности добавления арта
     *
     * @return bool
     */
    private  function _check()
    {
        $rightArtefacts = array(
        'GOLD_COMB_IGROMIR_KADERIUM',
        'GOLD_COMB_IGROMIR_NANO',
        'GOLD_COMB_IGROMIR_STATIC',
        'GOLD_COMB_IGROMIR_FOOD',
        'GOLD_COMB_IGROMIR_PRODIUM',
        'BUILDING_IGROMIR_KADERIUM',
        'BUILDING_IGROMIR_NANO',
        'BUILDING_IGROMIR_FOOD',
        'BUILDING_IGROMIR_STATIC',
        'BUILDING_IGROMIR_PRODIUM',
        'COMB_IGROMIR_KADERIUM',
        'COMB_IGROMIR_NANO',
        'COMB_IGROMIR_FOOD',
        'COMB_IGROMIR_STATIC',
        'COMB_IGROMIR_PRODIUM',
        'COMBCOMPLEX_IGROMIR_KADERIUM',
        'COMBCOMPLEX_IGROMIR_NANO',
        'COMBCOMPLEX_IGROMIR_FOOD',
        'COMBCOMPLEX_IGROMIR_STATIC',
        'COMBCOMPLEX_IGROMIR_PRODIUM',
        'ALLIANCE_IGROMIR_KADERIUM',
        'ALLIANCE_IGROMIR_NANO',
        'ALLIANCE_IGROMIR_FOOD',
        'ALLIANCE_IGROMIR_STATIC',
        'ALLIANCE_IGROMIR_PRODIUM',
        'IGROMIR_ATTACK',
        'IGROMIR_DEFEND',
        'IGROMIR_HITPOINTS',
        'IGROMIR_DAMAGE',
        'PAID_BUILDING_IGROMIR_KADERIUM',
        'PAID_BUILDING_IGROMIR_NANO',
        'PAID_BUILDING_IGROMIR_FOOD',
        'PAID_BUILDING_IGROMIR_STATIC',
        'PAID_BUILDING_IGROMIR_PRODIUM',
        'PAID_COMB_IGROMIR_KADERIUM',
        'PAID_COMB_IGROMIR_NANO',
        'PAID_COMB_IGROMIR_FOOD',
        'PAID_COMB_IGROMIR_STATIC',
        'PAID_COMB_IGROMIR_PRODIUM',
        'PAID_COMBCOMPLEX_IGROMIR_KADERIUM',
        'PAID_COMBCOMPLEX_IGROMIR_NANO',
        'PAID_COMBCOMPLEX_IGROMIR_FOOD',
        'PAID_COMBCOMPLEX_IGROMIR_STATIC',
        'PAID_COMBCOMPLEX_IGROMIR_PRODIUM',
        'PAID_ALLIANCE_IGROMIR_KADERIUM',
        'PAID_ALLIANCE_IGROMIR_NANO',
        'PAID_ALLIANCE_IGROMIR_FOOD',
        'PAID_ALLIANCE_IGROMIR_STATIC',
        'PAID_ALLIANCE_IGROMIR_PRODIUM',
        'PAID_WAR_IGROMIR_ATTACK',
        'PAID_WAR_IGROMIR_DEFEND',
        'PAID_WAR_IGROMIR_HITPOINTS',
        'PAID_WAR_IGROMIR_DAMAGE',
        'NANO_LOCAL_ARTEFACT',
        'REDS_LOVE_KADERIUMHEART',
        'REDS_LOVE_NANOHEART',
        'REDS_LOVE_ENERGYHEART',
        'REDS_LOVE_PRODIUMHEART',
        'REDS_LOVE_FOODHEART'
        );

        list($clear_art, $len) = $this->getLifeTime(); //возможно арт пришёл с приставкой LTnn
        $this->clear_art = $clear_art;
        $this->lifetime = $len;

        if  (!in_array($this->clear_art, $rightArtefacts))
        {
            $this->error_status = 1;
            return ;
        }

        if (empty($this->incoming_info['id_user']) || empty($this->incoming_info['worldname']) ||
        empty($this->incoming_info['artefact_name']) ) {

            $this->error_status = 1;
            return ;
        }

        if(!$this->connectToNeededDB()){
            return false;
        }
        return $this->collectData();
    }


    function getLifeTime()
    {
        $len = 0;
        $parts = explode('_',$this->incoming_info['artefact_name']);
        if (count($parts)==1)
        return array($this->incoming_info['artefact_name'], 0);
        else
        {
            $lp = array_pop($parts);
            if (preg_match('/^LT(\d+)/',$lp, $matches))
            {
                $len = $matches[1];
            }
            else
            {
                $parts[] = $lp; //возвращаем отсечённую часть обратно
            }
        }
        return array(implode('_',$parts), $len );
    }

    /**
     * Рисуем арт
     *
     */
    public function proceed()
    {


        if(!$this->_check()){
            return;
        }

        // Кто заплатил
        $this->whoPay = (int)$this->incoming_info['who_pay'];
        if($this->whoPay <= 0){
            $this->error_status = 22;
            return false;
        }

        $quantity = isset($this->incoming_info['quantity'])?intval($this->incoming_info['quantity']):1;
        if (!$quantity)
        $quantity = 1;

        for($i=0; $i < $quantity;$i++)
        {
            $sql = "SELECT
                        add_artefact_to_comb(".(int)$this->combID.", ".(int)$this->artefactID.",".$this->lifetime. ")";
            $this->common->executeQuery($sql);
            assert(empty($this->common->errormsg));
        }
        $this->outputAnswer();

        // Если игрок купил арт в подарок, шлем сообщение в зеленку
        if ((int)$this->incoming_info['id_user'] != $this->whoPay) {
            // Системное сообщение
            $messageScopeID = 10;

            // От игрока DestinyShere
            $fromUIID = 0;
            $senderName = 'DestinySphere';

            // Заголовок
            $subject ="Добавление артефарктов";
            $subject = pg_escape_string(urldecode($subject));
            assert(!empty($subject));

            // Кому отправляем сообзение
            $userInstance = DE_UserInstance::CreateFromID($this->uiid);
            $recieverName = $userInstance->getUser()->getName();

            // Название артефакта
            $artName = DE_Artefact::CreateFromArtefactID((int)$this->artefactID)->getLocalizedName();
            // Кто подарил
            $grantor = DE_User::CreateFromID($this->whoPay)->getName();

            // Если это валентинка
            if ($this->clear_art == 'REDS_LOVE_KADERIUMHEART' || $this->clear_art == 'REDS_LOVE_NANOHEART'
            || $this->clear_art == 'REDS_LOVE_ENERGYHEART' || $this->clear_art == 'REDS_LOVE_PRODIUMHEART'
            || $this->clear_art == 'REDS_LOVE_FOODHEART') {
                
                $message =
                'Уважаемый '.$recieverName.',
'.$grantor.' поздравил Вас с Днем Святого Валентина и прислал Вам Артефакт '.$artName.'.';
            } else {
                $message =
                'Уважаемый '.$recieverName.',
Вам пришел артефакт '.$artName.' от '.$grantor.'.';
            }
            $message = pg_escape_string(GL_EncodeGameMessage::Encode(urldecode($message)));
            assert(!empty($message));

            $messageID = DS_Message::CreateMessage($message, $subject, $senderName, $recieverName, $messageScopeID, $fromUIID);

            DS_Message::AddMessageToUser($messageID, $userInstance->getUserInstanceID());
            //DS_Message::AddMessageToUser($messageID, $userInstance->getUserInstanceID(), (int)$this->id_world);
        }
    }
}

?>