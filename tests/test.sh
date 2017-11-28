#!/bin/bash

# phpunit6.phar --colors --coverage-html ./coverage/
phpunit6.phar --colors --bootstrap tests/boot.php tests
