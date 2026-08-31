<?php $CVS_Version = '$Revision$ ; $Date$';

/***************************************************************/
/* PhpCaptcha												  */
/* Copyright © 2005 Edward Eliot - http://www.ejeliot.com/	  */
/* This class is Freeware, however please retain this		  */
/* copyright notice when using								  */
/* Last Updated:	 17th December 2005							  */
/* Disclaimer: The author accepts no responsibility for		  */
/* problems arising from the use of this class. The CAPTCHA	  */
/* generated is not guaranteed to be unbreakable				  */
/***************************************************************/
//session_start();

SU_GameSessionWork::SessionContinue();

// class defaults - change to effect globally

define('CAPTCHA_WIDTH', 110); // max 500
define('CAPTCHA_HEIGHT', 30); // max 200
define('CAPTCHA_NUM_CHARS', 4);
define('CAPTCHA_NUM_LINES', 70);
define('CAPTCHA_CHAR_SHADOW', false);
define('CAPTCHA_OWNER_TEXT', '');
define('CAPTCHA_BACKGROUND_IMAGE', '');
define('CAPTCHA_MIN_FONT_SIZE', 15);
define('CAPTCHA_MAX_FONT_SIZE', 21);
define('CAPTCHA_USE_COLOUR', true);
define('CAPTCHA_FILE_TYPE', 'jpeg');

class GE_ImageProtection
{
	var $oImage;
	var $aFonts;
	var $iWidth;
	var $iHeight;
	var $iNumChars;
	var $iNumLines;
	var $iSpacing;
	var $bCharShadow;
	var $sOwnerText;
	var $aCharSet;
	var $sBackgroundImage;
	var $iMinFontSize;
	var $iMaxFontSize;
	var $bUseColour;
	var $sFileType;
	var $sCode;

	function GE_ImageProtection(
	$aFonts, // array of TrueType fonts to use - specify full path
	$iWidth = CAPTCHA_WIDTH, // width of image
	$iHeight = CAPTCHA_HEIGHT, // height of image
	$iNumChars = CAPTCHA_NUM_CHARS, // number of characters to draw
	$iNumLines = CAPTCHA_NUM_LINES, // number of noise lines to draw
	$bCharShadow = CAPTCHA_CHAR_SHADOW, // add shadow to generated characters to further obscure code
	$sOwnerText = CAPTCHA_OWNER_TEXT, // add owner text to bottom of CAPTCHA, usually your site address
	$aCharSet = array(), // array of characters to select from - if blank uses upper case A - Z
	$sBackgroundImage = CAPTCHA_BACKGROUND_IMAGE, // background image to use - if blank creates image with white background
	$iMinFontSize = CAPTCHA_MIN_FONT_SIZE, // set the minimum font size that can be selected
	$iMaxFontSize = CAPTCHA_MAX_FONT_SIZE, // set the maximum font size that can be used
	$bUseColour = CAPTCHA_USE_COLOUR, // determines whether or not to use colour to draw lines and characters
	$sFileType = CAPTCHA_FILE_TYPE // set the output file type
	)
	{
		// get parameters
		$this->aFonts = $aFonts;
		$this->SetNumChars($iNumChars);
		$this->SetNumLines($iNumLines);
		$this->DisplayShadow($bCharShadow);
		$this->SetOwnerText($sOwnerText);
		$this->SetCharSet($aCharSet);
		$this->SetBackgroundImage($sBackgroundImage);
		$this->SetMinFontSize($iMinFontSize);
		$this->SetMaxFontSize($iMaxFontSize);
		$this->UseColour($bUseColour);
		$this->SetFileType($sFileType);
		$this->SetWidth($iWidth);
		$this->SetHeight($iHeight);

		// calculate spacing between characters based on width of image
		$this->CalculateSpacing();
	}

	function CalculateSpacing()
	{
		$this->iSpacing = (int)($this->iWidth / $this->iNumChars);
	}

	function SetWidth($iWidth)
	{
		$this->iWidth = $iWidth;
		if($this->iWidth > 500)
		{
			$this->iWidth = 500; // to prevent perfomance impact
		}
		$this->CalculateSpacing();
	}

	function SetHeight($iHeight)
	{
		$this->iHeight = $iHeight;
		if($this->iHeight > 200)
		{
			$this->iWidth = 200; // to prevent performance impact
		}
	}

