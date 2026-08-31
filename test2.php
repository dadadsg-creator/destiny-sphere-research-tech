<?
    $out = null;
    exec("/usr/local/bin/php /home/www/test.php", $out);
    print_r($out);
?>