function radio_button_checker()
{
	var radio_choice = false;
	var i;	
	
	if(storeForm.UIID.length == null)
	{
		radio_choice = storeForm.UIID.checked;
	} else {
		for(i=0; i<storeForm.UIID.length; i++)
		{
			if(storeForm.UIID[i].checked)
			{
				radio_choice = true; 
				break;
			}
		}
	}

	if (!radio_choice)
	{
		alert("No user instance selected!")
		return (false);
	}
	return (true);
}