<?php

namespace MichaelDrennen\Geonames\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'geonames_logs';

    const ERROR = 'error';
    const MODIFICATION = 'modification';
    const INSERT = 'insert';
    const INFO = 'info';


    /**
     * @param string $url The URL (if relevant) that was the source of this error.
     * @param string $message A verbose message that we want to save in the log table.
     * @param string $tag A short string that we can use to query/filter types of messages.
     * @return bool
     */
    public static function error($url = '', $message = '', $tag = '') {
        $log = new Log();
        $log->url = $url;
        $log->message = $message;
        $log->tag = $tag;
        $log->type = self::ERROR;
        return $log->save();
    }

    /**
     * @param string $url
     * @param string $message
     * @param string $tag
     * @return bool
     */
    public static function modification($url = '', $message = '', $tag = '') {
        $log = new Log();
        $log->url = $url;
        $log->message = $message;
        $log->tag = $tag;
        $log->type = self::MODIFICATION;
        return $log->save();
    }

    /**
     * @param string $url
     * @param string $message
     * @param string $tag
     * @return bool
     */
    public static function insert($url = '', $message = '', $tag = '') {
        $log = new Log();
        $log->url = $url;
        $log->message = $message;
        $log->tag = $tag;
        $log->type = self::INSERT;
        return $log->save();
    }

    public static function info($url = '', $message = '', $tag = '') {
        $log = new Log();
        $log->url = $url;
        $log->message = $message;
        $log->tag = $tag;
        $log->type = self::INFO;
        return $log->save();
    }
}
