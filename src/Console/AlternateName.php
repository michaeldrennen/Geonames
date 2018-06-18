<?php

namespace MichaelDrennen\Geonames\Console;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MichaelDrennen\Geonames\Models\GeoSetting;
use MichaelDrennen\Geonames\Models\Log;
use MichaelDrennen\LocalFile\LocalFile;
use MichaelDrennen\Geonames\Models\AlternateNamesWorking;

/**
 * Class AlternateName
 *
 * @package MichaelDrennen\Geonames\Console
 */
class AlternateName extends Command {

    use GeonamesConsoleTrait;

    const LINES_PER_SPLIT_FILE = 5000;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:alternate-name 
        {--connection= : If you want to specify the name of the database connection you want used.}
        {--test : If you want to test the command on a small countries data set.}
        {--country=* : Add the 2 character code for each country. Add additional countries with additional "--country=" options on the command line.}    ';

    /**
     * @var string The console command description.
     */
    protected $description = "Populate the alternate_names table.";

    /**
     *
     */
    const REMOTE_FILE_NAME_FOR_ALL = 'alternateNames.zip';

    /**
     *
     */
    const LOCAL_ALTERNATE_NAMES_FILE_NAME_FOR_ALL = 'alternateNames.txt';


    /**
     * The name of our alternate names table in our database. Using constants here, so I don't need
     * to worry about typos in my code. My IDE will warn me if I'm sloppy.
     */
    const TABLE = 'geonames_alternate_names';

    /**
     * The name of our temporary/working table in our database.
     */
    const TABLE_WORKING = 'geonames_alternate_names_working';


    /**
     * Initialize constructor.
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * Execute the console command. The zip file this command downloads has two files in it. The alternate names, and
     * iso language codes file. The iso language codes file is available as a separate download, so for simplicity's
     * sake I handle the iso language code download and insertion in another console command.
     *
     * @return bool
     * @throws Exception
     */
    public function handle() {
        ini_set( 'memory_limit', -1 );

        $this->startTimer();

        //$this->connectionName = $this->option( 'connection' );
        $this->setDatabaseConnectionName();

        if ( $this->option( 'test' ) ):
            $countries = [ 'YU' ];
        else:
            $countries = $this->option( 'country' );
        endif;

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

        try {
            GeoSetting::init(
                $countries,
                GeoSetting::DEFAULT_LANGUAGES,
                GeoSetting::DEFAULT_STORAGE_SUBDIR,
                $this->connectionName );
        } catch ( \Exception $exception ) {
            Log::error( NULL, "Unable to initialize the GeoSetting record.", '', $this->connectionName );
            $this->stopTimer();
            return FALSE;
        }

        $this->initTable();


        $urlsToAlternateNamesZipFiles                   = $this->getAlternateNameDownloadLinks( $countries );
        $absoluteLocalFilePathsOfAlternateNamesZipFiles = [];
        foreach ( $urlsToAlternateNamesZipFiles as $countryCode => $urlsToAlternateNamesZipFile ) {
            try {
                $absoluteLocalFilePathsOfAlternateNamesZipFiles[ $countryCode ] = $this->downloadFile( $this,
                                                                                                       $urlsToAlternateNamesZipFile,
                                                                                                       $this->connectionName );
            } catch ( Exception $e ) {
                $this->error( $e->getMessage() );
                Log::error( $urlsToAlternateNamesZipFiles, $e->getMessage(), 'remote', $this->connectionName );

                return FALSE;
            }
        }
        $this->comment( "Done downloading alternate zip files." );

        foreach ( $absoluteLocalFilePathsOfAlternateNamesZipFiles as $countryCode => $absoluteLocalFilePathOfAlternateNamesZipFile ) {
            try {
                $this->unzip( $absoluteLocalFilePathOfAlternateNamesZipFile, $this->connectionName );
                $this->comment( "Unzipped " . $absoluteLocalFilePathOfAlternateNamesZipFile );
            } catch ( Exception $e ) {
                $this->error( $e->getMessage() );
                Log::error( $absoluteLocalFilePathOfAlternateNamesZipFile, $e->getMessage(), 'local',
                            $this->connectionName );

                return FALSE;
            }

            $absoluteLocalFilePathOfAlternateNamesFile = $this->getLocalAbsolutePathToAlternateNamesTextFile( $countryCode );

            if ( ! file_exists( $absoluteLocalFilePathOfAlternateNamesFile ) ) {
                throw new Exception( "The unzipped file could not be found. We were looking for: " . $absoluteLocalFilePathOfAlternateNamesFile );
            }

            //$this->insertAlternateNamesWithLoadDataInfile( $absoluteLocalFilePathOfAlternateNamesFile );
            $this->insertAlternateNamesWithEloquent( $absoluteLocalFilePathOfAlternateNamesFile );
            //$this->insertAlternateNamesWithEloquentMassInsert( $absoluteLocalFilePathOfAlternateNamesFile );
            //$this->insertAlternateNamesWithLoadDataInfileFromRecreatedFile( $absoluteLocalFilePathOfAlternateNamesFile );
        }

        $this->finalizeTable();

        $this->info( "alternate_names data was downloaded and inserted in " . $this->getRunTime() . " seconds." );
    }

