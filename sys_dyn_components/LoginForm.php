
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr>
                                                        <td colspan=3 align=center>
                                                            <img src="img/graph/label_enter_in_game.gif" border=0></td>
                                                    </tr>
                                                    <tr>
                                                        <td width=4 height=4><img src="img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                                        <td width=100% height=4 background="img/border/02/t.gif"></td>
                                                        <td width=4 height=4><img src="img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                                    </tr>
                                                    <tr>
                                                    <?php
                                                        if(isset($_POST['rambler_login']))
                                                        {
                                                    ?>
                                                        <form action="ds/index_login.php" method="POST" name=login_form>
                                                        <input type="hidden" name="rambler_id_login" value="1">
                                                        <td width=4 background="img/border/02/l.gif"></td>
                                                        <td width=100% height=100% class=wnd_inner>
                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                <tr><td height=100% class=wnd_gen_txt align="center"><input type="submit" value="Войти в игру" class=input_button></td></tr>
                                                            </table>
                                                        </td>
                                                        <td width=4 background="img/border/02/r.gif"></td>
                                                        </form>
                                                    <?php       
                                                        } else {
                                                    ?>          
                                                        <form action="ds/index_login.php" method="POST" name=login_form>
                                                        <td width=4 background="img/border/02/l.gif"></td>
                                                        <td width=100% class=wnd_inner>
                                                            <table width=100% border=0 cellpadding=0 cellspacing=0>
                                                                <tr><td class=wnd_gen_txt>Имя игрока:</td><td rowspan=4 align=center valign=bottom><input type="hidden" name="enter" value="Login"><input type="image" src="img/buttons/but_login.gif" border="0" alt="Войти в игру"></td></tr>
                                                                <tr><td><input type="text" name="login" size="7" class=input_login_wnd></td></tr>
                                                                <tr><td class=wnd_gen_txt>Пароль:</td></tr>
                                                                <tr><td><input type="password" name="pass" size="7" class=input_login_wnd></td></tr>
                                                            </table>
                                                        </td>
                                                        <td width=4 background="img/border/02/r.gif"></td>
                                                        </form>
                                                    <?php
                                                        }
                                                    ?>                                                                                                      
                                                    </tr>
                                                    <tr>
                                                        <td width=4 height=4><img src="img/border/02/b_l.gif" border=0 width=4 height=4></td>
                                                        <td width=100% height=4 background="img/border/02/b.gif"></td>
                                                        <td width=4 height=4><img src="img/border/02/b_r.gif" border=0 width=4 height=4></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan=3 align=left>
                                                            <a href="ds/remaind_pass.php" class=wnd_gen_txt>Я забыл пароль!</td>
                                                    </tr>
                                                </table>                            