		{if empty($VISIT)}
			<b>{$lang.EMPTY_VISIT}</b><br><br>
		{/if}
		
		{if empty($TRADE)}
			<b>{$lang.EMPTY_TRADE}</b><br><br>
		{/if}
		
		{if !empty($TRADE)}
				<table border="0">
					<tr>
						<th align="center" class=header_txt_02>
							{$lang.TRADE_ACTIONS}
						</th>
					</tr>
					<tr>
						<td valign="top">
						<table cellpadding="3"  style="border:1px solid black; border-collapse:collapse;">
				{foreach from=$TRADE item=WORLD}
				<tr>
					<th style="border: 1px solid black" colspan="7">
						&nbsp;&nbsp;&nbsp; {$lang.WORLD} {$WORLD.world_name} &nbsp;&nbsp;&nbsp;
					</th>
				</tr>
				<tr>
					<th style="border: 1px solid black">
						&nbsp;&nbsp;&nbsp;{$lang.SELLER}&nbsp;&nbsp;&nbsp;
					</th>
					<th style="border: 1px solid black">
						&nbsp;&nbsp;&nbsp;{$lang.CUSTOMER}&nbsp;&nbsp;&nbsp;
					</th>
					<th style="border: 1px solid black">
						&nbsp;&nbsp;&nbsp;{$lang.WHAT}&nbsp;&nbsp;&nbsp;
					</th>
					<th style="border: 1px solid black">
						&nbsp;&nbsp;&nbsp;{$lang.FOR_WHAT}&nbsp;&nbsp;&nbsp;
					</th>
					<th style="border: 1px solid black">
						&nbsp;&nbsp;&nbsp;{$lang.TAX}&nbsp;&nbsp;&nbsp;
					</th>
					<th style="border: 1px solid black">
						&nbsp;&nbsp;&nbsp;{$lang.DATE_FROM}&nbsp;&nbsp;&nbsp;
					</th>
					<th style="border: 1px solid black">
						&nbsp;&nbsp;&nbsp;{$lang.DATE_TO}&nbsp;&nbsp;&nbsp;
					</th>
				</tr>
				
				{foreach from=$WORLD.data item=VALUE}
				<tr>
					<td style="border: 1px solid black" align="center">
						&nbsp;&nbsp;&nbsp;{$VALUE.seller}&nbsp;&nbsp;&nbsp;
					</td>
					<td style="border: 1px solid black" align="center">
						&nbsp;&nbsp;&nbsp;{$VALUE.customer}&nbsp;&nbsp;&nbsp;
					</td>
					<td style="border: 1px solid black" align="center">
						&nbsp;&nbsp;&nbsp;{$VALUE.from_ammount|number_format:0:"":" "} {$VALUE.goods_from}&nbsp;&nbsp;&nbsp;
					</td>
					<td style="border: 1px solid black" align="center">
						&nbsp;&nbsp;&nbsp;{$VALUE.to_ammount|number_format:0:"":" "} {$VALUE.goods_to}&nbsp;&nbsp;&nbsp;
					</td>
					<td style="border: 1px solid black" align="center">
						&nbsp;&nbsp;&nbsp;{$VALUE.tax_summ}&nbsp;&nbsp;&nbsp;
					</td>
					<td style="border: 1px solid black" align="center">
						&nbsp;&nbsp;&nbsp;{$VALUE.start_date}&nbsp;&nbsp;&nbsp;
					</td>
					<td style="border: 1px solid black" align="center">
						&nbsp;&nbsp;&nbsp;{$VALUE.end_date}&nbsp;&nbsp;&nbsp;
					</td>
				</tr>
				{/foreach}
			{/foreach}
				</table>
				</td>
				</tr>
			</table>
		{/if}
		
		{if !empty($VISIT)}
			<table border="0">
					<tr>
						<th align="center" class=header_txt_02>
							&nbsp;&nbsp;&nbsp;Заходы на соту&nbsp;&nbsp;&nbsp;
							</th>
					</tr>
					<tr>
						<td valign="top">
						<table cellpadding="3"  style="border:1px solid black; border-collapse:collapse;">
							<tr>
								<th style="border: 1px solid black">
									&nbsp;&nbsp;&nbsp;{$lang.DATE}&nbsp;&nbsp;&nbsp;
								</th>
								<th style="border: 1px solid black">
									&nbsp;&nbsp;&nbsp;{$lang.IP_ADDRESS}&nbsp;&nbsp;&nbsp;
								</th>
							</tr>
			{foreach from=$VISIT item=VALUE}
				<tr>
					<td style="border: 1px solid black" align="center">
						&nbsp;&nbsp;&nbsp;{$VALUE.lastvisit}&nbsp;&nbsp;&nbsp;
					</td>
					<td style="border: 1px solid black" align="center">
						&nbsp;&nbsp;&nbsp;{$VALUE.ip}&nbsp;&nbsp;&nbsp;
					</td>
				</tr>
			{/foreach}
				</table>
				</td>
				</tr>
			</table>
		{/if}