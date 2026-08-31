<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 
 * Класс гейта для проверки возможности переименования соты

 *

 */

class BG_CheckNewCombName extends BG_Common 

{   

    public function __construct($data)

    {

        parent::__construct($data);     

    }

        

    public function proceed()

    {       
        
        if($this->error_status != 0)
        {
            return;
        }

        if(!array_key_exists('new_combname',$this->incoming_info) ||

            !array_key_exists('id_user',$this->incoming_info) ||

            !array_key_exists('worldname',$this->incoming_info) ||

            !array_key_exists('comb_coords',$this->incoming_info)) 

        {

            $this->error_status = 1;

            $this->outputAnswer(); 
            return;

        }



        if($this->checkIfUserOwnerOfComb() &&

            $this->checkCombName())

                $this->addToResult('checking_result','new_combname_ok');

                    

        $this->outputAnswer(); 
        return;

    }

        

    private function checkCombName()

    {   

        if ( !preg_match('/^[a-zA-Z0-9_]+$/', $this->incoming_info['new_combname'] ) ||

                strlen ( $this->incoming_info['new_combname'] ) < 3 ||

                strlen ( $this->incoming_info['new_combname'] ) > 50)

        {

            $this->error_status = 8;

            return false;

        }

        

        if(!$this->connectToNeededDB())

            return false;

            

        $sql = "SELECT

                      count(cn.*)

                FROM

                    ds_combname cn,

                    ds_comb c,

                    ds_combcomplex cc,

                    ds_ring r

                WHERE

                     cn.combname = '".pg_escape_string($this->incoming_info['new_combname'])."'

                     AND cn.id_comb = c.id_comb

                     AND c.id_combcomplex = cc.id_combcomplex

                     AND cc.id_ring = r.id_ring

                     AND r.id_world =".$this->id_world;

        

        $res = $this->common->getResultArray("",$sql,"","","NO_CACHE"); 

        if(!$res)

        {

            $this->error_status = 5;

            return false;

        }

        elseif ((int)$res[0]['count'] > 0)

        {

            $this->addToResult('checking_result','combname_exists');

            return false;

        }

        

        return true;        

    }

    

    private function checkIfUserOwnerOfComb()

    {

        if(!is_numeric($this->incoming_info['id_user']) ||

            (int)$this->incoming_info['id_user'] <= 0 ||

            !preg_match("/^[0-9]{1,4}\.[0-9]{1,4}\.[0-9]{1,4}$/", $this->incoming_info['comb_coords']))

            {

                $this->error_status = 7;

                return false;

            }

            

        if(!$this->connectToNeededDB())

            return false;

            

        list($ring, $complexnumber, $combnumber) = split('\.', $this->incoming_info['comb_coords']);

            

        $sql = "SELECT

                      count(*)

                FROM

                    ds_user u,

                    ds_userinstance ui,

                    ds_comb c,

                    ds_combcomplex cc,

                    ds_ring r

                WHERE

                     u.id_user = ".$this->incoming_info['id_user']."

                     AND u.id_user = ui.id_user

                     AND ui.id_userinstance = c.id_userinstance

                     AND c.combnumber = ".$combnumber."

                     AND c.id_combcomplex = cc.id_combcomplex

                     AND cc.complexnumber = ".$complexnumber."

                     AND cc.id_ring = r.id_ring

                     AND r.ringnumber = ".$ring."

                     AND r.id_world =".$this->id_world;

        

        $res = $this->common->getResultArray("",$sql,"","","NO_CACHE"); 

        if(!$res)

        {

            $this->error_status = 10;

            return false;

        }

        elseif ((int)$res[0]['count'] != 1)

        {

            $this->addToResult('checking_result','comb_not_yours');

            return false;

        }

        

        return true;        

    }

}

?>