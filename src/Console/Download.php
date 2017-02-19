<?php

namespace MichaelDrennen\Geonames\Console;

use Curl\Curl;
use Illuminate\Console\Command;
use MichaelDrennen\Geonames\Log;

class Download extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "This command downloads the files you want from geonames.org and saves them locally.";

    protected $curl;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Curl $curl) {
        parent::__construct();
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

        $remoteFilePaths = $this->getRemoteFilePathsToDownloadForGeonamesTable();

        $downloadedData = [];

        foreach ($remoteFilePaths as $remoteFilePath) {
            $this->curl->get($remoteFilePath);

            if ($this->curl->error) {
                $this->error($this->curl->error_code . ':' . $this->curl->error_message);
                Log::error($remoteFilePath, $this->curl->error_message, $this->curl->error_code);
            } else {
                $this->info("Downloaded " . $remoteFilePath);
                $downloadedData[] = $this->curl->response;
            }
        }


        $this->line("Finished " . $this->signature);
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getRemoteFilePathsToDownloadForGeonamesTable() {
        $download_base_url = config('geonames.download_base_url');
        $countries = config('geonames.countries');

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
}
