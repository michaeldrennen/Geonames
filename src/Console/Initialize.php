<?php

namespace MichaelDrennen\Geonames\Console;

use ZipArchive;
use SplFileInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\Command;
use MichaelDrennen\Geonames\BaseTrait;

class Initialize extends Command {
    use BaseTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:initialize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download and insert fresh data from geonames.org';

    /**
     * @var string The name of the txt file that contains data from all of the countries.
     */
    protected $allCountriesTxtFileName = 'allCountries.txt';

    /**
     * @var string This command makes this file. It contains all the records that get inserted into the database.
     */
    protected $masterTxtFileName = 'master.txt';

    /**
     * @var array When combining the unzipped text files, make sure we don't zip up the readme.txt or master.txt file.
     */
    protected $txtFilesToIgnore = ['readme.txt'];

    /**
     * @var int A counter that tracks the number of lines written to the master txt file.
     */
    protected $numLinesInMasterFile;

    /**
     * Initialize constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->setStorage();
        // Add the master txt file to the list of files to ignore.
        // *this can't be done above where we assign properties.
        $this->txtFilesToIgnore[] = $this->masterTxtFileName;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->line("Starting " . $this->signature);

        $this->line("Turning off the memory limit for php. Some of these files are pretty big.");
        ini_set('memory_limit', -1);


        $zipFiles = $this->getLocalZipFiles();
        $this->line("We found " . count($zipFiles) . " zip files that were downloaded from geonames.");
        foreach ($zipFiles as $zipFile) {
            $absolutePathToFile = $this->getAbsolutePathToFile($zipFile);
            $this->unzip($absolutePathToFile);
            $this->line(" - unzipped " . $zipFile);
        }

        $this->combineTxtFiles();

        try {
            $absolutePathToMasterTxtFile = $this->getAbsolutePathToFile($this->masterTxtFileName);
            $this->insert($absolutePathToMasterTxtFile);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->line("Finished " . $this->signature);
    }


    /**
     * @return array The file names we saved from geonames.org
     */
    protected function getLocalFiles(): array {
        $storagePath = $this->getStorage();
        $fileNames = scandir($storagePath);
        array_shift($fileNames); // Remove .
        array_shift($fileNames); // Remove ..
        return $fileNames;
    }

    /**
     * @return array All of the zip file names we downloaded from geonames.org.
     */
    protected function getLocalZipFiles(): array {
        $fileNames = $this->getLocalFiles();
        $zipFileNames = [];
        foreach ($fileNames as $fileName) {
            if ($this->isZipFile($fileName)) {
                $zipFileNames[] = $fileName;
            }
        }

        return $zipFileNames;
    }

    /**
     * @return array All of the txt file names that we unzipped (from the geonames files).
     */
    protected function getLocalTxtFiles(): array {
        $fileNames = $this->getLocalFiles();
        $txtFileNames = [];
        foreach ($fileNames as $fileName) {
            if ($this->isTxtFile($fileName)) {
                $txtFileNames[] = $fileName;
            }
        }

        return $txtFileNames;
    }


    /**
     * Is the given filename a zip file?
     * @param $fileName string The file name you want to check.
     * @return bool
     */
    protected function isZipFile($fileName): bool {
        $info = new SplFileInfo($fileName);
        if ('zip' == $info->getExtension()) {
            return true;
        }

        return false;
    }

    /**
     * Is the given file name a txt file, AND not one of the files we want to ignore?
     * @param $fileName string The file name you want to check.
     * @return bool
     */
    protected function isTxtFile($fileName): bool {
        $info = new SplFileInfo($fileName);

        if ($this->ignoreThisTxtFileWhenCombiningUnzippedFiles($fileName)) {
            return false;
        }

        if ('txt' == $info->getExtension()) {
            return true;
        }

        return false;
    }

    /**
     * When combining the unzipped text files, make sure we don't zip up the
     * readme.txt or master.txt file.
     * @param $fileName string A file from our geonames storage directory.
     * @return bool
     */
    protected function ignoreThisTxtFileWhenCombiningUnzippedFiles($fileName) {
        if (in_array($fileName, $this->txtFilesToIgnore)) {
            return true;
        }

        return false;
    }

