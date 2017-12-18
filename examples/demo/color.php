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





