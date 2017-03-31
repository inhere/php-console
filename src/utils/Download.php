<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-31
 * Time: 19:08
 */

namespace inhere\console\utils;

/**
 * Class Download
 * @package inhere\console\utils
 */
class Download
{
    /*
     OUT:
    Connected...
    Mime-type: text/html; charset=utf-8
    Being redirected to: http://no2.php.net/distributions/php-5.2.5.tar.bz2
    Connected...
    Filesize: 7773024
    Mime-type: application/octet-stream
    [========================================>                                                           ] 40% (3076/7590 kb)
     */

    /**
     * @param $code
     * @param $severity
     * @param $message
     * @param $messageCode
     * @param $transferredBytes
     * @param $maxBytes
     */
    public static function stream_notification_callback($code, $severity, $message, $messageCode, $transferredBytes, $maxBytes)
    {
        static $fileSize = null;

        switch($code) {
            case STREAM_NOTIFY_RESOLVE:
            case STREAM_NOTIFY_AUTH_REQUIRED:
            case STREAM_NOTIFY_COMPLETED:
            case STREAM_NOTIFY_FAILURE:
            case STREAM_NOTIFY_AUTH_RESULT:
                /* Ignore */
                break;

            case STREAM_NOTIFY_REDIRECTED:
                echo "Being redirected to: ", $message, "\n";
                break;

            case STREAM_NOTIFY_CONNECT:
                echo "Connected...\n";
                break;

            case STREAM_NOTIFY_FILE_SIZE_IS:
                $fileSize = $maxBytes;
                echo "FileSize: ", $fileSize, "\n";
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                echo "Mime-type: ", $message, "\n";
                break;

            case STREAM_NOTIFY_PROGRESS:
                if ($transferredBytes > 0) {
                    if (!isset($fileSize)) {
                        printf("\rUnknown fileSize.. %2d kb done..", $transferredBytes/1024);
                    } else {
                        $length = (int)(($transferredBytes/$fileSize)*100);
                        printf("\r[%-100s] %d%% (%2d/%2d kb)", str_repeat("=", $length). ">", $length, ($transferredBytes/1024), $fileSize/1024);
                    }
                }
                
                break;
        }
    }

    /*
     OUT:
    Connected...
    Found the mime-type: text/html; charset=utf-8
    Being redirected to: http://no.php.net/contact
    Connected...
    Got the fileSize: 0
    Found the mime-type: text/html; charset=utf-8
    Being redirected to: http://no.php.net/contact.php
    Connected...
    Got the fileSize: 4589
    Found the mime-type: text/html;charset=utf-8
    Made some progress, downloaded 0 so far
    Made some progress, downloaded 0 so far
    Made some progress, downloaded 0 so far
    Made some progress, downloaded 1440 so far
    ... ...
     */
    public static function stream_notification_callback1($code, $severity, $message, $messageCode, $transferredBytes, $maxBytes)
    {
        switch($code) {
            case STREAM_NOTIFY_RESOLVE:
            case STREAM_NOTIFY_AUTH_REQUIRED:
            case STREAM_NOTIFY_COMPLETED:
            case STREAM_NOTIFY_FAILURE:
            case STREAM_NOTIFY_AUTH_RESULT:
                var_dump($code, $severity, $message, $messageCode, $transferredBytes, $maxBytes);
                /* Ignore */
                break;

            case STREAM_NOTIFY_REDIRECTED:
                echo "Being redirected to: ", $message;
                break;

            case STREAM_NOTIFY_CONNECT:
                echo "Connected...";
                break;

            case STREAM_NOTIFY_FILE_SIZE_IS:
                echo "Got the fileSize: ", $maxBytes;
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                echo "Found the mime-type: ", $message;
                break;

            case STREAM_NOTIFY_PROGRESS:
                echo "Made some progress, downloaded ", $transferredBytes, " so far";
                break;
        }
        echo "\n";
    }

    // php down.php <http://example.com/file> <localFile>
    public static function down($url, $saveTo, $type = 1)
    {
        $ctx = stream_context_create();
        stream_context_set_params($ctx, [
            "notification" => "stream_notification_callback"
        ]);

        $fp = fopen($url, "r", false, $ctx);

        if (is_resource($fp) && file_put_contents($saveTo, $fp)) {
            echo "\nDone!\n";
            exit(0);
        }

        $err = error_get_last();
        echo "\nErrrrrorr...\n", $err["message"], "\n";
        exit(1);
    }

}