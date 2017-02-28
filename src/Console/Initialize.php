<?php

namespace MichaelDrennen\Geonames\Console;

use ZipArchive;
use SplFileInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\Command;
use MichaelDrennen\Geonames\BaseTrait;
class Initialize extends Base {
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
     * The name of the txt file that contains data from all of the countries.
     *
     * @var string
     */
    protected $allCountriesTxtFileName = 'allCountries.txt';

    /**
     * @var string
     */
    protected $masterTxtFileName = 'master.txt';

    /**
     * @var array
     */
    protected $txtFilesToIgnore = ['readme.txt'];

    /**
     * @var int A counter that tracks the number of lines written to the master txt file.
     */
    protected $numLinesInMasterFile = 0;

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

        $this->info("Turning off the memory limit for php. Some of these files are pretty big.");
        ini_set('memory_limit', -1);

        $zipFiles = $this->getLocalZipFiles();
        foreach ($zipFiles as $zipFile) {
            $absolutePathToFile = $this->getAbsolutePathToFile($zipFile);
            $this->line($absolutePathToFile);
            $this->unzip($absolutePathToFile);
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
    protected function getLocalFiles() {
        $storagePath = $this->getStorage();
        $files = scandir($storagePath);
        array_shift($files); // Remove .
        array_shift($files); // Remove ..
        return $files;
    }

    /**
     * @return array
     */
    protected function getLocalZipFiles() {
        $files = $this->getLocalFiles();
        $zipFiles = [];
        foreach ($files as $file) {
            if ($this->isZipFile($file)) {
                $zipFiles[] = $file;
            }
        }
        return $zipFiles;
    }

    /**
     * @return array
     */
    protected function getLocalTxtFiles() {
        $files = $this->getLocalFiles();
        $txtFiles = [];
        foreach ($files as $file) {
            if ($this->isTxtFile($file)) {
                $txtFiles[] = $file;
            }
        }
        return $txtFiles;
    }


    /**
     * @param $fileName string The file name you want to check.
     * @return bool
     */
    protected function isZipFile($fileName) {
        $info = new SplFileInfo($fileName);
        if ('zip' == $info->getExtension()) {
            return true;
        }
        return false;
    }

    /**
     * @param $fileName string The file name you want to check.
     * @return bool
     */
    protected function isTxtFile($fileName) {
        $info = new SplFileInfo($fileName);

        if ($this->ignoreThisTxtFile($fileName)) {
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
    protected function ignoreThisTxtFile($fileName) {
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
    protected function combineTxtFiles() {
        $absolutePathToMasterTxtFile = $this->getAbsolutePathToFile($this->masterTxtFileName);

        // Truncate the master txt file before we start putting data in it.
        $masterResource = fopen($absolutePathToMasterTxtFile, "w");
        fclose($masterResource);

        $textFiles = $this->getLocalTxtFiles();


        if ($this->allCountriesInLocalTxtFiles($textFiles)) {
            $this->info("The allCountries text file was found, so no need to combine files. Just rename it to master.");

            $absolutePathToAllCountriesTxtFile = $this->getAbsolutePathToFile($this->allCountriesTxtFileName);
            $renameResult = rename($absolutePathToAllCountriesTxtFile, $absolutePathToMasterTxtFile);
            if ($renameResult === false) {
                throw new \Exception("We were unable to rename the allCountries to the master file.");
            }
            $this->info("The allCountries file has been renamed. Ready to insert.");
            return;
        }


        $this->line("We found " . count($textFiles) . " text files that we are going to combine.");

        $masterResource = fopen($absolutePathToMasterTxtFile, 'a+');

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


    protected function insert($localFilePath) {
        $this->line("\nStarting to insert the records found in " . $localFilePath);
        $this->line("We are going to try to insert " . $this->numLinesInMasterFile . " geoname records.");

        Schema::dropIfExists('geonames_working');


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

        $this->comment($query);

        $rowsInserted = DB::connection()->getpdo()->exec($query);
        if ($rowsInserted === false) {
            throw new \Exception("Unable to execute the load data infile query.");
        }

        $this->info("Inserted text file into geonames_working.");

        Schema::dropIfExists('geonames');
        $this->line("Dropped the geonames table.");

        Schema::rename('geonames_working', 'geonames');
        $this->info("Renamed geonames_working to geonames.");

    }


    /**
     * If the allCountries file is found in the geonames storage dir on this box, then we can just use that and
     * ignore any other text files.
     * @param array $txtFiles An array of text file names that we found in the geonames storage dir on this box.
     * @return bool
     */
    protected function allCountriesInLocalTxtFiles(array $txtFiles) {
        if (in_array($this->allCountriesTxtFileName, $txtFiles)) {
            return true;
        }
        return false;
    }

}
