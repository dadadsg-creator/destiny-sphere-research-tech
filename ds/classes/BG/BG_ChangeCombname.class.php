<?php $CVS_Version = '$Revision$ ; $Date$';

/**

 * Класс гейта для смены названия соты 

 *

 */

class BG_ChangeCombname extends BG_Common 

{

    public function __construct($data)

    {

        parent::__construct($data);     

    }

    

    /**

     * Получаем uid пользователя по логину

     *

     */

    public function proceed()

    {       
        
        if($this->error_status != 0)
        {
            return;
        }

        if(!array_key_exists('newcombname',$this->incoming_info) ||

            !array_key_exists('worldname',$this->incoming_info) ||

            !array_key_exists('coords',$this->incoming_info) ) 

        {

            $this->error_status = 1;

            $this->outputAnswer(); 
            return;

        }

                

        if($this->updateCombName())

            $this->checkIfUpdated();

            

        $this->outputAnswer(); 

    }

    

    private function updateCombName()

    {
        
        if($this->error_status != 0)
        {
            return;
        }

        if(!$this->connectToNeededDB())

            return false;

            

        if(strlen($this->incoming_info['newcombname']) < 3 ||

            strlen($this->incoming_info['newcombname']) > 50 ||

            !preg_match ('/^[a-zA-Z0-9_]+$/', $this->incoming_info['newcombname']) ||

            !preg_match("/^[0-9]{1,4}\.[0-9]{1,4}\.[0-9]{1,4}$/", $this->incoming_info['coords']) ||

            empty($this->id_world))

        {

            $this->error_status = 2; 

            return false;

        }   

            

        list($ring, $complexnumber, $combnumber) = split('\.', $this->incoming_info['coords']); 



        $sql = "UPDATE

                      ds_combname

                SET

                   combname = '".pg_escape_string($this->incoming_info['newcombname'])."'

                WHERE

                     id_comb =

                (

                SELECT

                      c.id_comb

                FROM

                    ds_comb c,

                    ds_combcomplex cc,

                    ds_ring r

                WHERE

                    c.combnumber = ".$combnumber."

                    AND c.id_combcomplex = cc.id_combcomplex

                    AND cc.complexnumber = ".$complexnumber."

                    AND cc.id_ring = r.id_ring

                    AND r.ringnumber = ".$ring."

                    AND r.id_world =".$this->id_world."

                )";

        

        $res = $this->common->executeQuery ($sql);

        

        if(!empty($this->common->errormsg))

        {

            $this->error_status = 3;

            return false;

        }

        

        return true;

    }

    

    private function checkIfUpdated()

    {

        if(!$this->connectToNeededDB())

            return false;

            

        list($ring, $complexnumber, $combnumber) = split('\.', $this->incoming_info['coords']); 

        

        $sql = "SELECT

                        count(*) as count

                    FROM

                        ds_comb c,

                        ds_combcomplex cc,

                        ds_ring r,

                        ds_combname cn

                    WHERE

                        c.combnumber = ".$combnumber."

                        AND c.id_combcomplex = cc.id_combcomplex

                        AND cc.complexnumber = ".$complexnumber."

                        AND cc.id_ring = r.id_ring

                        AND r.ringnumber = ".$ring."

                        AND r.id_world =".$this->id_world."

                        AND c.id_comb = cn.id_comb

                        AND cn.combname = '".pg_escape_string($this->incoming_info['newcombname'])."'
                        
                        AND c.is_capture = 'f'";

        

        $res = $this->common->getResultArray("",$sql,"","","NO_CACHE"); 

        if(!empty($this->common->errormsg) || !$res)

        {

            $this->error_status = 4;

            return false;

        }

        

        $this->addToResult('change_success',$res[0]['count']);  

        

        return true;

    }

}

?>