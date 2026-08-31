<?
define('PLAYER_PATH', "http://".$_SERVER['HTTP_HOST']."/stream/video/player.swf");
define('MOVIE_PATH', $_SERVER['DOCUMENT_ROOT']."/images/movie/");
define('MOVIE_URL', "http://".$_SERVER['HTTP_HOST']."/images/movie/");


class PlayerLoader{
	
	private $quality = 'high';
	private $bgcolor = '#ffffff';
	private $movieName;
	private $width;
	private $height;
	private $output;
	
	public function __construct($movieName, $width = false, $height=false, $quality = false, $bgcolor = false){
		
		$this->movieName = $movieName;
		$width = intval($width);
		$height = intval($height);
		
		if($width > 1){
			$this->width = $width;
		}
		
		if($height > 1){
			$this->height = $height;
		}
		
		if($quality){			
			$this->quality = $quality;
		}
		
		if($bgcolor){
			$this->bgcolor = $bgcolor;
		}
		
		$this->_proceed();
	}
	
	public function getOutput(){
		return $this->output;
	}
	
	private function _proceed(){
		
		if( file_exists(constant('MOVIE_PATH').$this->movieName) ){
			
			$this->_getMetric();
			
		$this->output = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" 
		codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" 
		width="100%" height="100%" id="player.swf" align="middle">
		<param name="allowScriptAccess" value="sameDomain" />
		<param name="FlashVars" value="file='.constant('MOVIE_URL').$this->movieName.'">
		<param name="movie" value="'.constant('PLAYER_PATH').'" />
		<param name="quality" value="'.$this->quality.'" />
		<param name="bgcolor" value="'.$this->bgcolor.'" />
		<embed src="'.constant('PLAYER_PATH').'" 
			quality="'.$this->quality.'" 
			bgcolor="'.$this->bgcolor.'" 
			width="'.$this->width.'" 
			height="'.$this->height.'" 
			name="player.swf" 
			align="middle" 
			allowScriptAccess="sameDomain" 
			FlashVars="file='.constant('MOVIE_URL').$this->movieName.'" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
		</object>'; 
		
		} else {
			$this->output = "file not found";
		}
	}
	
	private function _getMetric(){
		
		if($this->width && $this->height)
			return ;
			
		$mov = new ffmpeg_movie(constant('MOVIE_PATH').$this->movieName);
		
		if(!$this->width){			
			$this->width = $mov->getFrameWidth();			
		}
		
		if(!$this->height){
			$this->height = $mov->getFrameHeight();	
		}
	}
}

?>