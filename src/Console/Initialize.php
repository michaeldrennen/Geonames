<?php

namespace MichaelDrennen\Geonames\Console;

use ZipArchive;
use SplFileInfo;
use PDO;
use Illuminate\Support\Facades\DB;

class Initialize extends Base {
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

    protected $masterTxtFileName = 'master.txt';

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

        $zipFiles = $this->getLocalZipFiles();
        foreach ($zipFiles as $zipFile) {
            $absolutePathToFile = $this->getAbsolutePathToFile($zipFile);
            $this->line($absolutePathToFile);
            $this->unzip($absolutePathToFile);
        }

        //$txtFiles = $this->getLocalTxtFiles();

        $masterTxtFile = $this->combineTxtFiles();

        $this->insert($masterTxtFile);

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
        if ('txt' == $info->getExtension()) {
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

    protected function combineTxtFiles() {
        $absolutePathToMasterTxtFile = $this->getAbsolutePathToFile($this->masterTxtFileName);

        // Truncate the master txt file before we start putting data in it.
        $fp = fopen($absolutePathToMasterTxtFile, "w");
        fclose($fp);

        $textFiles = $this->getLocalTxtFiles();

        $fpMaster = fopen($absolutePathToMasterTxtFile, 'a+');

        foreach ($textFiles as $textFile) {
            $absolutePathToTextFile = $this->getAbsolutePathToFile($textFile);
            $fileContents = file_get_contents($absolutePathToTextFile);
            fwrite($fpMaster, $fileContents);
        }
        fclose($fpMaster);
    }

    /**
     * @param string $localFilePath Absolute local path to the zip archive.
     * @throws \Exception
     */
    protected function unzip($localFilePath) {
        $storage = $this->getStorage();
        $zip = new ZipArchive;
        if ($zip->open($localFilePath) === true) {
            $zip->extractTo($storage);
            $zip->close();
            return;
        }
        throw new \Exception("Unable to unzip the archive at " . $localFilePath);
    }

    protected function insert($localFilePath) {

        DB::statement('CREATE TABLE geonames_working LIKE geonames; ');

        //        $oldOptions = config('database.options', [PDO::MYSQL_ATTR_LOCAL_INFILE => true]);
        //        config('database.options', [PDO::MYSQL_ATTR_LOCAL_INFILE => true]);
        //        $query = "SELECT * FROM geonames";
        //        $results = DB::connection()->getpdo()->exec($query);
        //        var_dump($results);
        //        config('database.options', $oldOptions);
    }

}
