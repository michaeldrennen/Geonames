<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;
use MichaelDrennen\Geonames\Models\GeoSetting;

class Status extends Command {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:status';

    /**
     * @var string The console command description.
     */
    protected $description = "Outputs the status of this geonames installation.";


    /**
     * Initialize constructor.
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * Execute the console command.
     */
    public function handle() {
        $this->line("Status of this Geonames Installation");
        $settings = GeoSetting::first();

        $rows = [];


        $headers = ['Status',
                    'Countries',
                    'Countries to be Added',
                    'Languages',
                    'Storage Subdir',
                    'Installed',
                    'Last Modified',
                    'Created',
                    'Updated'];

        $rows[] = [$settings->status,
                   @implode( ", ", $settings->countries ),
                   @implode( ", ", $settings->countries_to_be_added ),
                   @implode( ", ", $settings->languages ),
                   $settings->storage_subdir,
                   isset( $settings->installed_at ) ? $settings->installed_at->diffForHumans() : '',
                   isset( $settings->last_modified_at ) ? $settings->last_modified_at->diffForHumans() : '',
                   isset( $settings->created_at ) ? $settings->created_at->diffForHumans() : '',
                   isset( $settings->updated_at ) ? $settings->updated_at->diffForHumans() : ''];


        $this->table( $headers, $rows );
    }

}
