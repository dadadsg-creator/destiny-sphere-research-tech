<?

 /**

   Класс для обеспечения подгрузки необходимых файлов

 */

 class IncludeManager {

     /** Корневая директория проекта */
     var $root;

     /** Массив с путями для подключения различных видов файлов */
     var $inc_dirs = array (

       "BaseClass" => "sys/include/classes/BaseClasses/",

	   "ListClass" => "sys/include/classes/ListClasses/",

       "ListsTechOverClasses" => "sys/include/classes/ListClasses/TechOver/",

       "ProductClass" => "sys/include/classes/ProductClasses/",

       "PublicationClass" => "sys/include/classes/PublicationClasses/",

       "Include" => "sys/include/include/",

	   "Сonfig" => "sys/include/configs/",

       "TechOver" => "sys/include/classes/TechOverClasses/",

       "TechOverCrt" => "sys/include/classes/TechOverClasses/crt/",

       "TechOverLcd" => "sys/include/classes/TechOverClasses/lcd/",

       "Subscribe" => "sys/include/classes/SubscribeClasses/",
	   
	   "PartnerListsClasses" => "sys/include/classes/ListClasses/Partners/",
	);
	
    var $error;



    /** Массив с описанием файлов для подключения */

    var $files = array (

       "base" => array (

                         array("name"=>"config.cfg.php", "type"=>"Сonfig"),

                         array("name"=>"Logger.class.php", "type"=>"BaseClass"),

                         array("name"=>"Error.class.php", "type"=>"BaseClass"),

                         array("name"=>"Object.class.php", "type"=>"BaseClass"),

                         array("name"=>"MYSQLInterface.class.php", "type"=>"BaseClass"),

                         array("name"=>"DBInterface.class.php", "type"=>"BaseClass"),

                         array("name"=>"Utils.class.php", "type"=>"BaseClass"),

                         array("name"=>"FastTemplate.class.php", "type"=>"BaseClass"),

                         array("name"=>"Parameters.class.php", "type"=>"BaseClass"),

                         array("name"=>"FileManager.class.php", "type"=>"BaseClass"),

                         array("name"=>"main.inc.php", "type"=>"Include"),

                       ),



       "techOver" => array (

						 array("name"=>"ProductAdmin.class.php", "type"=>"TechOver"),

                         array("name"=>"ProductManager.class.php", "type"=>"TechOver"),

                         array("name"=>"ProductSearchPre.class.php", "type"=>"TechOver"),

                         array("name"=>"ProductPre.class.php", "type"=>"TechOver"),

                       ),



    	"techOverOut" => array (

        					array("name"=>"ProductOverOut.class.php", "type"=>"TechOver"),

        				),



        "techOverCrt" => array (

						 array("name"=>"Product.class.php", "type"=>"TechOverCrt"),

                         array("name"=>"ProductSearch.class.php", "type"=>"TechOverCrt"),

                       ),



        "techOverLcd" => array (

						 array("name"=>"Product.class.php", "type"=>"TechOverLcd"),

                         array("name"=>"ProductSearch.class.php", "type"=>"TechOverLcd"),

                       ),



		"lists" => array (

						 array("name"=>"ListClass.class.php", "type"=>"ListClass"),

						 array("name"=>"ListNum.class.php", "type"=>"ListClass"),

						 array("name"=>"ListArray.class.php", "type"=>"ListClass"),

                         array("name"=>"ListText.class.php", "type"=>"ListClass"),

						 array("name"=>"ListDate.class.php", "type"=>"ListClass"),

                         array("name"=>"ListVendors.class.php", "type"=>"ListClass"),

                         array("name"=>"ListAdmin.class.php", "type"=>"ListClass"),

                         array("name"=>"ListProductTypes.class.php", "type"=>"ListClass"),

                         array("name"=>"ListProductStatusTypes.class.php", "type"=>"ListClass"),

                         array("name"=>"ListTradeMarks.class.php", "type"=>"ListClass"),

                         array("name"=>"ListAllAdmin.class.php", "type"=>"ListClass"),

                         array("name"=>"FileWork.class.php", "type"=>"ListClass"),

                         array("name"=>"ListMultyText.class.php", "type"=>"ListClass"),

                         array("name"=>"ListMultyValueText.class.php", "type"=>"ListClass"),
						 
						 array("name"=>"ListProductNews.class.php", "type"=>"ListClass"),

                       ),



        "listsTechOver" => array (

						 array("name"=>"ListTubeTechnology.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListProduct.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListTubeVendor.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListAntiFlashCover.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListPlugAndPlay.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListMultyUserControl.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListMultyPowerManagment.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListMultySerificate.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListMultyErgonomics.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListPanelTechology.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListMatrixVendor.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListMatrixType.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListMaxResolution.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListRecomendResolution.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListSyncSignalType.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListPowerType.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListBodyColor.class.php", "type"=>"ListsTechOverClasses"),

                         array("name"=>"ListMultyValueResolutionWithFreq.class.php", "type"=>"ListsTechOverClasses"),
                       ),



        "products" => array (

						 array("name"=>"Product.class.php", "type"=>"ProductClass"),

                         array("name"=>"ProductSearch.class.php", "type"=>"ProductClass"),

                         array("name"=>"ProductManager.class.php", "type"=>"ProductClass"),

                         array("name"=>"ProductAdmin.class.php", "type"=>"ProductClass"),

                       ),



       "publications" => array (

						 array("name"=>"Publication.class.php", "type"=>"PublicationClass"),

                         array("name"=>"PubSearch.class.php", "type"=>"PublicationClass"),

                         array("name"=>"PubManager.class.php", "type"=>"PublicationClass"),

                         array("name"=>"PubWork.class.php", "type"=>"PublicationClass")

                       ),

        "subscribe" => array (

						 array("name"=>"SubscribeWork.class.php", "type"=>"Subscribe"),
                         array("name"=>"SendMessage.class.php", "type"=>"Subscribe"),
                         array("name"=>"DirectMailSend.class.php", "type"=>"Subscribe"),
                         array("name"=>"DirectMailGrab.class.php", "type"=>"Subscribe")

                       ),
		"PartnerLists" => array (
						 array("name"=>"ListPartnerSex.class.php", "type"=>"PartnerListsClasses"),
						 array("name"=>"ListMailFormat.class.php", "type"=>"PartnerListsClasses"),
						 array("name"=>"ListMultyPartnerDirections.class.php", "type"=>"PartnerListsClasses"),
						 array("name"=>"ListPartnerPost.class.php", "type"=>"PartnerListsClasses"),
						 array("name"=>"ListPartnerPreferableName.class.php", "type"=>"PartnerListsClasses"),
						 array("name"=>"ListMultyPartnerResponsibility.class.php", "type"=>"PartnerListsClasses"),
						 array("name"=>"ListMultyPartnerSphears.class.php", "type"=>"PartnerListsClasses"),
						 array("name"=>"ListSubscribe.class.php", "type"=>"PartnerListsClasses"),
						 
		),
		
		

     );



     /**

       Конструктор.

         Параметры:

         $root - корневой католог.

     */

     function IncludeManager ($root = false) {

		if(!$root){

       		$root = $_SERVER['DOCUMENT_ROOT']."/";

        }

       $this->root = $root;

       $this->inc("base");

     }



     /**

       Метод для подключения группы файлов.

         Параметры:

         $files - название группы файлов

     */

     function inc ($files) {

		if(!is_array($this->files[$files])){

        	die("IncludeManager->inc:ERROR: Undefined file group name - ".$files);

        }

       foreach ($this->files[$files] as $v){

		$dir = $this->inc_dirs[$v['type']];

		if(ord($dir[0]) == 47){
			$root = constant('MAIN_SERVER_PATH');
		}else{
			$root = $this->root;
		}

	   	 if(file_exists($root.$dir.$v['name'])){

		 	require_once ($root.$dir.$v['name']);

		 }else{

		 	die("IncludeManager->inc:ERROR: Cant find file - ".$root.$dir.$v['name']);

		 }

       }//END OF foreach()

     }



   };//END OF CLASS IncludeManager

?>