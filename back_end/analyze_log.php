<?php
$file = 'temp_debug.log';
$search = "back_end.cache";
$handle = fopen($file, "r");
$lineNumber = 0;
$outputFile = 'log_analysis_result.txt';
$result = "";
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $lineNumber++;
        if (strpos($line, $search) !== false) {
            $result .= "Match on line $lineNumber: $line";
            for ($i = 0; $i < 50; $i++) {
                $result .= fgets($handle);
            }
            break; 
        }
    }
    fclose($handle);
}
file_put_contents($outputFile, $result);
