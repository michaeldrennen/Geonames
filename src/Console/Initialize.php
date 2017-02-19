<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;

class Initialize extends Command {
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
     * Create a new command instance.
     *
     * @return void
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


        $this->line("Finished " . $this->signature);
    }

    protected function getLocalFiles() {

    }




}
