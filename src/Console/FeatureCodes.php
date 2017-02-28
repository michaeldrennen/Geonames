<?php

namespace MichaelDrennen\Geonames\Console;

use Curl\Curl;
use MichaelDrennen\Geonames\Log;
use Illuminate\Console\Command;
use MichaelDrennen\Geonames\BaseTrait;

class FeatureCodes extends Command {
    use BaseTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:feature-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Download and insert the feature code list from geonames.";

    /**
     * @var Curl Instance of a Curl object that we use to download the files.
     */
    protected $curl;


    /**
     * Create a new command instance.
     * @param Curl $curl
     */
    public function __construct(Curl $curl) {
        parent::__construct();
        $this->setStorage();
        $this->curl = $curl;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        //
        $this->line("Starting " . $this->signature);


        $this->line("We will be saving the downloaded files to: " . $this->storageDir);

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

        foreach ($remoteFilePaths as $remoteFilePath) {
            try {
                $this->downloadAndSaveFile($remoteFilePath);
            } catch (\Exception $e) {
                $this->error($e->getMessage() . " The error was logged. Check the geo_logs table for details.");
            }
        }

        $this->line("Finished " . $this->signature);
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getRemoteFilePathsToDownloadForGeonamesTable($countriesFromCommandLine = []) {
        $download_base_url = config('geonames.download_base_url');
        $countries = config('geonames.countries');

        // Users have the ability to override the config file by passing
        // countries through options in the console (command line).
        if ($countriesFromCommandLine) {
            $countries = $countriesFromCommandLine;
        }

        if (empty($download_base_url)) {
            throw new \Exception("Did you forget to run php artisan vendor:publish? We were unable to load the download base url from the geonames config file.");
        }

        if (empty($countries)) {
            throw new \Exception("Did you forget to run php artisan vendor:publish? We were unable to load countries from the geonames config file.");
        }

        // Comment this code out. Only necessary if I start letting users add to the config list in an exclusionary
        // manner. For example, "Pull all country files, BUT these." So in the countries array, you would find a * and
        // a number of country codes to exclude.
        //        if( sizeof($countries) == 1 && $countries[0] == '*' ){
        //            return [$download_base_url . 'allCountries.zip'];
        //        }

        // If the config setting for countries has the wildcard symbol "*", then the user wants data for all countries.
        if (array_search("*", $countries) !== false) {
            return [$download_base_url . 'allCountries.zip'];
        }

        //
        $files = [];
        foreach ($countries as $country) {
            $files[] = $download_base_url . $country . '.zip';
        }

        return $files;
    }


    /**
     * Attempt to download
     * @param $remoteFilePath string The URL of the remote file we want to download.
     * @throws \Exception
     */
    protected function downloadAndSaveFile($remoteFilePath) {
        $this->line("Starting download of " . $remoteFilePath);
        $basename = basename($remoteFilePath);
        $localFilePath = $this->storageDir . DIRECTORY_SEPARATOR . $basename;

        // This function isn't working.
        //        try{
        //            $this->line("Attempting to get the remote file size.");
        //            $fileSize = $this->getRemoteFileSize($remoteFilePath);
        //            $this->line("The files is " . BaseTrait::getHumanFileSize($fileSize));
        //        } catch( \Exception $e ){
        //            $this->line($e->getMessage());
        //        }

        $this->line("Downloading the full file...");
        $this->curl->get($remoteFilePath);

        if ($this->curl->error) {
            $this->error($this->curl->error_code . ':' . $this->curl->error_message);
            Log::error($remoteFilePath, $this->curl->error_message, $this->curl->error_code);
            throw new \Exception("Unable to download the file at '" . $remoteFilePath . "', " . $this->curl->error_message);
        }

        $this->info("Downloaded " . $remoteFilePath);
        $data = $this->curl->response;
        $bytesWritten = file_put_contents($localFilePath, $data);
        if ($bytesWritten === false) {
            Log::error($remoteFilePath, "Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?", 'local');
            throw new \Exception("Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?");
        }
        $this->localFiles[] = $localFilePath;
        $this->info("Data saved to " . $localFilePath);
    }




}
