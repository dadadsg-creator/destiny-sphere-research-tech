<?
# Version 1.2  10.07.2002

class SimEdit {
	var $OUTPUT;
	var $Options = array(
		"StylePath"=>"/styles/main.css",
		"Dimensions"=>array("Width"=>"100%","Height"=>"100%")
	);
	var $Mode = "superadmin";

	function SimEdit() {
		$this->OUTPUT = "";
	}

	function setDimensions($width, $height) {
		$this->Options[Dimensions][Width] = $width;
		$this->Options[Dimensions][Height] = $height;
	}

	function setMode($mode) {
		$this->Mode = $mode;
	}
	
	function setStylePath($StylePath) {
		$this->Options[StylePath] = $StylePath; 
	}

	function getHTML(&$Text) {
		global $SERVER_NAME,$DOCUMENT_ROOT;
		
		$f = @file($DOCUMENT_ROOT.$this->Options[StylePath]);
		$i=0;
		if (sizeof($f)) {
			foreach ($f as $v) {
				ereg('\.(.+)\s*{.*!--(.+)--.*',$v,$regs);
				if ($regs[2]) {
					$styles[$i]['style']=trim($regs[1]);
					$styles[$i]['title']=trim($regs[2]);
					$i++;
				};
				unset($regs);
			};
		};

		$this->OUTPUT .= "
		<SCRIPT language=JavaScript>
			var alloweditsource = new Array();
			var tabview = new Array();
			var ae_fieldname = new Array();				
			var ae_imagedata = new Array();				
			var ae_imageurl = new Array();
			var ae_defaultfont = new Array();				
			var ae_baseurl = new Array();				
			var ae_breakonenter = new Array();
			var inc = '/Admin/SimEdit/';
			var errlevel = 2;
			var StylesheetUrl = 'http://".$SERVER_NAME.$this->Options[StylePath]."';
			
			ae_breakonenter[1] = false;
			ae_baseurl[1] = '';
			ae_defaultfont[1] = '';
			tabview[1] = true;
			alloweditsource[1] = true;
			ae_imagedata[1] = '';				
			ae_imageurl[1] = 'images';
		</SCRIPT>
		<SCRIPT language=JavaScript src='/Admin/SimEdit/JScript/dhtmled.js'></SCRIPT>
		<SCRIPT id=clientEventHandlersJS language=JavaScript src='/Admin/SimEdit/JScript/simedit.js'></SCRIPT>
		<SCRIPT language=JavaScript>
			//declare global javascript variables
			if (errlevel == 2){
				//error catching
				document.write('<BR>The file editjs.cfm did not load properly. Make sure the the inc attribute is specified correctly<br>');
				document.write('Current Value: inc=' + inc + '<br>');
				document.write('<comment>'); // Keeps the rest of the page from displaying
			}
		</SCRIPT>
		<SCRIPT language=javascript src='/Admin/SimEdit/JScript/user.js'></SCRIPT>
		
		<STYLE type=text/css>.disabled {
			BORDER-BOTTOM-WIDTH: 0px; BORDER-LEFT-WIDTH: 0px; BORDER-RIGHT-WIDTH: 0px; BORDER-TOP-WIDTH: 0px; FILTER: mask() mask(color=buttonshadow) dropshadow(offX=1,offY=1,color=buttonhighlight,positive=1); HEIGHT: 22px; MARGIN: 1px; PADDING-BOTTOM: 0px; PADDING-LEFT: 0px; PADDING-RIGHT: 0px; PADDING-TOP: 0px; VERTICAL-ALIGN: middle; VISIBILITY: visible; WIDTH: 23px
		}
		.flat {
			BORDER-BOTTOM: buttonface 1px solid; BORDER-LEFT: buttonface 1px solid; BORDER-RIGHT: buttonface 1px solid; BORDER-TOP: buttonface 1px solid; HEIGHT: 22px; MARGIN: 0px; PADDING-BOTTOM: 0px; PADDING-LEFT: 0px; PADDING-RIGHT: 0px; PADDING-TOP: 0px; VERTICAL-ALIGN: middle; VISIBILITY: visible; WIDTH: 23px
		}
		.outset {
			BORDER-BOTTOM: buttonshadow 1px solid; BORDER-LEFT: buttonhighlight 1px solid; BORDER-RIGHT: buttonshadow 1px solid; BORDER-TOP: buttonhighlight 1px solid; HEIGHT: 22px; MARGIN: 0px; PADDING-BOTTOM: 0px; PADDING-LEFT: 0px; PADDING-RIGHT: 0px; PADDING-TOP: 0px; VERTICAL-ALIGN: middle; VISIBILITY: visible; WIDTH: 23px
		}
		.inset {
			BORDER-BOTTOM: 1px inset; BORDER-LEFT: 1px inset; BORDER-RIGHT: 1px inset; BORDER-TOP: 1px inset; HEIGHT: 22px; MARGIN: 0px; PADDING-BOTTOM: 0px; PADDING-LEFT: 0px; PADDING-RIGHT: 0px; PADDING-TOP: 0px; VERTICAL-ALIGN: middle; VISIBILITY: visible; WIDTH: 23px
		}
		.latched {
			BACKGROUND-COLOR: #eeeeee; BORDER-BOTTOM: 1px inset; BORDER-LEFT: 1px inset; BORDER-RIGHT: 1px inset; BORDER-TOP: 1px inset; HEIGHT: 22px; MARGIN: 0px; PADDING-BOTTOM: 0px; PADDING-LEFT: 0px; PADDING-RIGHT: 0px; PADDING-TOP: 0px; VERTICAL-ALIGN: middle; VISIBILITY: visible; WIDTH: 23px
		}
		.spc {
			MARGIN: 0px; PADDING-BOTTOM: 0px; PADDING-LEFT: 0px; PADDING-RIGHT: 0px; PADDING-TOP: 0px; VERTICAL-ALIGN: middle
		}
		TABLE.ae_tableClass {
			BACKGROUND-COLOR: buttonface; BORDER-BOTTOM: buttonface 1px outset; BORDER-LEFT: buttonface 1px outset; BORDER-RIGHT: buttonface 1px outset; BORDER-TOP: buttonface 1px outset; CURSOR: default
		}
		TR.ae_trClass {
			BACKGROUND-COLOR: CED7DE
		}
		TD.ae_tdClass {
			BACKGROUND-COLOR: CED7DE
		}
		</STYLE>
		
		<TABLE bgColor=#CED7DE width=\"".$this->Options[Dimensions][Width]."\" height=\"".$this->Options[Dimensions][Height]."\"  cellPadding=0 cellSpacing=0 ondragstart=window.event.returnValue=false; onselectstart=window.event.returnValue=false;>
		  <TBODY>
		  <TR class=ae_trClass>
		    <TD class=ae_tdClass colSpan=2 vAlign=bottom>
			  <IFRAME id=frame1 marginHeight=0 marginWidth=0 scrolling=no
			  src='/Admin/SimEdit/blank.htm' 
		      style='HEIGHT: 10px; POSITION: absolute; VISIBILITY: hidden; WIDTH: 10px; Z-INDEX: 2'></IFRAME>
		      <TABLE border=0 cellPadding=0 cellSpacing=2>
		        <TBODY>
		        <TR vAlign=center>
		          <TD background='/Admin/SimEdit/Img/toolbar.gif' width=10><IMG alt='' height=24 src='/Admin/SimEdit/' width=9></TD>
		          <TD>
				  <SPAN id=ae_tbar1 ondragstart=window.event.returnValue=false;  onmousedown=ae_m_down(window.event.srcElement);       onmouseout=ae_m_out(window.event.srcElement);   onmouseover=ae_m_over(window.event.srcElement);  onmouseup=ae_m_up(window.event.srcElement);  onselectstart=window.event.returnValue=false;>
					<NOBR><IMG alt=Вырезать 
		            class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/cut.gif' cid='5003' type='btn' 
		            status='Cut the selected text and put it on the clipboard'><IMG 
		            alt=Копировать class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/copy.gif' cid='5002' type='btn' 
		            status='Copy the selected text into the clipboard'><IMG alt=Вставить 
		            class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/paste.gif' cid='5032' type='btn' 
		            status='Paste the contents of the clipboard into the workspace'><IMG 
		            class=spc src='/Admin/SimEdit/Img/space.gif'><WBR><IMG alt=Отменить class=flat 
		            onclick='ae_onCommand(null, 1)' src='/Admin/SimEdit/Img/undo.gif' 
		            cid='5049' type='btn' status='Undo the last operation'><IMG alt=Вернуть 
		            class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/redo.gif' cid='5033' type='btn' 
		            status='Redo the last operation'><IMG 
		            class=spc src='/Admin/SimEdit/Img/space.gif'><WBR><IMG alt=Шрифт 
		            class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/fgcolor.gif' cid='5009' type='btn' 
		            status='Change font and color'><IMG alt=Полужирный class=flat 
		            onclick='ae_onCommand(null, 1)' src='/Admin/SimEdit/Img/bold.gif' 
		            cid='5000' type='btn' status='Make selected text bold'><IMG 
		            alt=Курсив class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/italic.gif' cid='5023' type='btn' 
		            status='Make selected text italic'><IMG alt=Подчёркнутый class=flat 
		            onclick='ae_onCommand(null, 1)' src='/Admin/SimEdit/Img/under.gif' 
		            cid='5048' type='btn' status='Underline the selected text'><IMG 
		            class=spc src='/Admin/SimEdit/Img/space.gif'><WBR><IMG 
		            alt='Уменьшить отступ' class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/deindent.gif' cid='5031' type='btn' 
		            status='Reverse indent the current line'><IMG alt='Увеличить отступ' class=flat 
		            onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/inindent.gif' cid='5018' type='btn' 
		            status='Indent the current line'><IMG class=spc 
		            src='/Admin/SimEdit/Img/space.gif'><WBR><IMG alt='По левому краю' 
		            class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/left.gif' cid='5025' type='btn' 
		            status='Left justify the current line'><IMG alt='По центру' class=flat 
		            onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/center.gif' cid='5024' type='btn' 
		            status='Center the current line'><IMG alt='По правому краю' class=flat 
		            onclick='ae_onCommand(null, 1)' src='/Admin/SimEdit/Img/right.gif' 
		            cid='5026' type='btn' status='Right justify the current line'><IMG 
		            alt=Маркеры class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/bullist.gif' cid='5051' type='btn' 
		            status='Insert a bullet on current line'><IMG class=spc 
		            src='/Admin/SimEdit/Img/space.gif'><WBR><IMG alt='Вставить таблицу' 
		            class=flat name=ae_tbtn1 onclick='ae_onCommand(null, 1)' 
		            ondragstart='return false' onmouseout=ae_m_out(ae_tdrop1); 
		            onmouseover=ae_m_over(ae_tdrop1); 
		            src='/Admin/SimEdit/Img/instable.gif' cid='5022' type='btn' 
		            status='Insert a table'><IMG alt='Создать таблицу' class=flat 
		            name=ae_tdrop1 onclick=onTable(1) ondragstart='return false' 
		            onmouseout=ae_m_out(ae_tbtn1); onmouseover=ae_m_over(ae_tbtn1); 
		            src='/Admin/SimEdit/Img/tbdown.gif' style='WIDTH: 9px' cid='5022' 
		            type='btn' status='Insert a table'><IMG alt='Вставить картинку' 
		            class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/image.gif' cid='5017' type='btn' 
		            status='Insert an image'><IMG alt='Вставить гиперссылку' class=flat 
		            onclick='ae_onCommand(null, 1)' src='/Admin/SimEdit/Img/link.gif' 
		            cid='5016' type='btn' 
		            status='Insert a hyperlink on selected text'><IMG class=spc 
		            src='/Admin/SimEdit/Img/space.gif'><WBR><IMG alt=Найти class=flat 
		            onclick='ae_onCommand(null, 1)' src='/Admin/SimEdit/Img/find.gif' 
		            cid='5008' type='btn' status='Find text in the document'><IMG 
		            alt=Справка class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/help.gif' cid='6003' type='btn' 
		            status='Help'><!--
					<IMG alt='Spell Check' class=flat onclick='ae_onCommand(null, 1)' src='/Admin/SimEdit/Img/spell.gif' cid='6007' type='btn' status='Open spell check window'>
					--><IMG alt='Специальные символы' class=flat onclick='ae_onCommand(null, 1)' 
		            src='/Admin/SimEdit/Img/specialchar.gif' cid='6008' type='btn' 
		            status='Insert a Special Character'><BR><IMG height=24 src='/Admin/SimEdit/' 
		            style='VERTICAL-ALIGN: middle' width=1>
			 		<SELECT id=oQuickFormat1 onchange=ae_quickformat(1); size=1 style='FONT: 8pt Verdana; VERTICAL-ALIGN: middle; WIDTH: 120px'></SELECT> 
		            <IMG height=24 src='/Admin/SimEdit/' style='VERTICAL-ALIGN: middle' width=1> 
		            <SELECT id=oQuickFontSize1 onchange=ae_quickfontsize(1); size=1 style='FONT: 8pt Verdana; VERTICAL-ALIGN: middle; WIDTH: 40px'> 
		            	<OPTION selected value=1 name='ae_fs1'>8</OPTION>
						<OPTION value=2 name='ae_fs2'>10</OPTION>
						<OPTION value=3 name='ae_fs3'>12</OPTION>
						<OPTION value=4 name='ae_fs4'>14</OPTION>
						<OPTION value=5 name='ae_fs5'>18</OPTION>
						<OPTION value=6 name='ae_fs6'>24</OPTION>
						<OPTION value=7 name='ae_fs7'>36</OPTION>
					</SELECT> 
		            <IMG height=24 src='/Admin/SimEdit/' style='VERTICAL-ALIGN: middle' width=1> 
		            <SELECT id=oQuickFont1 onchange=ae_quickfont(1); size=1 style='FONT: 8pt Verdana; VERTICAL-ALIGN: middle; WIDTH: 120px'> 
		               <OPTION selected value=Arial name='Arial'>Arial</OPTION>
					   <OPTION value='Courier New' name='Courier New'>Courier New</OPTION>
					   <OPTION value=Helvetica name='Helvetica'>Helvetica</OPTION>
					   <OPTION value='Times New Roman' name='Times New Roman'>Times New Roman</OPTION>
					   <OPTION value=Verdana name='Verdana'>Verdana</OPTION>
					</SELECT>
					<SELECT id='style' onchange=css(this,1); size=1 style='FONT: 8pt Verdana; VERTICAL-ALIGN: middle; WIDTH: 120px'>
					   <OPTION VALUE=''>Стиль</OPTION>";
		if (sizeof($styles)) {
			foreach($styles as $v) {
		 		$this->OUTPUT .= "<OPTION VALUE='".$v['style']."'>".$v['title']."</OPTION>";
			};
		};
		$this->OUTPUT .= "
					</SELECT>  
		            </NOBR><BR><IMG alt='' height=1 src='/Admin/SimEdit/' width=1></SPAN>
				  </TD>
				 </TR>
				 </TBODY>
			</TABLE>
		   </TD>
		   <TD class=ae_tdClass>&nbsp;</TD>
		 </TR>
		 <TR class=ae_trClass>
		   <TD class=ae_tdClass>&nbsp; </TD>
		   <TD class=ae_tdClass height='100%' width='100%'>
		      <OBJECT classid=clsid:2D360201-FFF5-11d1-8D03-00A0C959BC0A height='100%' 
		      id=DHTMLSafe1 style='Z-INDEX: 1' width='100%'><PARAM NAME='ScrollbarAppearance' VALUE='0'></OBJECT>
		      <SCRIPT event=ShowContextMenu for=DHTMLSafe1 language=javascript>
				return ShowMenu(1);
			  </SCRIPT>
		      <SCRIPT event=onkeypress for=DHTMLSafe1 language=javascript>
				return ae_onkeypress(1);
			  </SCRIPT>
		      <SCRIPT event=onmousedown for=DHTMLSafe1 language=javascript>
				return ae_onmousedown(1);
		  	  </SCRIPT>
		      <SCRIPT event=onclick for=DHTMLSafe1 language=javascript>
				return ae_onclick(1);
			  </SCRIPT>
		      <SCRIPT event=DisplayChanged for=DHTMLSafe1 language=javascript>
				return ae_ondisplaychange(1);
			  </SCRIPT>
		      <SCRIPT event=ContextMenuAction(itemIndex) for=DHTMLSafe1 language=javascript>
				return ae_onmenuaction(itemIndex);
		 	  </SCRIPT>
		   </TD>
		   <TD class=ae_tdClass>&nbsp;</TD>
		  </TR>
			  <TR>
			    <TD colSpan=3 bgcolor=94A2B5 background=/Admin/img/publisher-menu-left-bg.gif>
			      <TABLE border=0 cellPadding=0 cellSpacing=0 width='100%'>
			        <TR>
			          <TD valign=top><IMG height=1 src='/Admin/SimEdit/' width=20><IMG alt='' border=0 height=11 
			            name=normaltab1 onclick='ae_editsourceinline(1, false)' 
			            src='/Admin/SimEdit/Img/normaltabon.gif' width=49><IMG alt='' 
			            border=0 height=11 name=htmltab1 
			            onclick='ae_editsourceinline(1, true)' 
			            src='/Admin/SimEdit/Img/htmltaboff.gif' width=41></TD>";
		if ($this->Mode != "simpleedit") {
			$this->OUTPUT .= "
					  <td><img src=/Admin/SimEdit/emp.gif width=1 height=40 border=0></td>
			          <TD align=right> <INPUT onclick=ae_onSubmit();document.editform.submit(); type=button value=Сохранить изменения class=submit>&nbsp;  </TD>";
		} 
		$this->OUTPUT .= "
					</TR>
				   </TABLE>
				 </TD>
			  </TR>
		  </TBODY>
		</TABLE>";

		$this->OUTPUT .= "<TEXTAREA id=ae_tx_content1 name=SimEditContent1 style='POSITION: absolute; VISIBILITY: hidden'>".$Text."</TEXTAREA>";
		$this->OUTPUT .= "<SCRIPT>ae_initialize(1);</SCRIPT>";
		return $this->OUTPUT;
	}

}
?>