<?php $CVS_Version = '$Revision$ ; $Date$';

class GB_RandomPassword
{
    public static function Generate($lenght = false)
    {
        assert(is_int($lenght) || is_bool($lenght));
        
        $vals = "ABCDEFGHIJKLMNOPQRSTUVWXYZabchefghjkmnpqrstuvwxyz0123456789";
        
        if ($lenght)
        {
            $PasswordLength = $lenght;
        } else {
            $PasswordLength = 8;
        }

        $i = 0;
        $password = "";
        while (strlen($password) < $PasswordLength)
        {
            // Returns the maximum value that can be returned by a call  rand
            mt_getrandmax();
            $num = rand() % strlen($vals);
            $tmp = substr($vals, $num+4, 1);
            $password = $password . $tmp;
            $tmp ="";
        }
        return $password ;
    }
    
    function GenerateOnlyNumbers($lenght = false)
    {
        assert(is_int($lenght) || is_bool($lenght));
        
        $vals = "0123456789";
        
        if ($lenght)
        {
            $PasswordLength = $lenght;
        } else {
            $PasswordLength = 8;
        }

        $i = 0;
        $password = "";
        while (strlen($password) < $PasswordLength)
        {
            // Returns the maximum value that can be returned by a call  rand
            mt_getrandmax();
            $num = rand() % strlen($vals);
            $tmp = substr($vals, $num+4, 1);
            $password = $password . $tmp;
            $tmp ="";
        }
        return $password ;
    }
    
    function GenerateEmail($lenght = false)
        {
        assert(is_int($lenght) || is_bool($lenght));
        
        $vals = "ABCDEFGHIJKLMNOPQRSTUVWXYZabchefghjkmnpqrstuvwxyz0123456789";
        
        if ($lenght)
        {
            $randemailLength = $lenght;
        } else {
            $randemailLength = 8;
        }

        $i = 0;
        $randemail = "";
        while (strlen($randemail) < $randemailLength)
        {
            // Returns the maximum value that can be returned by a call  rand
            mt_getrandmax();
            $num = rand() % strlen($vals);
            $tmp = substr($vals, $num+4, 1);
            $randemail = $randemail . $tmp;
            $tmp ="";
        }
        $randemail .= '@mail.com';
        return $randemail ;
    }
}

?>
