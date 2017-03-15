<?php
namespace MichaelDrennen\Geonames\Console;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\Command;
use Exception;


use MichaelDrennen\Geonames\GeoSetting;
use MichaelDrennen\Geonames\Log;

class InsertGeonames extends Command {

    use GeonamesConsoleTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:insert-geonames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert fresh data from geonames.org';

    /**
     * @var string The name of the txt file that contains data from all of the countries.
     */
    protected $allCountriesZipFileName = 'allCountries.zip';

    /**
     * @var string The name of the txt file that contains data from all of the countries.
     */
    protected $allCountriesTxtFileName = 'allCountries.txt';

    /**
     * @var string This command makes this file. It contains all the records that get inserted into the database.
     */
    protected $masterTxtFileName = 'master.txt';

    /**
     * @var int A counter that tracks the number of lines written to the master txt file.
     */
    protected $numLinesInMasterFile;

    /**
     *
     */
    const TABLE = 'geonames';

    /**
     *
     */
    const TABLE_WORKING = 'geonames_working';

    /**
     * Initialize constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->line("Starting " . $this->signature);

        $this->line( "Turning off the memory limit for php." );
        ini_set('memory_limit', -1);


        $zipFiles = $this->getLocalCountryZipFileNames();
        $this->line("We found " . count($zipFiles) . " zip files that were downloaded from geonames.");

        try {
            $this->unzipFiles( $zipFiles );
        } catch ( Exception $e ) {
            $this->error( "Unable to unzip at least one of the country zip files." );
            Log::error( '', "We were unable to unzip at least one of the country zip files.", 'local' );
        }

        $absolutePathToMasterTxtFile = $this->combineTxtFiles();

        try {
            $this->insert($absolutePathToMasterTxtFile);
        } catch ( Exception $e ) {
            $this->error($e->getMessage());
        }

        $this->line("Finished " . $this->signature);
    }


    /**
     * Get all of the file names in our geonames local storage directory.
     * @return array The file names we saved from geonames.org
     */
    private function getLocalFiles (): array {
        $storagePath = GeoSetting::getAbsoluteLocalStoragePath();
        $fileNames = scandir($storagePath);
        array_shift($fileNames); // Remove .
        array_shift($fileNames); // Remove ..
        return $fileNames;
    }

    /**
     * Every country has a zip file with all of their current geonames records. Also, there is
     * an allCountries.zip file that contains all of the records.
     * @return array    All of the zip file names we downloaded from geonames.org that contain
     *                  records for our geonames table.
     */
    private function getLocalCountryZipFileNames (): array {
        $fileNames = $this->getLocalFiles();
        $zipFileNames = [];
        foreach ($fileNames as $fileName) {
            if ( $this->isCountryZipFile( $fileName ) ) {
                $zipFileNames[] = $fileName;
            }
        }

        return $zipFileNames;
    }

    /**
     * After all of the country zip files have been downloaded and unzipped, we need to
     * gather up all of the resulting txt files.
     * @return array    An array of all the unzipped country text files.
     */
    protected function getLocalCountryTxtFileNames (): array {
        $fileNames = $this->getLocalFiles();
        $txtFileNames = [];
        foreach ($fileNames as $fileName) {
            if ( $this->isCountryTxtFile( $fileName ) ) {
                $txtFileNames[] = $fileName;
            }
        }

        return $txtFileNames;
    }


    /**
     * @param string $fileName
     * @return bool
     */
    private function isCountryZipFile ( string $fileName ): bool {
        if ( $fileName === $this->allCountriesZipFileName ) {
            return true;
        }
        if ( preg_match( '/^[A-Z]{2}\.zip$/', $fileName ) === 1 ) {
            return true;
        }
        return false;
    }

    /**
     * @param string $fileName
     * @return bool
     */
    private function isCountryTxtFile ( string $fileName ): bool {
        if ( $fileName === $this->allCountriesTxtFileName ) {
            return true;
        }
        if ( preg_match( '/^[A-Z]{2}\.txt$/', $fileName ) === 1 ) {
            return true;
        }
        return false;
    }