	function SetNumChars($iNumChars)
	{
		$this->iNumChars = $iNumChars;
		$this->CalculateSpacing();
	}

	function SetNumLines($iNumLines)
	{
		$this->iNumLines = $iNumLines;
	}

	function DisplayShadow($bCharShadow)
	{
		$this->bCharShadow = $bCharShadow;
	}

	function SetOwnerText($sOwnerText)
	{
		$this->sOwnerText = $sOwnerText;
	}

	function SetCharSet($aCharSet)
	{
		$this->aCharSet = $aCharSet;
	}

	function SetBackgroundImage($sBackgroundImage)
	{
		$this->sBackgroundImage = $sBackgroundImage;
	}

	function SetMinFontSize($iMinFontSize)
	{
		$this->iMinFontSize = $iMinFontSize;
	}

	function SetMaxFontSize($iMaxFontSize)
	{
		$this->iMaxFontSize = $iMaxFontSize;
	}

	function UseColour($bUseColour)
	{
		$this->bUseColour = $bUseColour;
	}

	function SetFileType($sFileType)
	{
		// check for valid file type
		if(in_array($sFileType, array('gif', 'png', 'jpeg'))) {
			$this->sFileType = $sFileType;
		} else {
			$this->sFileType = 'jpeg';
		}
	}

	function DrawLines()
	{
		for($i = 0; $i < $this->iNumLines; $i++)
		{
			// allocate colour
			if($this->bUseColour && false)
			{
				$iLineColour = imagecolorallocate($this->oImage, rand(100, 250), rand(100, 250), rand(100, 250));
			} else {
				$iRandColour = rand(100, 250);
				$iLineColour = imagecolorallocate($this->oImage, $iRandColour, $iRandColour, $iRandColour);
			}

			// draw line
			imageline($this->oImage, rand(0, $this->iWidth), rand(0, $this->iHeight), rand(0, $this->iWidth), rand(0, $this->iHeight), $iLineColour);
		}
	}

	function DrawOwnerText()
	{
		// allocate owner text colour
		$iBlack = imagecolorallocate($this->oImage, 0, 0, 0);
		// get height of selected font
		$iOwnerTextHeight = imagefontheight(2);
		// calculate overall height
		$iLineHeight = $this->iHeight - $iOwnerTextHeight - 4;

		// draw line above text to separate from CAPTCHA
		imageline($this->oImage, 0, $iLineHeight, $this->iWidth, $iLineHeight, $iBlack);

		// write owner text
		imagestring($this->oImage, 2, 3, $this->iHeight - $iOwnerTextHeight - 3, $this->sOwnerText, $iBlack);

		// reduce available height for drawing CAPTCHA
		$this->iHeight = $this->iHeight - $iOwnerTextHeight - 5;
	}

	function GenerateCode()
	{
		// reset code
		$this->sCode = '';

		// loop through and generate the code letter by letter
		for($i = 0; $i < $this->iNumChars; $i++)
		{
			if(count($this->aCharSet) > 0)
			{
				// select random character and add to code string
				$this->sCode .= $this->aCharSet[array_rand($this->aCharSet)];
			} else {
				// select random character and add to code string
				$this->sCode .= chr(rand(65, 90));
			}
		}

		// save code in session variable
		$_SESSION['php_captcha'] = md5(strtoupper($this->sCode));
	}

