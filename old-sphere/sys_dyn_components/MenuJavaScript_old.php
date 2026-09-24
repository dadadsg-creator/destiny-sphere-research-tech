<script language="JavaScript">
<!--
function mmLoadMenus() {
  if (window.mm_menu_news) return;
   window.mm_menu_news = new Menu("root",110,18,"",11,"#EEEEEE","#ffffff","#243254","#505F84","left","middle",1,0,500,-5,7,true,true,true,0,true,true);
   mm_menu_news.addMenuItem("Последние новости","window.open('https://forum.destinysphere.ru/forum/portal.php', '_blank');");
   mm_menu_news.addMenuItem("Онлайн-газета","window.open('https://forum.destinysphere.ru/forum/news.php','_blank');");
   mm_menu_news.addMenuItem("Опросы","window.open('https://forum.destinysphere.ru/forum/viewforum.php?f=12','_blank');");
   mm_menu_news.hideOnMouseOut=true;
   mm_menu_news.bgColor='#243254';
   mm_menu_news.menuBorder=1;
   mm_menu_news.menuLiteBgColor='#243254';
   mm_menu_news.menuBorderBgColor='#040D23';

   window.mm_menu_index = new Menu("root",110,18,"",11,"#EEEEEE","#ffffff","#243254","#505F84","left","middle",1,0,500,-5,7,true,true,true,0,true,true);
   mm_menu_index.addMenuItem("Предыстория","location='index.php?history'");
  	mm_menu_index.addMenuItem("Скриншоты","location='index.php?screens'");
  	mm_menu_index.addMenuItem("Игровой мир","location='index.php?game_world'");
  	mm_menu_index.addMenuItem("Аватары и расы","location='index.php?races'");
  	mm_menu_index.addMenuItem("Статистика","location='index.php?statistics'");
  	mm_menu_index.addMenuItem("Страницы альянсов","location='index.php?alliances_pages'");
   mm_menu_index.hideOnMouseOut=true;
   mm_menu_index.bgColor='#243254';
   mm_menu_index.menuBorder=1;
   mm_menu_index.menuLiteBgColor='#243254';
   mm_menu_index.menuBorderBgColor='#040D23';

   window.mm_menu_build_psi = new Menu("Псолао",135,18,"",11,"#EEEEEE","#ffffff","#243254","#505F84","left","middle",1,0,500,0,7,true,true,true,0,true,true);
  	mm_menu_build_psi.addMenuItem("Производство ресурсов","location='support.php?builds_psi_res'");
  	mm_menu_build_psi.addMenuItem("Производство войск","location='support.php?builds_psi_military'");
  	mm_menu_build_psi.addMenuItem("Археология и наука","location='support.php?builds_psi_tech'");
  	mm_menu_build_psi.addMenuItem("Специальные здания","location='support.php?builds_psi_spec'");
  	mm_menu_build_psi.addMenuItem("Шпионаж","location='support.php?builds_psi_spy'");
  	mm_menu_build_psi.addMenuItem("Жилые здания","location='support.php?builds_psi_live'");
  	mm_menu_build_psi.addMenuItem("Тогровля","location='support.php?builds_psi_trade'");
  	mm_menu_build_psi.addMenuItem("Хранилища","location='support.php?builds_psi_depot'");
   mm_menu_build_psi.hideOnMouseOut=true;
   mm_menu_build_psi.bgColor='#243254';
   mm_menu_build_psi.menuBorder=1;
   mm_menu_build_psi.menuLiteBgColor='#243254';
   mm_menu_build_psi.menuBorderBgColor='#040D23';

   window.mm_menu_build_bio = new Menu("Лиенсу",135,18,"",11,"#EEEEEE","#ffffff","#243254","#505F84","left","middle",1,0,500,0,7,true,true,true,0,true,true);
  	mm_menu_build_bio.addMenuItem("Производство ресурсов","location='support.php?builds_bio_res'");
  	mm_menu_build_bio.addMenuItem("Производство войск","location='support.php?builds_bio_military'");
  	mm_menu_build_bio.addMenuItem("Археология и наука","location='support.php?builds_bio_tech'");
  	mm_menu_build_bio.addMenuItem("Специальные здания","location='support.php?builds_bio_spec'");
  	mm_menu_build_bio.addMenuItem("Шпионаж","location='support.php?builds_bio_spy'");
  	mm_menu_build_bio.addMenuItem("Жилые здания","location='support.php?builds_bio_live'");
  	mm_menu_build_bio.addMenuItem("Тогровля","location='support.php?builds_bio_trade'");
  	mm_menu_build_bio.addMenuItem("Хранилища","location='support.php?builds_bio_depot'");
   mm_menu_build_bio.hideOnMouseOut=true;
   mm_menu_build_bio.bgColor='#243254';
   mm_menu_build_bio.menuBorder=1;
   mm_menu_build_bio.menuLiteBgColor='#243254';
   mm_menu_build_bio.menuBorderBgColor='#040D23';

   window.mm_menu_build_tech = new Menu("Воранеры",135,18,"",11,"#EEEEEE","#ffffff","#243254","#505F84","left","middle",1,0,500,0,7,true,true,true,0,true,true);
  	mm_menu_build_tech.addMenuItem("Производство ресурсов","location='support.php?builds_tech_res'");
  	mm_menu_build_tech.addMenuItem("Производство войск","location='support.php?builds_tech_military'");
  	mm_menu_build_tech.addMenuItem("Археология и наука","location='support.php?builds_tech_tech'");
  	mm_menu_build_tech.addMenuItem("Специальные здания","location='support.php?builds_tech_spec'");
  	mm_menu_build_tech.addMenuItem("Шпионаж","location='support.php?builds_tech_spy'");
  	mm_menu_build_tech.addMenuItem("Жилые здания","location='support.php?builds_tech_live'");
  	mm_menu_build_tech.addMenuItem("Тогровля","location='support.php?builds_tech_trade'");
  	mm_menu_build_tech.addMenuItem("Хранилища","location='support.php?builds_tech_depot'");
   mm_menu_build_tech.hideOnMouseOut=true;
   mm_menu_build_tech.bgColor='#243254';
   mm_menu_build_tech.menuBorder=1;
   mm_menu_build_tech.menuLiteBgColor='#243254';
   mm_menu_build_tech.menuBorderBgColor='#040D23';

   window.mm_menu_build = new Menu("Обзор зданий",70,18,"",11,"#EEEEEE","#ffffff","#243254","#505F84","left","middle",1,0,500,0,7,true,true,true,0,true,true);
  	mm_menu_build.addMenuItem(mm_menu_build_psi);
  	mm_menu_build.addMenuItem(mm_menu_build_bio);
  	mm_menu_build.addMenuItem(mm_menu_build_tech);
   mm_menu_build.hideOnMouseOut=true;
   mm_menu_build.bgColor='#243254';
   mm_menu_build.menuBorder=1;
   mm_menu_build.menuLiteBgColor='#243254';
   mm_menu_build.menuBorderBgColor='#040D23';

   window.mm_menu_info_2 = new Menu("Различная информация",155,18,"",11,"#EEEEEE","#ffffff","#243254","#505F84","left","middle",1,0,500,0,7,true,true,true,0,true,true);
   mm_menu_info_2.hideOnMouseOut=true;
   mm_menu_info_2.bgColor='#243254';
   mm_menu_info_2.menuBorder=1;
   mm_menu_info_2.menuLiteBgColor='#243254';
   mm_menu_info_2.menuBorderBgColor='#040D23';

   window.mm_menu_faq = new Menu("FAQ",160,18,"",11,"#EEEEEE","#ffffff","#243254","#505F84","left","middle",1,0,500,-5,7,true,true,true,0,true,true);
  	mm_menu_faq.addMenuItem("Официальный FAQ","location='support.php?gamefaq'");
   mm_menu_faq.addMenuItem("FAQ от Сенаторов Ориона","window.open('https://forum.destinysphere.ru/forum/viewforum.php?f=243','_blank');");
  	mm_menu_faq.addMenuItem("FAQ от Альянса Psi Corp","window.open('https://forum.destinysphere.ru/forum/viewtopic.php?t=4921','_blank');");
  	mm_menu_faq.addMenuItem("FAQ от TANKER","window.open('https://forum.destinysphere.ru/forum/viewtopic.php?t=55985','_blank');");
   mm_menu_faq.hideOnMouseOut=true;
   mm_menu_faq.bgColor='#243254';
   mm_menu_faq.menuBorder=1;
   mm_menu_faq.menuLiteBgColor='#243254';
   mm_menu_faq.menuBorderBgColor='#040D23';

   window.mm_menu_support = new Menu("root",160,18,"",11,"#EEEEEE","#ffffff","#243254","#505F84","left","middle",1,0,500,-5,7,true,true,true,0,true,true);
  	mm_menu_support.addMenuItem("Краткое описание игры","location='support.php?game_info'");
   mm_menu_support.addMenuItem("Помощь новичкам","location='support.php?beginer'");
  	mm_menu_support.addMenuItem("Развитие науки","window.open('https://forum.destinysphere.ru/forum/techtree5.html','_blank');");
  	mm_menu_support.addMenuItem(mm_menu_faq);
  	mm_menu_support.addMenuItem(mm_menu_build, "window.open('http://www.destinysphere.ru/ds/buildings.php?race=1','_blank');");
  	mm_menu_support.addMenuItem("Обзор Войск","location='support.php?units_info'");
  	mm_menu_support.addMenuItem("Поиск","window.open('https://forum.destinysphere.ru/forum/search.php','_blank');");
  	mm_menu_support.addMenuItem("Спроси экспертов","window.open('https://forum.destinysphere.ru/forum/posting.php?mode=newtopic&f=3','_blank')");
  	mm_menu_support.addMenuItem("Страницы игроков","location='support.php?user_pages'");
  	mm_menu_support.addMenuItem("Контакт","location='support.php?contact'");
   mm_menu_support.hideOnMouseOut=true;
   mm_menu_support.bgColor='#243254';
   mm_menu_support.menuBorder=1;
   mm_menu_support.menuLiteBgColor='#243254';
   mm_menu_support.menuBorderBgColor='#040D23';

   window.mm_menu_other = new Menu("root",120,18,"",11,"#EEEEEE","#ffffff","#243254","#505F84","left","middle",1,0,500,-5,7,true,true,true,0,true,true);
   mm_menu_other.addMenuItem("Форум","window.open('https://forum.destinysphere.ru/forum/index.php','_blank');");
  	mm_menu_other.addMenuItem("Рейтинг","window.open('http://www.destinysphere.ru/ds/ranking.php','_blank');");
  	mm_menu_other.addMenuItem("Профиль","window.open('https://forum.destinysphere.ru/forum/profile.php?mode=editprofile','_blank');");
  	mm_menu_other.addMenuItem("Чат в форуме","window.open('http://portal.destinysphere.ru/forum/chatbox_mod/chatbox.php','_blank');");
  	mm_menu_other.addMenuItem("IRC-чат","window.open('http://www.destinysphere.ru/ds/irc/irc.php?u=','_blank');");
  	mm_menu_other.addMenuItem("Об оплате Премиума","window.open('https://forum.destinysphere.ru/forum/premiumhelp.php','_blank');");
  	mm_menu_other.addMenuItem("Платные услуги","location='other.php?service_form'");
  	mm_menu_other.addMenuItem("Фэн-аксессуары","location='other.php?fan'");
  	mm_menu_other.addMenuItem("Веб-магазин","location='other.php?web_shop'");
   mm_menu_other.hideOnMouseOut=true;
   mm_menu_other.bgColor='#243254';
   mm_menu_other.menuBorder=1;
   mm_menu_other.menuLiteBgColor='#243254';
   mm_menu_other.menuBorderBgColor='#040D23';

   window.mm_menu_info = new Menu("root",130,18,"",11,"#EEEEEE","#ffffff","#243254","#505F84","left","middle",1,0,500,-5,7,true,true,true,0,true,true);
   mm_menu_info.addMenuItem("Условия игры","location='info.php?license'");
  	mm_menu_info.addMenuItem("Сервисное соглашение","location='info.php?service_license'");
  	mm_menu_info.addMenuItem("Правила форума","location='info.php?forum_license'");
  	mm_menu_info.addMenuItem("Дисциплинарный кодекс","location='info.php?ds_codecs'");
  	mm_menu_info.addMenuItem("Реклама","location='info.php?advertising'");
  	mm_menu_info.addMenuItem("Пресса","location='info.php?press'");
  	mm_menu_info.addMenuItem("Ссылки на нас","location='info.php?linkus'");
  	mm_menu_info.addMenuItem("О создателях","location='info.php?about'");
   mm_menu_info.hideOnMouseOut=true;
   mm_menu_info.bgColor='#243254';
   mm_menu_info.menuBorder=1;
   mm_menu_info.menuLiteBgColor='#243254';
   mm_menu_info.menuBorderBgColor='#040D23';

mm_menu_index.writeMenus();
} // mmLoadMenus()
//-->
</script>
<script language="JavaScript" src="popup_menu.js"></script><!--end menu java script-->