    /**
     * @param array $countryCodes The two character country code, if specified by the user.
     *
     * @return array   The absolute paths to the remote alternate names zip files.
     */
    protected function getAlternateNameDownloadLinks( array $countryCodes = [] ): array {
        if ( empty( $countryCodes ) ):
            return [ '*' => self::$url . self::REMOTE_FILE_NAME_FOR_ALL ];
        endif;

        $alternateNameDownloadLinks = [];
        foreach ( $countryCodes as $i => $countryCode ) {
            $alternateNameDownloadLinks[ $countryCode ] = self::$url . 'alternatenames/' . strtoupper( $countryCode ) . '.zip';
        }

        return $alternateNameDownloadLinks;

    }

    /**
     * This function is used in debugging only. The main block of code has no need for this function, since the
     * downloadFile() function returns this exact path as it's return value. The alternate names file takes a while
     * to download on my slow connection, so I save a copy of it for testing, and use this function to point to it.
     *
     * @return string The absolute local path to the downloaded zip file.
     * @throws \Exception
     */
    protected function getLocalAbsolutePathToAlternateNamesZipFile(): string {
        return GeoSetting::getAbsoluteLocalStoragePath( $this->connectionName ) . DIRECTORY_SEPARATOR . self::REMOTE_FILE_NAME_FOR_ALL;
    }

    /**
     * @param string $countryCode The two character country code that a user can optionally pass in.
     *
     * @return string
     * @throws \Exception
     */
    protected function getLocalAbsolutePathToAlternateNamesTextFile( string $countryCode = NULL ): string {
        if ( '*' == $countryCode || is_null( $countryCode ) ):
            return GeoSetting::getAbsoluteLocalStoragePath( $this->connectionName ) . DIRECTORY_SEPARATOR . self::LOCAL_ALTERNATE_NAMES_FILE_NAME_FOR_ALL;
        endif;
        return GeoSetting::getAbsoluteLocalStoragePath( $this->connectionName ) . DIRECTORY_SEPARATOR . strtoupper( $countryCode ) . '.txt';
    }


    protected function initTable() {
        Schema::connection( $this->connectionName )->dropIfExists( self::TABLE_WORKING );
        DB::connection( $this->connectionName )
          ->statement( 'CREATE TABLE ' . self::TABLE_WORKING . ' LIKE ' . self::TABLE . ';' );
        $this->disableKeys( self::TABLE_WORKING );
    }

    protected function finalizeTable() {
        $this->enableKeys( self::TABLE_WORKING );
        Schema::connection( $this->connectionName )->dropIfExists( self::TABLE );
        Schema::connection( $this->connectionName )->rename( self::TABLE_WORKING, self::TABLE );
    }

