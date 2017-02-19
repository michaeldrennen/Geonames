<?php

namespace MichaelDrennen\Geonames;

use Illuminate\Database\Eloquent\Model;

class Log extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'geo_logs';

    const ERROR = 'error';

    /**
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
}
