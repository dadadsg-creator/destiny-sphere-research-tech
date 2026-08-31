<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_FlashVersionControlInUseraction
{
    
    static private $versionsForControl = array
    (
        'ds3.swf' => array('FROM_BEGIN' => 10, 'FROM_END' => null), 
        'menu.lib.swf' =>  array('FROM_BEGIN' => null, 'FROM_END' => 10)
    );
    
    static public function StoreFilesVersionInSession($versions)
    {
        assert(is_array($versions) && (count($versions) >= count(self::$versionsForControl)));
        
        foreach($versions AS $fileName =>$fileData)
        {
            if(!key_exists($fileName, self::$versionsForControl))
            {
                continue;
            }
            
            assert(key_exists('version', $fileData));
            $version = (int)$fileData['version'];
            assert($version > 0);           
            
            $_SESSION['FLASH_VERSIONS'][$fileName] = $version;          
        }       

    }
    
    static public function ControlVersions($request)
    {
        return;
        assert(is_string($request) && !empty($request));
        
        if(RD_AdeptUserActionToConsole::$commandLineFlag)
        {
            return;
        }
        
        $fromSession = self::_importFilesVersionFromSession();
        $fromRequest = self::_importFilesVersionFromRequest($request);
        
        if($fromSession != $fromRequest)
        {
            ds_error('Wrong client version!');
        }
    }

    static private function _importFilesVersionFromSession()
    {
        assert(key_exists('FLASH_VERSIONS', $_SESSION) && is_array($_SESSION['FLASH_VERSIONS']));
        
        return $_SESSION['FLASH_VERSIONS'];
    }
    
    static private function _importFilesVersionFromRequest($request)
    {
        assert(is_string($request) && !empty($request));
        
        $requestData = explode(',', $request);
        assert(count($requestData) > 0);
        
        $out = array();
        
        foreach(self::$versionsForControl AS $fileName => $fileData)
        {
            assert(key_exists('FROM_BEGIN', $fileData));
            $fromBegin = $fileData['FROM_BEGIN'];
            
            assert(key_exists('FROM_END', $fileData));
            $fromEnd = $fileData['FROM_END'];
            
            assert(is_null($fromBegin) || is_null($fromEnd));
            
            if(!is_null($fromBegin))
            {
                $key = $fromBegin - 1;
                
            }elseif(!is_null($fromEnd)){
                $key = count($requestData) - $fromEnd;              
            }
            
            assert(key_exists($key, $requestData));
            $out[$fileName] = $requestData[$key];
        }
        
        return $out;
    }
    
    
}

?>