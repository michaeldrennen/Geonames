<?php

namespace MichaelDrennen\Geonames\Console;

use Curl\Curl;
use Illuminate\Console\Command;
use MichaelDrennen\Geonames\Log;

class Base extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:parent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "This is a parent class for shared functions. This should not be called directly.";


    /**
     * @var string Absolute local path to where we store the downloaded geonames files.
     */
    protected $storageDir;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * @return string The absolute path to where the downloaded geonames files will be stored.
     * @throws \Exception
     */
    protected function setStorage() {
        $geonamesStorageDirectory = config('geonames.storage');

        $path = storage_path() . DIRECTORY_SEPARATOR . $geonamesStorageDirectory;
        if (file_exists($path) && is_writable($path)) {
            return $path;
        }

        if (file_exists($path) && !is_writable($path)) {
            throw new Exception("The storage path at '" . $path . "' exists but we can't write to it.");
        }

        if (mkdir($path, 0700)) {
            return $path;
        }

        throw new \Exception("We were unable to create the storage path at '" . $path . "' so check to make sure you have the proper permissions.");
    }


}
