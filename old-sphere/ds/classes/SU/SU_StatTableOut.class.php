<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_StatTableOut
{
    private function __construct()
    {
    }
    
    public static function getFormattedArray(&$arrayToOutput)
    {
        assert(is_array($arrayToOutput));
        $transformedArray = self::_transformArray($arrayToOutput);
        $serverNames = self::_getServerNames($arrayToOutput);
        $html = self::_arrayToHtmlTable($transformedArray, $serverNames);
        
        return $html;
    }
    
    private static function _arrayToHtmlTable(&$array, &$serverNames)
    {
        assert(is_array($array));
        
        if (count($array) < 1) {
            return '<table border="1"><tr><td>&nbsp;</td></tr></table>';
        }

        $out = '';
        $out = "\n<table cellspacing=\"0\" cellpadding=\"3\" border=\"1\">\n";
        $out .= '<tr><th>&nbsp;</th>';
        
        ksort($serverNames);
        
        foreach ($serverNames as $serverName) {
            $out .= '<th>' . $serverName . '</th>';
        }
        
        $out .= "</tr>\n";
        
        //ksort($array);

        foreach ($array as $groupName => $groupArray) {
            if (is_array($groupArray)) {
                $out .= "<tr><th>" . $groupName . "</th><th colspan=\"" . count($serverNames) . "\">&nbsp;</th></tr>\n";

                //ksort($groupArray);

                foreach ($groupArray as $paramName => $paramArray) {
                    $out .= "<tr><td>" . $paramName . "</td>";

                    if (is_array($paramArray)) {
                        //ksort($paramArray);

                        foreach ($paramArray as $serverName => $paramValue) {
                            $out .= "<td>" . $paramValue . "</td>\n";
                        }
                    }
                    
                    $out .= "</tr>\n";
                }
            }
        }
        
        $out .= "</table>\n";
        
        return $out;    
    }
    
    private static function _transformArray(&$arrayToTransform)
    {
        $transformedArray = array();
        $serverNames = array();
        $groupNames = array();
        $paramNames = array();

        foreach ($arrayToTransform as $serverName => $serverArray) {
            $serverNames[] = $serverName;

            if (is_array($serverArray)) {
                foreach ($serverArray as $groupName => $groupArray) {
                    if (is_array($groupArray)) {
                        if (!key_exists($groupName, $transformedArray)) {
                            $transformedArray[$groupName] = array();
                        }
                        
                        if (!key_exists($groupName, $groupNames)) {
                            $groupNames[] = $groupName;
                        }

                        foreach ($groupArray as $paramName => $paramValue) {
                            if (!key_exists($paramName, $transformedArray[$groupName])) {
                                $transformedArray[$groupName][$paramName] = array();
                            }
                            
                            if (!key_exists($paramName, $paramNames)) {
                                $paramNames[$groupName][] = $paramName;
                            }
                            
                            $transformedArray[$groupName][$paramName][$serverName] = $paramValue;
                        }
                    }
                }
            }
        }

        // verify if array is "full" parallelogram
        foreach ($transformedArray as $groupName => $groupArray) 
        {
            foreach ($groupArray as $paramName => $paramArray) 
            {
                foreach ($serverNames as $serverName) 
                {
                    if (!key_exists($serverName, $paramArray)) 
                    {
                        $transformedArray[$groupName][$paramName][$serverName] = '—';
                    }
                    
                    if($transformedArray[$groupName][$paramName][$serverName] == '')
                    {
                    	$transformedArray[$groupName][$paramName][$serverName] = '—';
                    }
                }
            }
        }

        return $transformedArray;
    }
    
    private static function _getServerNames(&$arrayToTransform)
    {
        assert(is_array($arrayToTransform));
        $serverNames = array();
        
        foreach ($arrayToTransform as $serverName => $serverArray) {
            $serverNames[] = $serverName;
        }
        
        return $serverNames;
    }
}

?>