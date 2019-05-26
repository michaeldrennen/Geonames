<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;
use Curl\Curl;
use Goutte\Client;
use StdClass;
use MichaelDrennen\Geonames\Models\GeonamesDelete;
use MichaelDrennen\Geonames\Models\Geoname;
use MichaelDrennen\Geonames\Models\Log;
use MichaelDrennen\Geonames\Models\GeoSetting;


class UpdateGeonames extends AbstractCommand {

    use GeonamesConsoleTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:update-geonames
    {--connection= : If you want to specify the name of the database connection you want used.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Download the modifications txt file from geonames.org, then update our database.";

    /**
     * The actual file name looks like 'modifications-2017-02-22.txt' which we will set in the constructor.
     *
     * @var string
     */
    protected $modificationsTxtFileNamePrefix = 'modifications-';

    /**
     * Set in the constructor.
     *
     * @var string
     */
    protected $modificationsTxtFileName;
    protected $deletesTxtFileName;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $urlForDownloadList = 'http://download.geonames.org/export/dump/';

    /**
     * @var array
     */
    protected $linksOnDownloadPage = [];

    /**
     * @var float When the update started. Microseconds included.
     */
    protected $startTime;

    /**
     * @var float When the update ended. Microseconds included.
     */
    protected $endTime;

    /**
     * @var float How long the update command took to run. Microseconds included.
     */
    protected $runTime;

    protected $storageDir;


    /**
     * UpdateGeonames constructor.
     *
     * @param \Curl\Curl $curl
     * @param \Goutte\Client $client
     *
     * @throws \Exception
     */
    public function __construct( Curl $curl, Client $client ) {
        parent::__construct();
        $this->curl   = $curl;
        $this->client = $client;

    }


    /**
     * @return bool
     * @throws \Exception
     */
    public function handle() {

        //DB::enableQueryLog();
        ini_set( 'memory_limit', -1 );

        try {
            $this->setDatabaseConnectionName();
            $this->info( "The database connection name was set to: " . $this->connectionName );
            $this->comment( "Testing database connection..." );
            $this->checkDatabase();
            $this->info( "Confirmed database connection set up correctly." );
        } catch ( \Exception $exception ) {
            $this->error( $exception->getMessage() );
            $this->stopTimer();
            return FALSE;
        }

        GeoSetting::init( [ GeoSetting::DEFAULT_COUNTRIES_TO_BE_ADDED ],
                          [ GeoSetting::DEFAULT_LANGUAGES ],
                          GeoSetting::DEFAULT_STORAGE_SUBDIR,
                          $this->connectionName );
        $this->storageDir = GeoSetting::getStorage( $this->connectionName );
        GeoSetting::setStatus( GeoSetting::STATUS_UPDATING, $this->connectionName );
        $this->startTime = (float)microtime( TRUE );
        $this->line( "Starting " . $this->signature );


        // Download the file from geonames.org and save it on local storage.
        $localFilePath = $this->saveRemoteModificationsFile();

        //
        $modificationRows = $this->prepareRowsForUpdate( $localFilePath );

        $bar = $this->output->createProgressBar( count( $modificationRows ) );

        foreach ( $modificationRows as $i => $obj ):
            try {
                $geoname = Geoname::firstOrNew( [ 'geonameid' => $obj->geonameid ] );

                $geoname->name              = $obj->name;
                $geoname->asciiname         = $obj->asciiname;
                $geoname->alternatenames    = $obj->alternatenames;
                $geoname->latitude          = $obj->latitude;
                $geoname->longitude         = $obj->longitude;
                $geoname->feature_class     = $obj->feature_class;
                $geoname->feature_code      = $obj->feature_code;
                $geoname->country_code      = $obj->country_code;
                $geoname->cc2               = $obj->cc2;
                $geoname->admin1_code       = $obj->admin1_code;
                $geoname->admin2_code       = $obj->admin2_code;
                $geoname->admin3_code       = $obj->admin3_code;
                $geoname->admin4_code       = $obj->admin4_code;
                $geoname->population        = $obj->population;
                $geoname->elevation         = $obj->elevation;
                $geoname->dem               = $obj->dem;
                $geoname->timezone          = $obj->timezone;
                $geoname->modification_date = $obj->modification_date;

                if ( !$geoname->isDirty() ) {
                    //$this->info( "Geoname record " . $obj->geonameid . " does not need to be updated." );
                    $bar->advance();
                    continue;
                }

                $saveResult = $geoname->save();

                if ( $saveResult ) {

                    if ( $geoname->wasRecentlyCreated ):
                        Log::insert( '',
                                     "Geoname record " . $obj->geonameid . " was inserted.",
                                     "create",
                                     $this->connectionName );
                    else:
                        Log::modification( '',
                                           "Geoname record [" . $obj->geonameid . "] was updated.",
                                           "update",
                                           $this->connectionName );
                        $this->info( "exited modification without throwing an exception" );
                    endif;

                    $bar->advance();

                } else {
                    Log::error( '',
                                "Unable to updateOrCreate geoname record: [" . $obj->geonameid . "]",
                                'database',
                                $this->connectionName );
                    $bar->advance();
                }

            } catch ( \Exception $e ) {
                Log::error( '',
                            "{" . $e->getMessage() . "} Unable to save the geoname record with id: [" . $obj->geonameid . "]",
                            'database',
                            $this->connectionName );
                $bar->advance();
            }
        endforeach;
        $bar->finish();


        /**
         *
         */
        $this->comment( "\nStarting to delete rows found in the 'deletes' file." );
        $this->processDeletedRows();
        $this->comment( "\nDone deleting rows found in the 'deletes' file." );

        $this->endTime = (float)microtime( TRUE );
        $this->runTime = $this->endTime - $this->startTime;
        Log::info(
            '',
            "Finished updates in " . $localFilePath . " in " . $this->runTime . " seconds.",
            'update',
            $this->connectionName );
        $this->line( "\nFinished " . $this->signature );
        GeoSetting::setStatus( GeoSetting::STATUS_LIVE, $this->connectionName );

        return TRUE;
    }


    /**
     * Given the local path to the modifications file, pull it into an array, and mung the rows so they are ready
     * to be sent to the Laravel model for updates.
     * @param string $absoluteLocalFilePath
     * @return array An array of StdClass objects to be passed to the Laravel model.
     */
    protected function prepareRowsForUpdate( string $absoluteLocalFilePath ): array {
        $modificationRows = file( $absoluteLocalFilePath );

        // An array of StdClass objects to be passed to the Laravel model.
        $geonamesData = [];
        foreach ( $modificationRows as $row ):

            $array = explode( "\t", $row );
            $array = array_map( 'trim', $array );

            $object                 = new StdClass;
            $object->geonameid      = $array[ 0 ];
            $object->name           = $array[ 1 ];
            $object->asciiname      = $array[ 2 ];
            $object->alternatenames = $array[ 3 ];

            // The lat and long fields are decimal (nullable), so if the value in the modifications file is blank, we
            // want the value to be null instead of 0 (zero).
            $object->latitude  = empty( $array[ 4 ] ) ? NULL : number_format( (float)$array[ 4 ], 8 );
            $object->longitude = empty( $array[ 5 ] ) ? NULL : number_format( (float)$array[ 5 ], 8 );

            $object->feature_class = $array[ 6 ];
            $object->feature_code  = $array[ 7 ];
            $object->country_code  = $array[ 8 ];
            $object->cc2           = $array[ 9 ];
            $object->admin1_code   = $array[ 10 ];
            $object->admin2_code   = $array[ 11 ];
            $object->admin3_code   = $array[ 12 ];
            $object->admin4_code   = $array[ 13 ];
            $object->population    = $array[ 14 ];

            // Null is different than zero, which was getting entered when the field was blank.
            $object->elevation = empty( $array[ 15 ] ) ? NULL : $array[ 15 ];
            $object->dem       = empty( $array[ 16 ] ) ? NULL : $array[ 16 ];

            $object->timezone          = $array[ 17 ];
            $object->modification_date = $array[ 18 ];
            $geonamesData[]            = $object;
        endforeach;

        return $geonamesData;
    }


    /**
     * Go to the downloads page on the geonames.org site, download the modifications file, and
     * save it locally.
     *
     * @return string The absolute file path of the local copy of the modifications file.
     * @throws \Exception
     */
    protected function saveRemoteModificationsFile() {
        $this->line( "Downloading the modifications file from geonames.org" );

        // Grab the remote file.
        $this->linksOnDownloadPage      = $this->getAllLinksOnDownloadPage();
        $this->modificationsTxtFileName = $this->filterModificationsLink( $this->linksOnDownloadPage );
        $absoluteUrlToModificationsFile = $this->urlForDownloadList . $this->modificationsTxtFileName;
        $this->curl->get( $absoluteUrlToModificationsFile );


        if ( $this->curl->error ) {
            $this->error( $this->curl->error_code . ':' . $this->curl->error_message );
            Log::error( $absoluteUrlToModificationsFile, $this->curl->error_message, $this->curl->error_code,
                        $this->connectionName );
            throw new \Exception( "Unable to download the file at '" . $absoluteUrlToModificationsFile . "', " . $this->curl->error_message );
        }

        $data = $this->curl->response;
        $this->info( "Downloaded " . $absoluteUrlToModificationsFile );


        // Save it locally
        $localFilePath = GeoSetting::getAbsoluteLocalStoragePath( $this->connectionName ) . DIRECTORY_SEPARATOR . $this->modificationsTxtFileName;
        $bytesWritten  = file_put_contents( $localFilePath, $data );
        if ( $bytesWritten === FALSE ) {
            Log::error( $absoluteUrlToModificationsFile,
                        "Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?",
                        'local',
                        $this->connectionName );
            throw new \Exception( "Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?" );
        }
        $this->info( "Saved modification file to: " . $localFilePath );

        return $localFilePath;
    }


    /**
     * Returns a list of every link (href) on the geonames.org page for downloads.
     *
     * @return array
     */
    protected function getAllLinksOnDownloadPage(): array {
        $crawler = $this->client->request( 'GET', $this->urlForDownloadList );

        return $crawler->filter( 'a' )->each( function ( Crawler $node ) {
            return $node->attr( 'href' );
        } );
    }


    /**
     * The geonames.org link to the modifications file has a different file name every day.
     * This function accepts an array of ALL of the links from the downloads page, and returns
     * the file name of the current modifications file.
     *
     * @param array $links The list of links on the geonames export page.
     *
     * @return string The file name of the current modifications file on the geonames website.
     * @throws \Exception If we can't find the modifications file name in the list of links.
     */
    protected function filterModificationsLink( array $links ): string {
        foreach ( $links as $link ) {
            if ( preg_match( '/^modifications-/', $link ) === 1 ) {
                return $link;
            }
        }
        throw new \Exception( "We were unable to find the modifications file on the geonames site. This is very unusual." );
    }


    protected function processDeletedRows() {

        $this->comment( "\nProcessing deleted rows." );
        // Download the file from geonames.org and save it on local storage.
        $localFilePath    = $this->saveRemoteDeletesFile();
        $dateFromFileName = $this->getDateFromDeletesFileName( $localFilePath );
        $deletionRows     = $this->prepareRowsToRecordDeletes( $localFilePath, $dateFromFileName );
        $this->comment( "There were " . count( $deletionRows ) . " that need to be deleted." );

        $bar = $this->output->createProgressBar( count( $deletionRows ) );

        foreach ( $deletionRows as $obj ):

            try {
                $geonamesDelete = GeonamesDelete::firstOrNew( [
                                                                  'geonameid' => $obj->geonameid,
                                                                  'date'      => $dateFromFileName, ] );

                $geonamesDelete->date   = $obj->date;
                $geonamesDelete->name   = $obj->name;
                $geonamesDelete->reason = $obj->reason;


                if ( !$geonamesDelete->isDirty() ) {
                    //$this->info( "GeonamesDelete record " . $obj->geonameid . " does not need to be updated." );
                    $bar->advance();
                    continue;
                }

                $saveResult = $geonamesDelete->save();

                if ( $saveResult ):

                    if ( $geonamesDelete->wasRecentlyCreated ) {
                        Log::insert(
                            '',
                            "GeonamesDelete record " . $obj->geonameid . " was inserted.",
                            "create",
                            $this->connectionName );
                    } else {
                        Log::modification(
                            '',
                            "GeonamesDelete record " . $obj->geonameid . " was updated.",
                            "update",
                            $this->connectionName );
                    }
                    $bar->advance();


                    $numRecordsDeleted = $this->deleteGeonameRecord( $geonamesDelete->{GeonamesDelete::geonameid} );
                    if ( 1 > $numRecordsDeleted ):
                        $this->info( "Geoname: " . $geonamesDelete->{GeonamesDelete::geonameid} . " was deleted." );
                    else:
                        $this->comment( "Geoname: " . $geonamesDelete->{GeonamesDelete::geonameid} . " has already been deleted." );
                    endif;


                else:
                    Log::error(
                        '',
                        "Unable to updateOrCreate GeonamesDelete record: [" . $obj->geonameid . "]",
                        'database',
                        $this->connectionName );
                    $bar->advance();
                    continue;
                endif;

            } catch ( \Exception $e ) {
                Log::error( '',
                            $e->getMessage() . " Unable to save the GeonamesDelete record with id: [" . $obj->geonameid . "]",
                            'database',
                            $this->connectionName );
                $bar->advance();
            }
        endforeach;


        //$this->comment( "Done processing deleted rows." );

    }

    /**
     * @param int $geonameid
     * @return int
     */
    protected function deleteGeonameRecord( int $geonameid ) {
        $this->comment( "Deleting geonameid: " . $geonameid );
        return Geoname::destroy( $geonameid );
    }

    protected function filterDeletesLink( array $links ): string {
        foreach ( $links as $link ) {
            if ( preg_match( '/^deletes-/', $link ) === 1 ) {
                return $link;
            }
        }
        throw new \Exception( "We were unable to find the modifications file on the geonames site. This is very unusual." );
    }

    protected function saveRemoteDeletesFile() {
        $this->line( "Downloading the deletes file from geonames.org" );

        // Grab the remote file.
        $this->linksOnDownloadPage = $this->getAllLinksOnDownloadPage();
        $this->deletesTxtFileName  = $this->filterDeletesLink( $this->linksOnDownloadPage );
        $absoluteUrlToDeletesFile  = $this->urlForDownloadList . $this->deletesTxtFileName;
        $this->curl->get( $absoluteUrlToDeletesFile );


        if ( $this->curl->error ) {
            $this->error( $this->curl->error_code . ':' . $this->curl->error_message );
            Log::error( $absoluteUrlToDeletesFile, $this->curl->error_message, $this->curl->error_code,
                        $this->connectionName );
            throw new \Exception( "Unable to download the file at '" . $absoluteUrlToDeletesFile . "', " . $this->curl->error_message );
        }

        $data = $this->curl->response;
        $this->info( "Downloaded " . $absoluteUrlToDeletesFile );


        // Save it locally
        $localFilePath = GeoSetting::getAbsoluteLocalStoragePath( $this->connectionName ) . DIRECTORY_SEPARATOR . $this->deletesTxtFileName;
        $bytesWritten  = file_put_contents( $localFilePath, $data );
        if ( $bytesWritten === FALSE ) {
            Log::error( $absoluteUrlToDeletesFile,
                        "Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?",
                        'local',
                        $this->connectionName );
            throw new \Exception( "Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?" );
        }
        $this->info( "Saved deletes file to: " . $localFilePath );

        return $localFilePath;
    }

    /**
     * @param string $absoluteLocalFilePath
     * @param Carbon $dateFromFileName
     * @return array
     */
    protected function prepareRowsToRecordDeletes( string $absoluteLocalFilePath, Carbon $dateFromFileName ): array {
        $deletionRows = file( $absoluteLocalFilePath );

        // An array of StdClass objects to be passed to the Laravel model.
        $deleteRecords = [];
        foreach ( $deletionRows as $row ):

            $array = explode( "\t", $row );
            $array = array_map( 'trim', $array );

            $object            = new StdClass;
            $object->geonameid = $array[ 0 ];
            $object->name      = $array[ 1 ];
            $object->reason    = $array[ 2 ];
            $object->date      = $dateFromFileName->toDateString();

            $deleteRecords[] = $object;
        endforeach;

        return $deleteRecords;
    }

    /**
     * @param string $fileNameOfDeletesFile
     * @return Carbon
     * @throws \Exception
     * @link http://php.net/manual/en/function.preg-match.php
     */
    protected function getDateFromDeletesFileName( string $fileNameOfDeletesFile ): Carbon {
        $matches = [];
        $pattern = '/deletes-(\d{4}-\d{2}-\d{2})\.txt$/';
        $result  = preg_match( $pattern, $fileNameOfDeletesFile, $matches );
        if ( FALSE === $result ) {
            throw new \Exception( "There was an error running preg_match() in getDateFromDeletesFileName() on the string [" . $fileNameOfDeletesFile . "]" );
        } elseif ( 0 === $result ) {
            throw new \Exception( "A date couldn't be found by preg_match() in getDateFromDeletesFileName() on the string [" . $fileNameOfDeletesFile . "]" );
        }
        // $matches[1] will have the text that matched the first captured parenthesized subpattern
        return Carbon::parse( $matches[ 1 ] );
    }

}