    /**
     * @param $localFilePath
     *
     * @returns int The total number of rows inserted.
     * @throws \Exception
     */
    protected function insertAlternateNamesWithLoadDataInfile( $localFilePath ): int {
        $totalRowsInserted = 0;

        try {
            $localFileSplitPaths = LocalFile::split( $localFilePath, self::LINES_PER_SPLIT_FILE, 'split_', NULL );
            $numSplitFiles       = count( $localFileSplitPaths );
        } catch ( Exception $exception ) {
            throw $exception;
        }

        foreach ( $localFileSplitPaths as $i => $localFileSplitPath ):
            $query = "LOAD DATA LOCAL INFILE '" . $localFileSplitPath . "'
            
                        INTO TABLE " . self::TABLE_WORKING . "
                            (   alternateNameId, 
                                geonameid,
                                isolanguage, 
                                alternate_name, 
                                isPreferredName, 
                                isShortName, 
                                isColloquial, 
                                isHistoric,              
                                @created_at, 
                                @updated_at)                            
                        SET created_at=NOW(),updated_at=null
                        ";

            $this->line( "Running the LOAD DATA INFILE query. This could take a good long while." );

            /**
             * @link http://php.net/manual/en/pdo.exec.php
             * PDO::exec() returns the number of rows that were modified or deleted by the SQL statement you issued.
             * If no rows were affected, PDO::exec() returns 0.
             */

            try {
                $rowsInserted = DB::connection( $this->connectionName )->getpdo()->exec( $query );
            } catch ( Exception $exception ) {
                throw new \Exception( "Unable to execute the load data infile query. " . print_r( DB::connection( $this->connectionName )
                                                                                                    ->getpdo()
                                                                                                    ->errorInfo(),
                                                                                                  TRUE ) . " QUERY: " . $query );
            }


            if ( FALSE === $rowsInserted ) {
                Log::error( '', "Unable to load data infile for alternate names.", 'database', $this->connectionName );
                throw new \Exception( "Unable to execute the load data infile query. " . print_r( DB::connection( $this->connectionName )
                                                                                                    ->getpdo()
                                                                                                    ->errorInfo(),
                                                                                                  TRUE ) );
            }
            $this->info( "Inserted file " . ( $i + 1 ) . " of " . $numSplitFiles );
            $totalRowsInserted += $rowsInserted;
        endforeach;

        return $totalRowsInserted;
    }


    protected function insertAlternateNamesWithLoadDataInfileFromRecreatedFile( $localFilePath ): int {
        try {
            $this->comment( "Inserting alternate names using insertAlternateNamesWithLoadDataInfileFromRecreatedFile()" );
            $totalLinesInOriginalFile = LocalFile::lineCount( $localFilePath );
            $this->comment( "Splitting " . $localFilePath );
            $localFileSplitPaths = LocalFile::split( $localFilePath, self::LINES_PER_SPLIT_FILE, 'split_', NULL );
            $numSplitFiles       = count( $localFileSplitPaths );
            $this->comment( "$localFilePath was split into $numSplitFiles files." );
        } catch ( Exception $exception ) {
            throw $exception;
        }

        $geonamesBar = $this->output->createProgressBar( $totalLinesInOriginalFile );
        $geonamesBar->setFormat( "Recreating %message% %current%/%max% [%bar%] %percent:3s%%\n" );


        $totalRowsRecreated  = 0;
        $pathToRecreatedFile = './recreatedFile.txt';
        $recreatedFileHandle = fopen( $pathToRecreatedFile, 'w' );
        foreach ( $localFileSplitPaths as $i => $localFileSplitPath ):
            $geonamesBar->setMessage( "file #" . ( $i + 1 ) . " of " . $numSplitFiles );
            $rows = file( $localFileSplitPath );
            //$numRowsInSplitFile = count( $rows );

            foreach ( $rows as $j => $row ):
                $recreatedFields   = [];
                $fields            = explode( "\t", $row );
                $fields            = array_map( 'trim', $fields );
                $recreatedFields[] = $fields[ 0 ];
                $recreatedFields[] = $fields[ 1 ];
                $recreatedFields[] = empty( $fields[ 2 ] ) ? '' : $fields[ 2 ];
                $recreatedFields[] = empty( $fields[ 3 ] ) ? '' : $fields[ 3 ];
                $recreatedFields[] = empty( $fields[ 4 ] ) ? FALSE : $fields[ 4 ];
                $recreatedFields[] = empty( $fields[ 5 ] ) ? FALSE : $fields[ 5 ];
                $recreatedFields[] = empty( $fields[ 6 ] ) ? FALSE : $fields[ 6 ];
                $recreatedFields[] = empty( $fields[ 7 ] ) ? FALSE : $fields[ 7 ];

                fwrite( $recreatedFileHandle, implode( "\t", $recreatedFields ) . "\n" );
                $totalRowsRecreated++;
                $geonamesBar->advance();
            endforeach;
        endforeach;

        fclose( $recreatedFileHandle );

        $geonamesBar->finish();

        $this->comment( "Running LOAD DATA INFILE." );

        $query = "LOAD DATA LOCAL INFILE '" . $pathToRecreatedFile . "'
            
                        INTO TABLE " . self::TABLE_WORKING . "
                            (   alternateNameId, 
                                geonameid,
                                isolanguage, 
                                alternate_name, 
                                isPreferredName, 
                                isShortName, 
                                isColloquial, 
                                isHistoric,              
                                @created_at, 
                                @updated_at)                            
                        SET created_at=NOW(),updated_at=null
                        ";

        $this->line( "Running the LOAD DATA INFILE query. This could take a good long while." );

        /**
         * @link http://php.net/manual/en/pdo.exec.php
         * PDO::exec() returns the number of rows that were modified or deleted by the SQL statement you issued.
         * If no rows were affected, PDO::exec() returns 0.
         */

        try {
            $rowsInserted = DB::connection( $this->connectionName )->getpdo()->exec( $query );
        } catch ( Exception $exception ) {
            throw new \Exception( "Unable to execute the load data infile query. " . print_r( DB::connection( $this->connectionName )
                                                                                                ->getpdo()
                                                                                                ->errorInfo(),
                                                                                              TRUE ) . " QUERY: " . $query );
        }


        if ( FALSE === $rowsInserted ) {
            Log::error( '', "Unable to load data infile for alternate names.", 'database', $this->connectionName );
            throw new \Exception( "Unable to execute the load data infile query. " . print_r( DB::connection( $this->connectionName )
                                                                                                ->getpdo()
                                                                                                ->errorInfo(), TRUE ) );
        }

        unlink( $pathToRecreatedFile );

        return $totalRowsRecreated;
    }