    /**
     * @param $fileName
     * @return string
     */
    protected function getAbsolutePathToFile($fileName) {
        return $this->getStorage() . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Takes all of the unzipped text files in the storage dir, and combines them into one file.
     * @throws \Exception
     */
    protected function combineTxtFiles(): bool {
        $absolutePathToMasterTxtFile = $this->getAbsolutePathToFile($this->masterTxtFileName);
        $textFiles = $this->getLocalTxtFiles();

        if ($this->allCountriesInLocalTxtFiles($textFiles)) {
            $this->line("The allCountries text file was found, so no need to combine files. Just rename it to master.");

            $absolutePathToAllCountriesTxtFile = $this->getAbsolutePathToFile($this->allCountriesTxtFileName);
            $renameResult = rename($absolutePathToAllCountriesTxtFile, $absolutePathToMasterTxtFile);
            if ($renameResult === false) {
                throw new \Exception("We were unable to rename the allCountries to the master file.");
            }
            $this->line("The allCountries file has been renamed. Ready to insert.");

            return true;
        }

        $this->line("We found " . count($textFiles) . " text files that we are going to combine.");

        // Create and/or truncate the master txt file before we start putting data in it.
        $this->line("We just truncated the master txt file. Ready to put all of our rows from geonames into it.");
        $masterResource = fopen($absolutePathToMasterTxtFile, "w+");

        if (!file_exists($absolutePathToMasterTxtFile)) {
            throw new \Exception("We were unable to create a master txt file to put all of our rows into.");
        }

        foreach ($textFiles as $textFile) {
            $absolutePathToTextFile = $this->getAbsolutePathToFile($textFile);
            $this->line("\nStarting to process " . $absolutePathToTextFile);
            $inputFileSize = filesize($absolutePathToTextFile);

            $inputResource = @fopen($absolutePathToTextFile, 'r');

            if ($inputResource) {
                $this->line("Opened...");
                $bar = $this->output->createProgressBar($inputFileSize);
                while (($buffer = fgets($inputResource)) !== false) {
                    $bytesWritten = fwrite($masterResource, $buffer);
                    if ($bytesWritten === false) {
                        throw new \Exception("Unable to write " . strlen($buffer) . " characters from " . $absolutePathToTextFile . " to the master file.");
                    }
                    $this->numLinesInMasterFile++;
                    $bar->advance($bytesWritten);
                }
                if (!feof($inputResource)) {
                    throw new \Exception("Error: unexpected fgets() fail on " . $absolutePathToTextFile);
                }
                fclose($inputResource);
            } else {
                throw new \Exception("Unable to open this file in read mode " . $absolutePathToTextFile);
            }
        }
        $closeResult = fclose($masterResource);
        if ($closeResult === false) {
            throw new \Exception("Unable to close the master file at " . $absolutePathToMasterTxtFile);
        }

        return true;
    }

    /**
     * @param string $localFilePath Absolute local path to the zip archive.
     * @throws \Exception
     */
    protected function unzip($localFilePath) {
        $storage = $this->getStorage();
        $zip = new ZipArchive;
        if ($zip->open($localFilePath) === true) {
            $extractResult = $zip->extractTo($storage);
            if ($extractResult === false) {
                throw new \Exception("Unable to unzip the file at " . $localFilePath);
            }
            $closeResult = $zip->close();
            if ($closeResult === false) {
                throw new \Exception("After unzipping unable to close the file at " . $localFilePath);
            }

            return;
        }
        throw new \Exception("Unable to unzip the archive at " . $localFilePath);
    }


    /**
     * @param string $localFilePath
     * @throws \Exception
     */
    protected function insert($localFilePath) {
        $this->line("\nStarting to insert the records found in " . $localFilePath);
        if (is_null($this->numLinesInMasterFile)) {
            $this->line("We are going to try to insert " . $this->numLinesInMasterFile . " geoname records.");
        } else {
            $this->line("We are going to try to insert the geoname records from the allCountries file.");
        }

        $this->line("Dropping the temp table named geonames_working (if it exists).");
        Schema::dropIfExists('geonames_working');

        $this->line("Creating the temp table named geonames_working.");
        DB::statement('CREATE TABLE geonames_working LIKE geonames; ');

        $query = "LOAD DATA LOCAL INFILE '" . $localFilePath . "'
    INTO TABLE geonames_working
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
            throw new \Exception("Unable to execute the load data infile query. " . print_r(DB::connection()->getpdo()->errorInfo(), true));
        }

        $this->info("Inserted text file into geonames_working.");

        $this->line("Dropping the active geonames table.");
        Schema::dropIfExists('geonames');


        Schema::rename('geonames_working', 'geonames');
        $this->info("Renamed geonames_working to geonames.");
    }


    /**
     * If the allCountries file is found in the geonames storage dir on this box, then we can just use that and
     * ignore any other text files.
     * @param array $txtFiles An array of text file names that we found in the geonames storage dir on this box.
     * @return bool
     */
    protected function allCountriesInLocalTxtFiles(array $txtFiles): bool {
        if (in_array($this->allCountriesTxtFileName, $txtFiles)) {
            return true;
        }

        return false;
    }

}
