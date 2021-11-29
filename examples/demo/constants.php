<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

// Boolean true constants
const yes     = true;
const ok      = true;
const okay    = true;
const ✔       = true;
const correct = true;
const 👍      = true;

// Boolean false constants
const no    = false;
const not   = false;
const ✘     = false;
const wrong = false;
const 👎    = false;

// Constants with a random boolean value
define('maybe', (bool)random_int(0, 1));
define('perhaps', (bool)random_int(0, 1));
define('possibly', (bool)random_int(0, 2));
define('unlikely', random_int(0, 99) < 20);
