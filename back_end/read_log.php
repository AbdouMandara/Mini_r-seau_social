<?php
$file = 'storage/logs/laravel.log';
$lines = 50;
$handle = fopen($file, "r");
$linecounter = $lines;
$pos = -2;
$text = [];
while ($linecounter > 0) {
    $t = " ";
    while ($t != "\n") {
        if(fseek($handle, $pos, SEEK_END) == -1) break;
        $t = fgetc($handle);
        $pos --;
    }
    $linecounter --;
    if(feof($handle)) break;
    $text[] = fgets($handle);
}
fclose($handle);
echo implode("", array_reverse($text));
