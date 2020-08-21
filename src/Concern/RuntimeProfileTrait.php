<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-18
 * Time: 13:10
 */

namespace Inhere\Console\Concern;

use InvalidArgumentException;
use Toolkit\Stdlib\Helper\PhpHelper;
use function array_pop;
use function explode;
use function in_array;
use function memory_get_usage;
use function microtime;

/**
 * Trait RuntimeProfileTrait
 *
 * @package Inhere\Library\Concern
 */
trait RuntimeProfileTrait
{
    /**
     * profile data
     *
     * @var array
     */
    private static $profiles = [];

    /**
     * @var array
     * [
     *  profileKey0,
     *  profileKey1,
     *  profileKey2,
     *  ...
     * ]
     */
    private static $keyQueue = [];

    /**
     * mark data analysis start
     *
     * @param        $name
     * @param array  $context
     * @param string $category
     *
     * @throws InvalidArgumentException
     */
    public static function profile($name, array $context = [], $category = 'application'): void
    {
        $data = [
            '_profile_stats' => [
                'startTime' => microtime(true),
                'startMem'  => memory_get_usage(),
            ],
            '_profile_start' => $context,
            '_profile_end'   => null,
            '_profile_msg'   => null,
        ];

        $profileKey = $category . '|' . $name;

        if (in_array($profileKey, self::$keyQueue, 1)) {
            throw new InvalidArgumentException("Your added profile name [$name] have been exists!");
        }

        self::$keyQueue[]                 = $profileKey;
        self::$profiles[$category][$name] = $data;
    }

    /**
     * mark data analysis end
     *
     * @param string|null $msg
     * @param array       $context
     *
     * @return bool|array
     */
    public static function profileEnd(string $msg = null, array $context = [])
    {
        if (!$latestKey = array_pop(self::$keyQueue)) {
            return false;
        }

        [$category, $name] = explode('|', $latestKey);

        if (isset(self::$profiles[$category][$name])) {
            $data = self::$profiles[$category][$name];

            $old                    = $data['_profile_stats'];
            $data['_profile_stats'] = PhpHelper::runtime($old['startTime'], $old['startMem']);
            $data['_profile_end']   = $context;
            $data['_profile_msg']   = $msg;

            // $title = $category . ' - ' . ($title ?: $name);

            self::$profiles[$category][$name] = $data;
            // self::$log(Logger::DEBUG, $title, $data);

            return $data;
        }

        return false;
    }

    /**
     * @param null|string $name
     * @param string      $category
     *
     * @return array
     */
    public static function getProfileData(string $name = null, string $category = 'application'): array
    {
        if ($name) {
            return self::$profiles[$category][$name] ?? [];
        }

        if ($category) {
            return self::$profiles[$category] ?? [];
        }

        return self::$profiles;
    }

    public function clearProfileData(): void
    {
        self::$profiles = [];
        self::$keyQueue = [];
    }
}
