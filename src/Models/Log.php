<?php

namespace MichaelDrennen\Geonames\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Log
 * @package MichaelDrennen\Geonames\Models
 * TODO perhaps write errors to a flat file as well, incase the error is database related... right?
 */
class Log extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'geonames_logs';

    const ERROR        = 'error';
    const MODIFICATION = 'modification';
    const INSERT       = 'insert';
    const INFO         = 'info';

    const url     = 'url';
    const message = 'message';
    const tag     = 'tag';
    const type    = 'type';

    public $fillable = [
        Log::url,
        Log::message,
        Log::tag,
        Log::type,
    ];


    /**
     * @param string $url        The URL (if relevant) that was the source of this error.
     * @param string $message    A verbose message that we want to save in the log table.
     * @param string $tag        A short string that we can use to query/filter types of messages.
     * @param string $connection Passed in by the commands. Necessary if you are installing on a specific db connection.
     *
     * @return Log|Model
     */
    public static function error( $url = '', $message = '', $tag = '', $connection = NULL ) {

        if ( $connection ):
            return Log::on( $connection )->create( [
                                                               Log::url     => $url,
                                                               Log::message => $message,
                                                               Log::tag     => $tag,
                                                               Log::type    => Log::ERROR,
                                                           ] );
        endif;

        return Log::create( [
                                Log::url     => $url,
                                Log::message => $message,
                                Log::tag     => $tag,
                                Log::type    => Log::ERROR,
                            ] );


    }

    /**
     * @param string $url        The URL (if relevant) that was the source of this error.
     * @param string $message    A verbose message that we want to save in the log table.
     * @param string $tag        A short string that we can use to query/filter types of messages.
     * @param string $connection Passed in by the commands. Necessary if you are installing on a specific db connection.
     * @return bool
     */
    public static function modification( $url = '', $message = '', $tag = '', $connection = NULL ) {
        if ( $connection ):
            return Log::on( $connection )->create( [
                                                               Log::url     => $url,
                                                               Log::message => $message,
                                                               Log::tag     => $tag,
                                                               Log::type    => Log::MODIFICATION,
                                                           ] );
        endif;


        return Log::create( [
                                Log::url     => $url,
                                Log::message => $message,
                                Log::tag     => $tag,
                                Log::type    => Log::MODIFICATION,
                            ] );
    }

    /**
     * @param string $url        The URL (if relevant) that was the source of this error.
     * @param string $message    A verbose message that we want to save in the log table.
     * @param string $tag        A short string that we can use to query/filter types of messages.
     * @param string $connection Passed in by the commands. Necessary if you are installing on a specific db connection.
     * @return bool
     */
    public static function insert( $url = '', $message = '', $tag = '', $connection = NULL ) {
        if ( $connection ):
            return Log::on( $connection )->create( [
                                                               Log::url     => $url,
                                                               Log::message => $message,
                                                               Log::tag     => $tag,
                                                               Log::type    => Log::INSERT,
                                                           ] );
        endif;

        return Log::create( [
                                Log::url     => $url,
                                Log::message => $message,
                                Log::tag     => $tag,
                                Log::type    => Log::INSERT,
                            ] );
    }


    /**
     * @param string $url        The URL (if relevant) that was the source of this error.
     * @param string $message    A verbose message that we want to save in the log table.
     * @param string $tag        A short string that we can use to query/filter types of messages.
     * @param string $connection Passed in by the commands. Necessary if you are installing on a specific db connection.
     * @return mixed
     */
    public static function info( $url = '', $message = '', $tag = '', $connection = NULL ) {
        if ( $connection ):
            return Log::on( $connection )->create( [
                                                               Log::url     => $url,
                                                               Log::message => $message,
                                                               Log::tag     => $tag,
                                                               Log::type    => Log::INFO,
                                                           ] );
        endif;
        return Log::create( [
                                Log::url     => $url,
                                Log::message => $message,
                                Log::tag     => $tag,
                                Log::type    => Log::INFO,
                            ] );
    }
}