    /**
     * @param string $fileName
     * @return string
     */
    private function getAbsolutePathToFile ( string $fileName ): string {
        return GeoSetting::getAbsoluteLocalStoragePath() . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Find all of the unzipped country txt files, and combine them into one master file.
     * @return string
     * @throws Exception
     */
    protected function combineTxtFiles (): string {
        $absolutePathToMasterTxtFile = $this->getAbsolutePathToFile($this->masterTxtFileName);
        $textFiles = $this->getLocalCountryTxtFileNames();

        if ($this->allCountriesInLocalTxtFiles($textFiles)) {
            $this->line("The allCountries text file was found, so no need to combine files. Just rename it to master.");

            $absolutePathToAllCountriesTxtFile = $this->getAbsolutePathToFile($this->allCountriesTxtFileName);
            $renameResult = rename($absolutePathToAllCountriesTxtFile, $absolutePathToMasterTxtFile);
            if ($renameResult === false) {
                throw new Exception( "We were unable to rename the allCountries to the master file." );
            }
            $this->line("The allCountries file has been renamed. Ready to insert.");

            return $absolutePathToMasterTxtFile;
        }

        $this->line("We found " . count($textFiles) . " text files that we are going to combine.");

        // Create and/or truncate the master txt file before we start putting data in it.
        $masterResource = fopen($absolutePathToMasterTxtFile, "w+");

        if (!file_exists($absolutePathToMasterTxtFile)) {
            throw new Exception( "We were unable to create a master txt file to put all of our rows into." );
        }

        foreach ($textFiles as $textFile) {
            $absolutePathToTextFile = $this->getAbsolutePathToFile($textFile);
            $this->line("\nStarting to process " . $absolutePathToTextFile);
            $inputFileSize = filesize($absolutePathToTextFile);

            $inputResource = @fopen($absolutePathToTextFile, 'r');

            if ( $inputResource === false ) {
                throw new Exception( "Unable to open this file in read mode " . $absolutePathToTextFile );
            }

            $this->line( "Opened..." );
            $bar = $this->output->createProgressBar( $inputFileSize );
            while ( ( $buffer = fgets( $inputResource ) ) !== false ) {
                $bytesWritten = fwrite( $masterResource, $buffer );
                if ( $bytesWritten === false ) {
                    throw new Exception( "Unable to write " . strlen( $buffer ) . " characters from " . $absolutePathToTextFile . " to the master file." );
                }
                $this->numLinesInMasterFile++;
                $bar->advance( $bytesWritten );
            }
            if ( !feof( $inputResource ) ) {
                throw new Exception( "Error: unexpected fgets() fail on " . $absolutePathToTextFile );
            }
            fclose( $inputResource );

        }
        $closeResult = fclose($masterResource);
        if ($closeResult === false) {
            throw new Exception( "Unable to close the master file at " . $absolutePathToMasterTxtFile );
        }

        return $absolutePathToMasterTxtFile;
    }



    /**
     * @param string $localFilePath
     * @throws Exception
     */
    protected function insert($localFilePath) {
        $this->line("\nStarting to insert the records found in " . $localFilePath);
        if (is_null($this->numLinesInMasterFile)) {
            $this->line("We are going to try to insert " . $this->numLinesInMasterFile . " geoname records.");
        } else {
            $this->line("We are going to try to insert the geoname records from the allCountries file.");
        }

        $this->line( "Dropping the temp table named " . self::TABLE_WORKING . " (if it exists)." );
        Schema::dropIfExists( self::TABLE_WORKING );

        $this->line("Creating the temp table named geonames_working.");
        DB::statement( 'CREATE TABLE ' . self::TABLE_WORKING . ' LIKE ' . self::TABLE . '; ' );

        $query = "LOAD DATA LOCAL INFILE '" . $localFilePath . "'
    INTO TABLE " . self::TABLE_WORKING . "
        (geonameid, 
             name, 
             asciiname, 
             alternatenames, 
             latitude, 
             longitude, 
             feature_class, 
             feature_code, 
             country_code, 
             cc2, 
             admin1_code, 
             admin2_code, 
             admin3_code, 
             admin4_code, 
             population, 
             elevation, 
             dem, 
             timezone, 
             modification_date, 
             @created_at, 
             @updated_at)
SET created_at=NOW(),updated_at=null";

        $this->line("Running the LOAD DATA INFILE query...");

        $rowsInserted = DB::connection()->getpdo()->exec($query);
        if ($rowsInserted === false) {
            throw new Exception( "Unable to execute the load data infile query. " . print_r( DB::connection()->getpdo()->errorInfo(), true ) );
        }

        $this->info( "Inserted text file into " . self::TABLE_WORKING );

        $this->line( "Dropping the active " . self::TABLE . " table." );
        Schema::dropIfExists( self::TABLE );


        Schema::rename( self::TABLE_WORKING, self::TABLE );
        $this->info( "Renamed " . self::TABLE_WORKING . " to " . self::TABLE );
        GeoSetting::setCountriesFromCountriesToBeAdded();
    }


    /**
     * If the allCountries file is found in the geonames storage dir on this box, then we can just use that and
     * ignore any other text files.
     * @param array $txtFiles An array of text file names that we found in the geonames storage dir on this box.
     * @return bool
     */
    private function allCountriesInLocalTxtFiles ( array $txtFiles ): bool {
        if (in_array($this->allCountriesTxtFileName, $txtFiles)) {
            return true;
        }

        return false;
    }


}
