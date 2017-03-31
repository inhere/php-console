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
    const PROGRESS_TEXT = 1;
    const PROGRESS_BAR = 2;

    private static $fileSize = null;
    private static $showType = 1;

    /*
     progressBar() OUT:
    Connected...
    Mime-type: text/html; charset=utf-8
    Being redirected to: http://no2.php.net/distributions/php-5.2.5.tar.bz2
    Connected...
    FileSize: 7773024
    Mime-type: application/octet-stream
    [========================================>                                                           ] 40% (3076/7590 kb)
     */

    /**
     * @param int $notifyCode       stream notify code
     * @param int $severity         severity code
     * @param string $message       Message text
     * @param int $messageCode      Message code
     * @param int $transferredBytes Have been transferred bytes
     * @param int $maxBytes         Target max length bytes
     */
    protected static function progressShow($notifyCode, $severity, $message, $messageCode, $transferredBytes, $maxBytes)
    {
        $msg = '';

        switch($notifyCode) {
            case STREAM_NOTIFY_RESOLVE:
            case STREAM_NOTIFY_AUTH_REQUIRED:
            case STREAM_NOTIFY_COMPLETED:
            case STREAM_NOTIFY_FAILURE:
            case STREAM_NOTIFY_AUTH_RESULT:
                $msg = "NOTIFY: $message(NO: $messageCode, Severity: $severity)";
                /* Ignore */
                break;

            case STREAM_NOTIFY_REDIRECTED:
                $msg = "Being redirected to: $message";
                break;

            case STREAM_NOTIFY_CONNECT:
                $msg = 'Connected ...';
                break;

            case STREAM_NOTIFY_FILE_SIZE_IS:
                self::$fileSize = $maxBytes;
                $fileSize = sprintf('%2d',$maxBytes/1024);
                $msg = "Got the file size: <info>$fileSize</info> kb";
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                $msg = "Found the mime-type: <info>$message</info>";
                break;

            case STREAM_NOTIFY_PROGRESS:
                if ($transferredBytes > 0) {
                    self::showProgressByType($transferredBytes);
                }

                break;
        }

        $msg && Show::write($msg);
    }

    /**
     * @param $transferredBytes
     * @return string
     */
    protected static function showProgressByType($transferredBytes)
    {
        if ($transferredBytes <= 0) {
            return '';
        }

        $tfKb = $transferredBytes/1024;

        if ( self::$showType === self::PROGRESS_BAR ) {
            $size = self::$fileSize;

            if ( $size === null ) {
                printf("\rUnknown file size... %2d kb done..", $tfKb);
            } else {
                $length = ceil(($transferredBytes/$size)*100); // ■ =
                printf("\r[%-100s] %d%% (%2d/%2d kb)", str_repeat('=', $length). '>', $length, $tfKb, $size/1024);
            }

        } else {
            printf("\r\rMade some progress, downloaded %2d kb so far", $tfKb);
            //$msg = "Made some progress, downloaded <info>$transferredBytes</info> so far";
        }

        return '';
    }

    /*
     progressText() OUT:
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

    /**
     * @param int $notifyCode       stream notify code
     * @param int $severity         severity code
     * @param string $message       Message text
     * @param int $messageCode      Message code
     * @param int $transferredBytes Have been transferred bytes
     * @param int $maxBytes         Target max length bytes
     */
    protected static function progressText($notifyCode, $severity, $message, $messageCode, $transferredBytes, $maxBytes)
    {
        $msg = '';
        switch($notifyCode) {
            case STREAM_NOTIFY_RESOLVE:
            case STREAM_NOTIFY_AUTH_REQUIRED:
            case STREAM_NOTIFY_COMPLETED:
            case STREAM_NOTIFY_FAILURE:
            case STREAM_NOTIFY_AUTH_RESULT:
                // var_dump($notifyCode, $severity, $message, $messageCode, $transferredBytes, $maxBytes);
                $msg = "NOTIFY: $message(NO: $messageCode, Severity: $severity)";
                /* Ignore */
                break;

            case STREAM_NOTIFY_REDIRECTED:
                $msg = "Being redirected to: $message";
                break;

            case STREAM_NOTIFY_CONNECT:
                $msg = 'Connected...';
                break;

            case STREAM_NOTIFY_FILE_SIZE_IS:
                $fileSize = sprintf('%2d',$maxBytes/1024);
                $msg = "Got the file size: <info>$fileSize</info> kb";
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                $msg = "Found the mime-type: <info>$message</info>";
                break;

            case STREAM_NOTIFY_PROGRESS:
                if ( $transferredBytes > 0 ) {
                    printf("\r\rMade some progress, downloaded %2d kb so far", $transferredBytes/1024);
                    //$msg = "Made some progress, downloaded <info>$transferredBytes</info> so far";
                }
                break;
        }

        $msg && Show::write($msg);
    }

    /**
     * eg: php down.php <http://example.com/file> <localFile>
     * @param string $url
     * @param string $saveAs
     * @param int    $type
     */
    public static function down($url, $saveAs, $type = self::PROGRESS_TEXT)
    {
        self::$showType = (int)$type;
        $ctx = stream_context_create();
        stream_context_set_params($ctx, [
            // register stream notification callback
            'notification' => [ self::class, 'progressShow']
        ]);

        Show::write("Download: $url\nSave As: $saveAs \n");

        $fp = fopen($url, 'rb', false, $ctx);

        if (is_resource($fp) && file_put_contents($saveAs, $fp)) {
            self::$fileSize = null;

            Show::write("\nDone!", true, 0);
        }

        self::$fileSize = null;
        $err = error_get_last();
        Show::error("\nErr.rrr..orr...\n {$err['message']}\n", 1);
    }

}
