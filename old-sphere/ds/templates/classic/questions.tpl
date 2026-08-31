<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">

<html dir="ltr">

<head>

<meta http-equiv="Content-Type" content="text/html; charset={CHARSET}">

<meta http-equiv="Content-Style-Type" content="text/css">



<title>{TITLE}</title>

<link rel="stylesheet" href="DestinySphere.css" type="text/css">

</head><body bgcolor="#000000" text="#DDDDDD" link="#8BC7FF" vlink="#5493B4">





<script language="Javascript" type="text/javascript">

<!--

function MM_preloadImages() { //v3.0

  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();

    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)

    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}

}

//-->

</script>



<script language="Javascript" type="text/javascript">

<!--

function rollover(name, source)  {



  document.images[name].src = source;



}

//-->

</script>



<a name="top"></a>



<table width="100%" border="0" cellpadding="0" cellspacing="0">

<tr>

    <td width="5" height="5"></td>

    <td height="5"></td>

    <td height="5" width="5"></td>

</tr>



<tr>

    <td width="5"></td>

    <td>



<table width="100%" height="550" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#000000">

        <tr>

		  <td valign="top" class="bodyline">

            <table width="100%" cellspacing="0" cellpadding="0" border="0">
              	<tr>
              		<td align="center"><a href="http://www.destinysphere.ru"><span class="maintitle"><img src="images/logo_ds.gif" border="0" alt="Главная страница Destiny Sphere" hspace="10" width="229" height="70" /></span></a><a href="http://www.rambler.ru" target="_blank"><span class="maintitle"><img src="images/logorambler.gif" border="0" alt="Rambler" hspace="10" width="108" height="70" /></span></a></td>
              	</tr>
        		</table>

            <br>

            <table width="100%" height="449" border="0" align="center" cellpadding="0" cellspacing="0">

              <tr>

                <td valign="top" width="100%">

                  <table width="100%" height="449" border="0" align="right" cellpadding="1" cellspacing="1">

                    <tr>

                      <td valign="top">



                   <form method='post' action='questions.php'>



	  							<table width="100%" cellpadding="0" cellspacing="0" border="0">
  									<tr>
			  							<td height="8" class="TopBr1" align="left"><img src="images/border/f_topic_left.gif" border="0"></td>
  										<td height="8" class="TopBr1" align="right"><img src="images/border/f_topic_right.gif" border="0"></td>
			  						</tr>
								</table>

                        <table width='100%' height="449" border='0' cellpadding='2' cellspacing='1' class='forumline'>
                        	<tr>
                           	<td height='25' align="center" class='cathead'><span class='genblue'><b>
                              	&nbsp;&nbsp;&nbsp;{TEXT1}</b></span></td>
                           </tr>

  									<tr>
  										<td class="TopBr3"></td>
  									</tr>

                           <tr>
                           	<td align="left" class='row1'>
                      				<!-- BEGIN ERROR -->
		                      		<br><FONT color="#FFCCCC" size="+1"><b>{ERROR}</b></font>
											<!-- END ERROR -->
                             		<br> {TEXT2}
                              	<br><br>
                              	{AGE}<br>
                              	<!-- BEGIN AGE -->
                              	<input type="radio" name="age" value="{AGE_VALUE}" {AGE_CHECKED} style="background-color: #C6CDDD;"> {AGE_TEXT}<br>
                              	<!-- END AGE -->
								  			<br>
									  		{GENDER}<br>
	                              <!-- BEGIN GENDER -->
   	                           <input type="radio" name="gender" value="{GENDER_VALUE}" {GENDER_CHECKED} style="background-color: #C6CDDD;"> {GENDER_TEXT}<br>
      	                        <!-- END GENDER -->
                              	<br>
								  			{BUSY}<br>
	                              <!-- BEGIN BUSY -->
	                              <input type="radio" name="busy" value="{BUSY_VALUE}" {BUSY_CHECKED} style="background-color: #C6CDDD;"> {BUSY_TEXT}<br>
	                              <!-- END BUSY -->
								  			<br>
										  	{PROFESSION}<br>

										  	<select name="profession" size="1">
											<option value="">----------------------------------------------</option>
                              	<!-- BEGIN PROFESSION -->
	                              <option value="{PROFESSION_VALUE}" {PROFESSION_SELECTED}> {PROFESSION_TEXT}<br>
                              	<!-- END PROFESSION -->
 											</select>
										  	<br><br>
								  			{INCOME}<br>

											<select name="income" size="1">
											<option value="">-------------------</option>
                              	<!-- BEGIN INCOME -->
	                              <option value="{INCOME_VALUE}" {INCOME_SELECTED}> {INCOME_TEXT}<br>
                              	<!-- END INCOME -->
 											</select>
								  			<br><br>
								  			{INTEREST}<br>
                              	<!-- BEGIN INTEREST -->
                              	<input type="checkbox" name="interest[{INT}]" value="{INTEREST_VALUE}" {INTEREST_CHECKED} style="background-color: #C6CDDD;"> {INTEREST_TEXT}<br>
                              	<!-- END INTEREST -->
								  			<br>
								  			{FAMILY}<br>
	                              <!-- BEGIN FAMILY -->
   	                           <input type="radio" name="family" value="{FAMILY_VALUE}" {FAMILY_CHECKED} style="background-color: #C6CDDD;"> {FAMILY_TEXT}<br>
      	                        <!-- END FAMILY -->
										  	<br>
								  			{KIDS}
											<select name="kids" size="1">
											<option value="">----------</option>
                              	<!-- BEGIN KIDS -->
                              	<option value="{KIDS_VALUE}" {KIDS_SELECTED}> {KIDS_TEXT}<br>
                              	<!-- END KIDS -->
 											</select>
								  			<br><br>
								  			{PLACE}
											<select name="place" size="1">
											<option value="">--------------------------</option>
                              	<!-- BEGIN PLACE -->
		                           <option value="{PLACE_VALUE}" {PLACE_SELECTED}> {PLACE_TEXT}<br>
                              	<!-- END PLACE -->
 											</select>
								  			<br><br>
								  			{ONLINE}<br>
                              	<!-- BEGIN ONLINE -->
                              	<input type="radio" name="online" value="{ONLINE_VALUE}" {ONLINE_CHECKED} style="background-color: #C6CDDD;"> {ONLINE_TEXT}<br>
                              	<!-- END ONLINE -->
										  	<br>
								  			{HOURS}<br>
                              	<!-- BEGIN HOURS -->
                              	<input type="radio" name="hours" value="{HOURS_VALUE}" {HOURS_CHECKED} style="background-color: #C6CDDD;"> {HOURS_TEXT}<br>
                              	<!-- END HOURS -->
                             		<br><br>
                               	{INTERNET_WHERE}<br>
                              	<!-- BEGIN INTERNET_WHERE -->
                              	<input type="checkbox" name="internet_where[{INT}]" value="{INTERNET_WHERE_VALUE}" {INTERNET_WHERE_CHECKED} style="background-color: #C6CDDD;"> {INTERNET_WHERE_TEXT}<br>
                              	<!-- END INTERNET_WHERE -->
								  			<br><br>
								  			{AVTO}<br>
                              	<!-- BEGIN AVTO -->
                              	<input type="radio" name="avto" value="{AVTO_VALUE}" {AVTO_CHECKED} style="background-color: #C6CDDD;"> {AVTO_TEXT}<br>
                              	<!-- END AVTO -->
								  			<br><br>
										  	<span class="genmed">{PROF_EDUC}</span><br>
                              	<!-- BEGIN PROF_EDUC -->
                              	<input type="radio" name="prof_educ" value="{PROF_EDUC_VALUE}" {PROF_EDUC_CHECKED} style="background-color: #C6CDDD;"> {PROF_EDUC_TEXT}<br>
                              	<!-- END PROF_EDUC -->
								  			<br><br>
								  			<span class="genmed">{WORKER_AMOUNT}</span><br>
                              	<!-- BEGIN WORKER_AMOUNT -->
                              	<input type="radio" name="worker_amount" value="{WORKER_AMOUNT_VALUE}" {WORKER_AMOUNT_CHECKED} style="background-color: #C6CDDD;"> {WORKER_AMOUNT_TEXT}<br>
                              	<!-- END WORKER_AMOUNT -->
								  			<br><br>
								  			{DS_HOW_OFTEN}<br>
	                              <!-- BEGIN DS_HOW_OFTEN -->
   	                           <input type="radio" name="ds_how_often" value="{DS_HOW_OFTEN_VALUE}" {DS_HOW_OFTEN_CHECKED} style="background-color: #C6CDDD;"> {DS_HOW_OFTEN_TEXT}<br>
      	                        <!-- END DS_HOW_OFTEN -->
								  			<br><br>
								  			<span class="genmed">{BUY_OWN}</span><br><br>

											<table border="1" cellpadding="0" cellpadding="0">
												<tr>
                                    	<td></td>
													<!-- BEGIN BUY_OWN_VARIANTS_NAME -->
													<td align="center"><span class="genmed">{VARIANT_NAME}</span></td>
													<!-- END BUY_OWN_VARIANTS_NAME -->
												</tr>
                              		<!-- BEGIN BUY_OWN -->
                              		<tr>
                                    	<td><span class="genmed">{BUY_OWN_NAME}</span></td>
			                              <!-- BEGIN BUY_OWN_VARIANTS -->
                              			<td align="center">
                              				<input type="radio" name="buy_own[{INT}]" value="{BUY_OWN_VALUE}" {BUY_OWN_CHECKED} style="background-color: #C6CDDD;">
                              			</td>
			                              <!-- END BUY_OWN_VARIANTS -->
                              		</tr>
		                              <!-- END BUY_OWN -->
                              	</table>

								  			<br><br>
								  			<span class="genmed">{BUY_PRICE_QUALITY}</span><br><br>

											<table border="1" cellpadding="0" cellpadding="0">
												<tr>
                                    	<td></td>
													<!-- BEGIN BUY_PRICE_QUALITY_VARIANTS_NAME -->
													<td align="center"><span class="genmed">{VARIANT_NAME}</span></td>
													<!-- END BUY_PRICE_QUALITY_VARIANTS_NAME -->
												</tr>
                              		<!-- BEGIN BUY_PRICE_QUALITY -->

                              		<tr>
                                    	<td><span class="genmed">{BUY_PRICE_QUALITY_NAME}</span></td>
                              			<!-- BEGIN BUY_PRICE_QUALITY_VARIANTS -->
                              			<td align="center">
                              				<input type="radio" name="buy_price_quality[{INT}]" value="{BUY_PRICE_QUALITY_VALUE}" {BUY_PRICE_QUALITY_CHECKED} style="background-color: #C6CDDD;">
			                              </td>
			                              <!-- END BUY_PRICE_QUALITY_VARIANTS -->
         		                     </tr>
		                              <!-- END BUY_PRICE_QUALITY -->
	                              </table>

											<br><br>

											<div align="center">
											<input type="submit" value="{SUBMIT}">&nbsp;&nbsp;<input type="reset" value="{RESET}"><br>
											</div>
									  </td>

                            </tr>

                          </table>

                        </form>

                      </td>

                    </tr>

                  </table>

                </td>

              </tr>

            </table>



<div align="center"><span class="copyright">

			  <table width="100%" border="0" cellspacing="0" cellpadding="0">

                <tr>

                  <td align="center" class="copyright">&copy; Copyright 2003-2004 by

                    DestinySphere GmbH</td>

  </tr>



</table>







</td></tr></table>



</td>

    <td width="5"></td>

	</tr>



  <tr>

    <td height="5" width="5"></td>

    <td height="5"></td>

    <td height="5" width="5"></td>



</table>





</body>

</html>