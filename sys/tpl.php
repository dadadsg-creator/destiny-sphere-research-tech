<?php
class TPL
{
    public static $inexpandedmode = false;
    public static function render($block, $vars)
    {
      $str = 'templates/'.$block;
      ob_start();
      extract($vars);
      include ('templates/'.$block);
      return ob_get_clean();
    }

    public static function showTree($menu, $expand=0)
    {
      echo "<ul style='color:white'>"; 
      foreach ($menu as $el)
      {
        echo "<li><a href='?r={$el['val'][0]}'>".$el['val'][1].'</a>';
        if ( (($expand==$el['val'][0])  || (self::$inexpandedmode==true)) && (count($el['children'])>0) )
        {
         self::$inexpandedmode = true;
         self::showTree($el['children']);
        }
        if ($expand==$el['val'][0])
          self::$inexpandedmode = false;
      }
      echo "</ul>\n";
    }
}
?>