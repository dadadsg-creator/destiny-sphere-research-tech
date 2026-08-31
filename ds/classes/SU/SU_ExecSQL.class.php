<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_ExecSQL 
{   
    private $dbWorldAccess;
    
    private $conn;

    private $dbId;
    
    private $getMulty;
    
    private $username;
    
    private $password;
    
    private $query;
    
    private $getResult;
    
    private $out;
    
    private $errormsg;

    function __construct()
    {
        
        $this->dbWorldAccess = $GLOBALS['dbWorldAccess'];
        if($_POST['submit']=='Exec')
        {           
            if ( $this->getPostData ( $_POST ) ) {
                
                if( !$this->proceed() )
                {
                    
                    echo $this->errormsg."\n<br>";
                }

                echo "<table cellSpacing='0' cellPadding='5'>".$this->out."</table>";

            }else{
                echo $this->errormsg;
            }           
        }
        else{           
            $this->showFirstMenu();
        }
    }
    
    
    function showFirstMenu ( $str = "" ) {
        
        $ifSelected='';
        
        print "
            
        <table>
        ".$str."
        <form action='".$_SERVER["PHP_SELF"]."' method='POST'>
        
        <table cellSpacing='0' cellPadding='5' width=100%>
            <tr>
                <td><textarea name='query' style='width:90%;height:400px;' >".@$_SESSION[ __CLASS__.'_SQL']."</textarea></td>
                <td colspan = 2 ></tr><tr><td>
                    <table cellSpacing='0' cellPadding='5'>
                        <tr>
                            <td colspan =2 >
                            <div>";
        
        echo "<strong>Servers:</strong><br> <select name='dbId[]' size='10' multiple>";
        foreach($this->dbWorldAccess as $dbId => $db)
        {
            if($dbId== $_SESSION['dbId'][0])
            {
                $ifSelected='selected';
            }
            else 
            {
                $ifSelected=''; 
            }
            echo "<option value='".$dbId."' ".$ifSelected.">".$db['server']."</option>";
        }
    
        echo "
                                </select><br>
                                </div>
                            </td>
                            <td>
                                <table cellSpacing='0' cellPadding='5'>
                                    <tr>
                                        <td colspan = 2>UserName: </td>
                                        <td colspan = 2><input type='text' name='exec_username' value='".@$_SESSION['userName']."'></td>
                                    </tr>
                                    <tr>
                                        <td colspan = 2>Password: </td>
                                        <td colspan = 2><input type='password' name='exec_password' value='".@$_SESSION['password']."'></td>
                                    </tr>
                                    <tr>
                                        <td colspan = 2>ReceiveResult: </td>
                                        <td colspan = 2><input type='checkbox' name='getresult' value=1 class=check></td>
                                    </tr>
                                    <tr>
                                        <td colspan = 2></td>
                                        <td colspan = 2><input type='submit' value='Exec' name='submit'></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        </form>
        ";
    
        return true;
    }
    

    function getPostData($data) 
    {
                
        if(isset($data['dbId']))
        {           
            $this->dbId = $data['dbId'];
            $this->getMulty = is_array($this->dbId);
        }else{
            $this->errormsg = 'SELECT DB';
            return false;
        }
        
        if(isset($data['query']))
        {
            $this->query = $data['query'];
        }else{
            $this->errormsg = 'Enter Query';
            return false;
        }
        
        if(isset($data['exec_username']))
        {
            $this->username = $data['exec_username'];
        }else{
            $this->errormsg = 'Enter UserName';
            return false;
        }
        
        if( isset( $data['exec_password'] ) ){
            $this->password = $data['exec_password'];
        }else{
            $this->errormsg = 'Enter Password';
            return false;
        }
        
        $this->getResult = isset( $data['getresult'] );
        
        return true;
    }
    
    
    function proceed( ){
        
        echo "<a href=".$_SERVER['PHP_SELF'].">BACK</a><br><br>";
        
        $_SESSION['userName']=$this->username;
        $_SESSION['password']=$this->password;
        $_SESSION['dbId']=$this->dbId;
        
        echo "SQL:<hr><xmp>";
        
        echo $this->query;
        
        echo "</xmp><hr>";
        
        $_SESSION[ __CLASS__.'_SQL'] = $this->query;
        
        
        
        if( $this->getMulty )
        {               
            for($i=0; $i < count($this->dbId); $i++)
            {               
                $this->proceedSingl($this->dbId[$i]);
            }           
        }else{          
            $this->proceedSingl( $this->dbId );
        }
        
        
        
        return true;
    }
    
    function proceedSingl($dbId)
    {       
        if(!$this->connectSingl($dbId))
        {
            return;
        }
        $dbname = $this->dbWorldAccess[$dbId]['dbname'];
        $out.=" username = ".$this->username."\n database = ".$dbname."\n query = ".$this->query;
        $this->logging($out);
                                        
        echo "============================ <strong> Server: ".$this->dbWorldAccess[ $dbId ]['server']."</strong> ============================= <br>";       
        flush();
        
        SU_DebugTools::TimerStart(__CLASS__);
        $res = @pg_query($this->conn, $this->query);
        $timeDelta = SU_DebugTools::TimerStop(__CLASS__);
        
        if (!$res) 
        {
            echo pg_last_error($this->conn)."<br>";
            flush();
            return;
        }
        
        
        if($this->getResult)
        {
            $rows = pg_num_rows($res);
        }else{
            $rows = pg_affected_rows($res);
        }
        
        echo "<br>Exec: OK! Afected rows: <b>$rows</b> Time: $timeDelta<br><br>";
        flush();
                
        if($this->getResult)
        {           
            $this->formatResult(pg_fetch_all($res));            
            
        }
                        
        return;
    }
    
    function formatResult($array = '')
    {
        
        $out = "<table cellSpacing='0' cellPadding='5' border=1><tr>";
        
        foreach ( $array[0] as $key => $val ){
            
            $out .= "<th>".$key."</th>";
        }
        
        foreach ( $array as $row )
        {           
            $out .= "<tr>";         
            foreach ( $row as $col )
            {
                
                $out .= "<td >".$col."</td>";
            }           
            $out .= "</tr>";            
        }
        
        $out .= "</tr></table><br>";
        
        echo $out;
        flush();
    }
    
    
        
    function connectSingl( $dbId ){
        
        $host = $this->dbWorldAccess[$dbId]['host'];
        $dbname = $this->dbWorldAccess[$dbId]['dbname'];
        $user = $this->username;
        $password = $this->password;

        $conn_string = "host=".$host." dbname=".$dbname." user=".$user." password=".$password;

        $this->conn = pg_connect ($conn_string);

        if(!$this->conn) 
        {
            echo "CONNECT ERROR [".$this->dbWorldAccess[ $dbId ]['server']."]: ".pg_last_error($this->conn)."<br>";
            return false;
        }
        pg_query($this->conn, "SET statement_timeout TO 100000000");
        
        return true;
    }
    
    function logging($out)
    {
        
        SU_DebugTools::LogAll($out, 'LogSQL.log');
    }
    
}


?>