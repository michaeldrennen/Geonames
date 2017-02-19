<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Support\Facades\Storage;

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


        $this->line("Finished " . $this->signature);
    }


    /**
     *
     */
    protected function getLocalFiles() {
        $storagePath = $this->getStorage();
        $files = Storage::files($storagePath);


    }


}
