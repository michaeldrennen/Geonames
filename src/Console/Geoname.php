<?php

namespace MichaelDrennen\Geonames\Console;

class Geoname extends AbstractCommand {

    use GeonamesConsoleTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:geoname
        {--connection= : If you want to specify the name of the database connection you want used.}
        {--test : If you want to test the command on a small countries data set.}';

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
    public function __construct() {
        parent::__construct();
    }


    /**
     * @throws \Exception
     */
    public function handle() {
        $this->setDatabaseConnectionName();

        if ( $this->option( 'test' ) ):
            $this->comment( "Running the geonames:geoname artisan command in test mode." );
            $this->call( 'geonames:download-geonames',
                         [ '--test'       => TRUE,
                           '--connection' => $this->connectionName ] );
            $this->call( 'geonames:insert-geonames',
                         [ '--test'       => TRUE,
                           '--connection' => $this->connectionName ] );
        else:
            $this->comment( "Running the geonames:geoname artisan command in live mode." );
            $this->call( 'geonames:download-geonames', [ '--connection' => $this->connectionName ] );
            $this->call( 'geonames:insert-geonames', [ '--connection' => $this->connectionName ] );
        endif;


    }


}
