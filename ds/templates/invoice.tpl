<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>DestinySphere Premium - Rechnung</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">
<!--
.header1 {
	font-family: Tahoma, Arial, Helvetica, sans-serif;
	font-size: 12px;
}
.header2 {
	font-family: Tahoma, Arial, Helvetica, sans-serif;
	font-size: 16px;
	font-weight: bold;
}
.header1bold {
	font-family: Tahoma, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
}
.footer {
	font-family: Tahoma, Arial, Helvetica, sans-serif;
	font-size: 10px;
}
-->
</style>
</head>

<body bgcolor="#FFFFFF">
<p align="center"><img src="images/logo_premium2.gif"></p>
<p class="header1">{INVOICE_ADDRESS}</p>
<p class="header1">{FIRSTNAME} {SURNAME}<br>
  {STREET}<br>
  {POSTCODE} {CITY}</p>
<p class="header1">{PAYMENT_DATE_TEXT} {PAYMENT_DATE}<br>
  {CUSTOMER_NUMBER_TEXT} {LANGUAGE_SHORT}-{ID_USER}<br>
  {INVOICE_NUMBER_TEXT} {LANGUAGE_SHORT}-{ID_PAYMENT}</p>
<p class="header2">{INVOICE_CAPTION}</p>
<p class="header1"> {PAYMENT_PERIOD} {PAYMENT_START_DATE} &#8211; {PAYMENT_END_DATE}</p>
<hr>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr class="header1bold"> 
    <td width="25%">{CAPTION1}</td>
    <td width="15%">{CAPTION2}</td>
    <td width="29%">{CAPTION5}</td>
    <td width="16%">{CAPTION3}</td>
    <td width="15%">{CAPTION4}</td>
  </tr>
  <tr class="header1"> 
    <td width="25%">{PAYMENT_MODEL}</td>
    <td width="15%">{USERNAME}</td>
    <td width="29%">{EMAIL}</td>
    <td width="16%">{PRICE}</td>
    <td width="15%">{TOTAL_PRICE}</td>
  </tr>
  <tr align="right" class="header1">
    <td colspan="4">&nbsp;</td>
  </tr>
  <tr align="right" class="header1"> 
    <td colspan="4">{PRICE_TEXT} {PRICE}</td>
  </tr>
  <tr align="right" class="header1"> 
    <td colspan="4">{TAX_TEXT} {TAX}</td>
  </tr>
</table>
<hr>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr align="right" class="header1"> 
    <td width="100%">{TOTAL_PRICE_TEXT} {TOTAL_PRICE}</td>
  </tr>
</table>
<p class="header1">{TEXT1} {ACCOUNT_NUMBER} {TEXT27} {BANK_NAME} {TEXT2} {BANK_CODE} {TEXT28} {COUNTRY} {TEXT3}</p>
<p class="header1">{TEXT4}</p>
<p class="header1">{TEXT5}</p>
<p align="center" class="footer">{FOOTER}</p>

<a href='javascript:window.print()'>{PRINT}</a>


</body>
</html>