    /**
     * I have been getting a UTF-8 error when
     *
     * @param $localFilePath
     *
     * @return int
     * @throws \Exception
     */
    protected function insertAlternateNamesWithEloquent( $localFilePath ): int {
        //DB::enableQueryLog();
        $numLines = LocalFile::lineCount( $localFilePath );

        $this->disableKeys( self::TABLE );

        try {
            $this->comment( "Splitting " . $localFilePath );
            $localFileSplitPaths = LocalFile::split( $localFilePath, self::LINES_PER_SPLIT_FILE, 'split_', NULL );
            $numSplitFiles       = count( $localFileSplitPaths );
            $this->comment( "I split $localFilePath into $numSplitFiles split files." );
        } catch ( Exception $exception ) {
            throw $exception;
        }

        $totalRowsInserted = 0;
        foreach ( $localFileSplitPaths as $i => $localFileSplitPath ):
            $rows               = file( $localFileSplitPath );
            $numRowsInSplitFile = count( $rows );
            $this->comment( "Split file #" . ( $i + 1 ) . " of " . $numSplitFiles . " has $numRowsInSplitFile rows." );

            $geonamesBar = $this->output->createProgressBar( $numRowsInSplitFile );
            $geonamesBar->setFormat( "Inserting %message% %current%/%max% [%bar%] %percent:3s%%\n" );
            $geonamesBar->setMessage( 'alternate names' );
            /**
             * alternateNameId   : the id of this alternate name, int
             * geonameid         : geonameId referring to id in table 'geoname', int
             * isolanguage       : iso 639 language code 2- or 3-characters; 4-characters 'post' for postal codes and 'iata','icao' and faac for airport codes, fr_1793 for French Revolution names,  abbr for abbreviation, link to a website (mostly to wikipedia), wkdt for the wikidataid, varchar(7)
             * alternate name    : alternate name or name variant, varchar(400)
             * isPreferredName   : '1', if this alternate name is an official/preferred name
             * isShortName       : '1', if this is a short name like 'California' for 'State of California'
             * isColloquial      : '1', if this alternate name is a colloquial or slang term. Example: 'Big Apple' for 'New York'.
             * isHistoric        : '1', if this alternate name is historic and was used in the past. Example 'Bombay' for 'Mumbai'.
             */
            foreach ( $rows as $j => $row ) {
                $fields = explode( "\t", $row );
                $fields = array_map( 'trim', $fields );

                $alternateNameId = $fields[ 0 ];
                $geonameid       = $fields[ 1 ];
                $isolanguage     = empty( $fields[ 2 ] ) ? '' : $fields[ 2 ];
                $alternate_name  = empty( $fields[ 3 ] ) ? '' : $fields[ 3 ];
                $isPreferredName = empty( $fields[ 4 ] ) ? FALSE : $fields[ 4 ];
                $isShortName     = empty( $fields[ 5 ] ) ? FALSE : $fields[ 5 ];
                $isColloquial    = empty( $fields[ 6 ] ) ? FALSE : $fields[ 6 ];
                $isHistoric      = empty( $fields[ 7 ] ) ? FALSE : $fields[ 7 ];

                $alternateName = \MichaelDrennen\Geonames\Models\AlternateNamesWorking::on( $this->connectionName )
                                                                                      ->firstOrCreate(
                    [ 'alternateNameId' => $alternateNameId ],
                    [
                        'geonameid'       => $geonameid,
                        'isolanguage'     => $isolanguage,
                        'alternate_name'  => $alternate_name,
                        'isPreferredName' => $isPreferredName,
                        'isShortName'     => $isShortName,
                        'isColloquial'    => $isColloquial,
                        'isHistoric'      => $isHistoric,
                    ]
                );

                $geonamesBar->advance();
                $totalRowsInserted++;
            }
            $geonamesBar->finish();
        endforeach;

        $this->enableKeys( self::TABLE );

        return $totalRowsInserted;
    }


