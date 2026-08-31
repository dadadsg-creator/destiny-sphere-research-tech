<?php


/**
 * Ќаграждение победителей турнира
 * 1 место = 6500, 50 место = 130.
 *
 */
class TN_RewardSender
{
    static private $prizePlaces = 50;
    
    static private $startPrize = 6500;
    
    static private $gates = array(
            'UN' => 'http://ds-game.ru/ds/gate/turnir.php',
            'CL' => 'http://ds.rambler.ru/ds/gate/turnir.php',
            'TS' => 'http://unlim/ds/gate/turnir.php',
        );
    
    /**
     * Enter description here...
     *
     * @var SU_HTTPRequest
     */
    static private $request;
    
    static private $turnirID;
    
    static public function SendPrizeForTurnir($turnirID)
    {
        assert(is_int($turnirID) && $turnirID > 0);
        
        self::$turnirID = $turnirID;
        
        $turnir = TN_Search::GetTurnirByID($turnirID);
        
        if(count($turnir) == 0)
        {
            return false;
        }
        
        if(!empty($turnir['prize_send_date']))
        {
            return false;
        }
        
        $results = TN_Search::GetTopUsers($turnirID, 50);
        $reward = array();

        foreach ($results as $k => $user)
        {
            if($user['prize_recieved'] > 0)
            {
                continue;
            }
            $prize = ceil(self::$startPrize / ($k + 1));
            $userData = explode('__', $user['username']);
            if(!key_exists(1, $userData))
            {
                $userData[1] = 'TS';
            }
            assert(count($userData) == 2);
            $reward[$userData[1]][] = $userData[0].':'.$prize;
        }

        self::_initHTTP();
        
        foreach ($reward as $version => $data)
        {
            if(self::_sendData($data, $version))
            {
                self::_markUsersAsRecievedPrize($data);
            }
        }
        
        return true;
    }
    
    static private function _initHTTP()
    {
        self::$request = new SU_HTTPRequest();
        
        self::$request->setOption('method', 'POST');
        self::$request->addHeader("Content-type: application/x-www-form-urlencoded");
    }
    
    static private function _sendData(array $data, $version)
    {
        assert(key_exists($version, self::$gates));
        
        $dataStr = implode(';', $data);
        
        self::$request->setVar('data', base64_encode($dataStr));
        self::$request->setVar(md5('gate_magic'), md5('TURNIR_RESULT'.date('Y-d-m').$dataStr));
        $res = self::$request->get(self::$gates[$version]);
        if($res == md5('TURNIR_RESULT_OK'.date('Y-d-m')))
        {
            return true;
        }
        
        return false;
    }
    
    static private function _markUsersAsRecievedPrize(array $data)
    {
        foreach ($data as $k => $userData)
        {
            list($username,$prize) = explode(':', $userData);
            $prize = intval($prize);
            TN_Search::MarkUserAsRecievedPrize(self::$turnirID, $username, $prize);
        }
    }
}

?>