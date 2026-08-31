{if $ACTION == 'show_question'}
<br>
<br>


	
	{foreach from=$QUESTIONS key=k item=data}
	
	{if $data.is_active == 't'}
		{assign var=val value='f'}
		{assign var=button_text value='Сделать неактивным'}
		{assign var=color value='#FFFAFA'}
	{else}
		{assign var=val value='t'}
		{assign var=button_text value='Cделать активным'}
		{assign var=color value='#696969'}
	{/if}
	
	<font color={$color}>
		<form action="" method="POST">
		<input type="hidden" name="change_active" value="{$val}">
		<input type="hidden" name="id_question" value="{$k}">
		
		<b>{$data.question}</b>
	
		<input type="submit" class="submit" value="{$button_text}">
		</form>
	
	{assign var=foo value=1}
		{foreach from=$data.answers key=k1 item=answer}
		{$foo}. {$answer}<br>
		{assign var=foo value=`$foo+1`}
		{/foreach}
	<br>
	</font>
	{/foreach}
	
<br>

<form action="" method="POST">
<input type="submit" class="submit" value="Back">
</form>
{else}


{if !empty($ERROR)}
<h2><font color="Red">Error: {$ERROR}!</font></h2><br>
{/if}

{if !empty($SUCCESFUL)}
<h2><font color="#FFB5C5">Вопрос добавлен!</font></h2><br>
{/if}

<form action="" method="POST">
<TABLE border="0">
	<tr>
		<th>
			<font color=#FFFFFF>Вопрос:</font>
		</th>
		<th>
			<textarea name="question" rows="10" cols="150">{$question}</textarea>
		</th>
	</tr>
	<tr>
		<th>
			&nbsp;<br>
		</tr>
	<tr>
</TABLE>
		
		<TABLE border="0" cellpadding="5" >	
			<tr>
				<th>
					<font color=#FFFFFF>N</font>
				</th>
				<th>
					<font color=#FFFFFF>Текст ответа</font>
				</th>
				<th>
					<font color=#FFFFFF>Привильный<br>ответ</font>
				</th>
			</tr>
			
			{section name=question start=1 loop=6 step=1}
			<tr>
				<th>
				{if $is_right == $smarty.section.question.index}
					{assign var="selected" value="checked"}
				{else}
					{assign var="selected" value=""}
				{/if}
					<font color=#FFFFFF>{$smarty.section.question.index}
				</th>
				<th>
					<input type="text" size="100" name="answer[{$smarty.section.question.index}]" value="{$answer[$smarty.section.question.index]}">
				</th>
				<th>
					<input name="is_right" value="{$smarty.section.question.index}" type="radio" {$selected}>
				</th>
			</tr>
			{/section}
			
			</TABLE>
	<br><br>
	<input type="hidden" name="add">
	<input type="submit" class="Submit" value="Добавить">
	</form>
	<br>
<br>
<form action="" method="POST">
<input type="hidden" name="show_questions">
<input type="submit" class="submit" value="Посмотреть вопросы">
</form>

{/if}