	function DrawCharacters()
	{
		// loop through and write out selected number of characters
		for($i = 0; $i < strlen($this->sCode); $i++)
		{
			// select random font
			$sCurrentFont = $this->aFonts[array_rand($this->aFonts)];

			// select random colour
			if($this->bUseColour)
			{
				$iTextColour = imagecolorallocate($this->oImage, rand(0, 100), rand(0, 100), rand(0, 100));

				if($this->bCharShadow)
				{
					// shadow colour
					$iShadowColour = imagecolorallocate($this->oImage, rand(0, 100), rand(0, 100), rand(0, 100));
				}
			} else {
				$iRandColour = rand(0, 100);
				$iTextColour = imagecolorallocate($this->oImage, $iRandColour, $iRandColour, $iRandColour);

				if($this->bCharShadow)
				{
					// shadow colour
					$iRandColour = rand(0, 100);
					$iShadowColour = imagecolorallocate($this->oImage, $iRandColour, $iRandColour, $iRandColour);
				}
			}

			// select random font size
			$iFontSize = rand($this->iMinFontSize, $this->iMaxFontSize);

			// select random angle
			$iAngle = rand(-30, 30);

			// get dimensions of character in selected font and text size
			$aCharDetails = imageftbbox($iFontSize, $iAngle, $sCurrentFont, $this->sCode[$i], array());

			// calculate character starting coordinates
			$iX = $this->iSpacing / 4 + $i * $this->iSpacing;
			$iCharHeight = $aCharDetails[2] - $aCharDetails[5];
			$iY = $this->iHeight / 2 + $iCharHeight / 4;

			// write text to image
			imagefttext($this->oImage, $iFontSize, $iAngle, $iX, $iY, $iTextColour, $sCurrentFont, $this->sCode[$i], array());

			if ($this->bCharShadow)
			{
				$iOffsetAngle = rand(-30, 30);

				$iRandOffsetX = rand(-5, 5);
				$iRandOffsetY = rand(-5, 5);

				imagefttext($this->oImage, $iFontSize, $iOffsetAngle, $iX + $iRandOffsetX, $iY + $iRandOffsetY, $iShadowColour, $sCurrentFont, $this->sCode[$i], array());
			}
		}
	}

	function WriteFile($sFilename)
	{
		if ($sFilename == '')
		{
			// tell browser that data is jpeg
			header("Content-type: image/$this->sFileType");
			// Date in the past
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			// always modified
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			// HTTP/1.1
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			// HTTP/1.0
			header("Pragma: no-cache");
		}

		switch ($this->sFileType)
		{
			case 'gif':
			$sFilename != '' ? imagegif($this->oImage, $sFilename) : imagegif($this->oImage);
			break;
			case 'png':
			$sFilename != '' ? imagepng($this->oImage, $sFilename) : imagepng($this->oImage);
			break;
			default:
			$sFilename != '' ? imagejpeg($this->oImage, $sFilename) : imagejpeg($this->oImage);
		}
	}

	function Create($sFilename = '')
	{
		// check for required gd functions
		if(!function_exists('imagecreate') || !function_exists("image$this->sFileType") || ($this->sBackgroundImage != '' && !function_exists('imagecreatetruecolor')))
		{
			return false;
		}

		// get background image if specified and copy to CAPTCHA
		if($this->sBackgroundImage != '')
		{
			// create new image
			$this->oImage = imagecreatetruecolor($this->iWidth, $this->iHeight);

			// create background image
			$oBackgroundImage = imagecreatefromjpeg($this->sBackgroundImage);

			// copy background image
			imagecopy($this->oImage, $oBackgroundImage, 0, 0, 0, 0, $this->iWidth, $this->iHeight);

			// free memory used to create background image
			imagedestroy($oBackgroundImage);
		} else {
			// create new image
			$this->oImage = imagecreate($this->iWidth, $this->iHeight);
		}

		// allocate white background colour
		imagecolorallocate($this->oImage, 255, 255, 255);

		// check for owner text
		if($this->sOwnerText != '')
		{
			$this->DrawOwnerText();
		}

		// check for background image before drawing lines
		if($this->sBackgroundImage == '')
		{
			$this->DrawLines();
		}

		$this->GenerateCode();
		$this->DrawCharacters();

		// write out image to file or browser
		$this->WriteFile($sFilename);

		// free memory used in creating image
		imagedestroy($this->oImage);

		return true;
	}

	// call this method statically
	function Validate($sUserCode)
	{
		if(!isset($_SESSION) || !key_exists('php_captcha', $_SESSION) || empty($_SESSION['php_captcha']) || empty($sUserCode))
		{
			return false;
		}
		
		if(md5(strtoupper($sUserCode)) == $_SESSION['php_captcha'])
		{
			// clear to prevent re-use
			$_SESSION['php_captcha'] = '';
			return true;
		}

		return false;
	}
}

// example sub class
class GE_ImageProtectionColour extends GE_ImageProtection
{
	function GE_ImageProtectionColour($aFonts, $iWidth = CAPTCHA_WIDTH, $iHeight = CAPTCHA_HEIGHT)
	{
		// call parent constructor
		parent::GE_ImageProtection($aFonts, $iWidth, $iHeight);

		// set options
		$this->UseColour(true);
	}
}

?>