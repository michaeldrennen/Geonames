<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;


class Geoname extends Command {

    use GeonamesConsoleTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:geoname';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "This command downloads and inserts the files you want from geonames.org and saves them locally.";


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


    public function handle () {
        $this->call( 'geonames:download-geonames' );
        $this->call( 'geonames:insert-geonames' );
    }


}