<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/10 0010
 * Time: 21:23
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
