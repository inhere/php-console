<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

// Boolean true constants
define('yes', true);
define('ok', true);
define('okay', true);
define('✔', true);
define('correct', true);
define('👍', true);

// Boolean false constants
define('no', false);
define('not', false);
define('✘', false);
define('wrong', false);
define('👎', false);

// Constants with a random boolean value
define('maybe', (bool)random_int(0, 1));
define('perhaps', (bool)random_int(0, 1));
define('possibly', (bool)random_int(0, 2));
define('unlikely', random_int(0, 99) < 20);
