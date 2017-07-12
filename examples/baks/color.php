<?php

// $text = 'test message';
// $setCodes = [32];
// $unsetCodes = [39];
// $text = sprintf("\033[%sm%s\033[%sm\n", implode(';', $setCodes), $text, implode(';', $unsetCodes));
// var_dump(chr(27));
// echo $text;
system("echo -e '\033[32;40;1;4mfoo\033[39;49;22;24m'");
system("printf '\033[32;40;1;4mfoo\033[39;49;22;24m'");
fwrite(STDOUT, "\033[32;40;1;4mfoo\033[39;49;22;24m");
fflush(STDOUT);

function execInBackground($cmd)
{
    if (substr(php_uname(), 0, 7) == "Windows") {
        pclose(popen("start /B " . $cmd, "r"));
    } else {
        exec($cmd . " > /dev/null &");
    }
}
function GetProgCpuUsage($program)
{
    if (!$program) {
        return -1;
    }

    $c_pid = exec("ps aux | grep " . $program . " | grep -v grep | grep -v su | awk {'print $3'}");
    return $c_pid;
}

function GetProgMemUsage($program)
{
    if (!$program) {
        return -1;
    }

    $c_pid = exec("ps aux | grep " . $program . " | grep -v grep | grep -v su | awk {'print $4'}");
    return $c_pid;
}

// If you want to download files from a linux server with a filesize bigger than 2GB you can use the following
function serveFile($file, $as)
{
    header('Expires: Mon, 1 Apr 1974 05:00:00 GMT');
    header('Pragma: no-cache');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Description: File Download');
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . trim(`stat -c%s "$file"`));
    header('Content-Disposition: attachment; filename="' . $as . '"');
    header('Content-Transfer-Encoding: binary');
    //@readfile( $file );

    flush();
    $fp = popen("tail -c " . trim(`stat -c%s "$file"`) . " " . $file . ' 2>&1', "r");
    while (!feof($fp)) {
        // send the current file part to the browser
        print fread($fp, 1024);
        // flush the content to the browser
        flush();
    }
    fclose($fp);
}
