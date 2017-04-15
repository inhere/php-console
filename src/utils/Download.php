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
    const PROGRESS_TEXT = 'text';
    const PROGRESS_BAR = 'bar';

    /**
     * @var int
     */
    private $fileSize;

    /**
     * @var int
     */
    private $showType;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    private $saveAs;


    /**
     * eg: php down.php <http://example.com/file> <localFile>
     * @param string $url
     * @param string $saveAs
     * @param string $type
     * @return Download
     */
    public static function down(string $url, string $saveAs, string $type = self::PROGRESS_TEXT)
    {
        $d = new self($url, $saveAs, $type);

        return $d->start();
    }

    /**
     * Download constructor.
     * @param string $url
     * @param string $saveAs
     * @param string $type
     */
    public function __construct(string $url, string $saveAs, $type = self::PROGRESS_TEXT)
    {
        $this->url = $url;
        $this->saveAs = $saveAs;
        $this->showType = $type === self::PROGRESS_BAR ? self::PROGRESS_BAR : self::PROGRESS_TEXT;
    }

    public function start()
    {
        if (!$this->url || !$this->saveAs) {
            Show::error("Please the property 'url' and 'saveAs'.", 1);
        }

        $ctx = stream_context_create();

        // register stream notification callback
        stream_context_set_params($ctx, [
            'notification' => [ $this, 'progressShow']
        ]);

        Show::write("Download: {$this->url}\nSave As: {$this->saveAs}\n");

        $fp = fopen($this->url, 'rb', false, $ctx);

        if (is_resource($fp) && file_put_contents($this->saveAs, $fp)) {
            Show::write("\nDone!");
        } else {
            $err = error_get_last();
            Show::error("\nErr.rrr..orr...\n {$err['message']}\n", 1);
        }

        $this->fileSize = null;

        return $this;
    }

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
    protected function progressShow($notifyCode, $severity, $message, $messageCode, $transferredBytes, $maxBytes)
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
                $this->fileSize = $maxBytes;
                $fileSize = sprintf('%2d',$maxBytes/1024);
                $msg = "Got the file size: <info>$fileSize</info> kb";
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                $msg = "Found the mime-type: <info>$message</info>";
                break;

            case STREAM_NOTIFY_PROGRESS:
                if ($transferredBytes > 0) {
                    $this->showProgressByType($transferredBytes);
                }

                break;
        }

        $msg && Show::write($msg);
    }

    /**
     * @param $transferredBytes
     * @return string
     */
    protected function showProgressByType($transferredBytes)
    {
        if ($transferredBytes <= 0) {
            return '';
        }

        $tfKb = $transferredBytes/1024;

        if ($this->showType === self::PROGRESS_BAR) {
            $size = $this->fileSize;

            if ($size === null) {
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

    /**
     * @return int
     */
    public function getShowType(): int
    {
        return $this->showType;
    }

    /**
     * @param int $showType
     */
    public function setShowType(int $showType)
    {
        $this->showType = $showType;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getSaveAs(): string
    {
        return $this->saveAs;
    }

    /**
     * @param string $saveAs
     */
    public function setSaveAs(string $saveAs)
    {
        $this->saveAs = $saveAs;
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


}
