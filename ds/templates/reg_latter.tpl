
{$lang.REG_CONGERT} 

{$lang.REG_LOGIN}: {$LOGIN}
{$lang.REG_PASSWORD}: {$PASSWORD}
{if $FORUM_PASSWORD!=""}
{$lang.FORUM_REGISTRATION}{$FORUM_PASSWORD}
{/if}

{if !empty($URL)}
{$lang.REG_ABOUT_ACTIVATION}:
{$URL}
{/if}

{$lang.REG_ENDLETTER}

{if !empty($GAMEURL)}
{$lang.GAME_LINK} {$GAMEURL}
{/if}