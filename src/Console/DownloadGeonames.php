<?php

namespace MichaelDrennen\Geonames\Console;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use MichaelDrennen\Geonames\GeoSetting;
use MichaelDrennen\Geonames\Log;


class DownloadGeonames extends Command {

    use GeonamesConsoleTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:download-geonames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "This command downloads the files you want from geonames.org and saves them locally.";



    /**
     * @var array List of absolute local file paths to downloaded geonames files.
     */
    protected $localFiles = [];

    /**
     * Download constructor.
     */
    public function __construct () {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        //
        $this->line("Starting " . $this->signature);

        $this->info("Turning off the memory limit for php. Some of these files are pretty big.");
        ini_set('memory_limit', -1);

        //$this->emptyTheStorageDirectory();

        $countries = GeoSetting::getCountriesToBeAdded();
        $this->line( "We will be saving the downloaded files to: " . GeoSetting::getAbsoluteLocalStoragePath() );


        try {
            $remoteFilePaths = $this->getRemoteFilePathsToDownloadForGeonamesTable($countries);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            Log::error('', $e->getMessage(), 'local');
            return false;
        }

        $this->line("Attempting to download the following files:");
        foreach ($remoteFilePaths as $remoteFilePath) {
            $this->info("  " . $remoteFilePath);
        }

        $localFilePaths = $this->downloadFiles( $this, $remoteFilePaths );


        return true;
    }


    /**
     * Returns an array of absolute remote paths to geonames country files we need to download.
     * @param array $countries The value from GeoSetting countries_to_be_added
     * @return array
     */
    protected function getRemoteFilePathsToDownloadForGeonamesTable ( array $countries ) {
        // If the config setting for countries has the wildcard symbol "*", then the user wants data for all countries.
        if (array_search("*", $countries) !== false) {
            return [self::$url . 'allCountries.zip'];
        }

        $files = [];
        foreach ($countries as $country) {
            $files[] = self::$url . $country . '.zip';
        }
        return $files;
    }




    /**
     * @throws \Exception
     */
    protected function emptyTheStorageDirectory() {

        $allFiles = Storage::allFiles($this->getStorage());
        $this->line("We found " . count($allFiles) . " in our storage directory.");
        $this->line("Deleting all of the txt and zip files out of " . $this->getStorage());
        Storage::delete($allFiles);

        $allFiles = Storage::allFiles($this->getStorage());
        $numFiles = count($allFiles);
        if ($numFiles != 0) {
            throw new \Exception("We were unable to delete all of the files in " . $this->getStorage() . " Check the permissions.");
        }
        $this->line("The storage dir is clean. Start downloading files.");
    }

}