    /**
     * I have been getting a UTF-8 error when
     *
     * @param $localFilePath
     *
     * @return int
     * @throws \Exception
     */
    protected function insertAlternateNamesWithEloquentMassInsert( $localFilePath ): int {
        $numLines = LocalFile::lineCount( $localFilePath );

        $this->disableKeys( self::TABLE );

        try {
            $this->comment( "Splitting " . $localFilePath );
            $localFileSplitPaths = LocalFile::split( $localFilePath, self::LINES_PER_SPLIT_FILE, 'split_', NULL );
            $numSplitFiles       = count( $localFileSplitPaths );
            $this->comment( "I split $localFilePath into $numSplitFiles split files." );
        } catch ( Exception $exception ) {
            throw $exception;
        }

        $totalRowsInserted = 0;
        foreach ( $localFileSplitPaths as $i => $localFileSplitPath ):
            $rows               = file( $localFileSplitPath );
            $numRowsInSplitFile = count( $rows );
            $this->comment( "Split file #" . ( $i + 1 ) . " of " . $numSplitFiles . " has $numRowsInSplitFile rows." );

            $geonamesBar = $this->output->createProgressBar( $numRowsInSplitFile );
            $geonamesBar->setFormat( "Inserting %message% %current%/%max% [%bar%] %percent:3s%%\n" );
            $geonamesBar->setMessage( 'alternate names' );
            $splitRowsToInsert = [];
            /**
             * alternateNameId   : the id of this alternate name, int
             * geonameid         : geonameId referring to id in table 'geoname', int
             * isolanguage       : iso 639 language code 2- or 3-characters; 4-characters 'post' for postal codes and 'iata','icao' and faac for airport codes, fr_1793 for French Revolution names,  abbr for abbreviation, link to a website (mostly to wikipedia), wkdt for the wikidataid, varchar(7)
             * alternate name    : alternate name or name variant, varchar(400)
             * isPreferredName   : '1', if this alternate name is an official/preferred name
             * isShortName       : '1', if this is a short name like 'California' for 'State of California'
             * isColloquial      : '1', if this alternate name is a colloquial or slang term. Example: 'Big Apple' for 'New York'.
             * isHistoric        : '1', if this alternate name is historic and was used in the past. Example 'Bombay' for 'Mumbai'.
             */
            foreach ( $rows as $j => $row ) {
                $fields = explode( "\t", $row );
                //$fields          = array_map( 'trim', $fields );
                $alternateNameId = $fields[ 0 ];
                $geonameid       = $fields[ 1 ];
                $isolanguage     = empty( $fields[ 2 ] ) ? '' : $fields[ 2 ];
                $alternate_name  = empty( $fields[ 3 ] ) ? '' : $fields[ 3 ];
                $isPreferredName = empty( $fields[ 4 ] ) ? FALSE : $fields[ 4 ];
                $isShortName     = empty( $fields[ 5 ] ) ? FALSE : $fields[ 5 ];
                $isColloquial    = empty( $fields[ 6 ] ) ? FALSE : $fields[ 6 ];
                $isHistoric      = empty( $fields[ 7 ] ) ? FALSE : $fields[ 7 ];

                $splitRowsToInsert[] = [
                    'alternateNameId' => $alternateNameId,
                    'geonameid'       => $geonameid,
                    'isolanguage'     => $isolanguage,
                    'alternate_name'  => $alternate_name,
                    'isPreferredName' => $isPreferredName,
                    'isShortName'     => $isShortName,
                    'isColloquial'    => $isColloquial,
                    'isHistoric'      => $isHistoric,
                    'created_at'      => Carbon::now(),
                    'updated_at'      => Carbon::now(),
                ];

                $geonamesBar->advance();
                $totalRowsInserted++;
            }
            $this->comment( "Inserting $numRowsInSplitFile rows from split file..." );
            AlternateNamesWorking::on( $this->connectionName )->insert( $splitRowsToInsert );

            $geonamesBar->finish();
        endforeach;

        return $totalRowsInserted;
    }
}
