<?php declare(strict_types=1);

// $text = 'test message';
// $setCodes = [32];
// $unsetCodes = [39];
// $text = sprintf("\033[%sm%s\033[%sm\n", implode(';', $setCodes), $text, implode(';', $unsetCodes));
// var_dump(chr(27));
// echo $text;


passthru("echo \033[32;40;1;4mfoo\033[39;49;22;24m");
passthru('echo -e \033[32;40;1;4mfoo\033[39;49;22;24m');
passthru("printf '\033[32;40;1;4mfoo\033[39;49;22;24m'");
echo "\n";

// $cmdFile = dirname(__DIR__, 2) . '/src/BuiltIn/Resources/cmd/SetEscChar.cmd';
// echo $cmdFile . "\n";

// exec("call $cmdFile");
system('echo %ESC%[1;33;40m Yellow on black %ESC%[0m');

// system('set TEST=val1 && echo %TEST%');
// passthru('set TEST=val2');
// passthru('echo %TEST%');

fwrite(STDOUT, "\033[32;40;1;4mfoo\033[39;49;22;24m");
fwrite(STDOUT, "\x1b[32;40;1;4mfoo\x1b[39;49;22;24m");
// fflush(STDOUT);
