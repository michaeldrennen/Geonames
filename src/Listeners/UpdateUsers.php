<?php
namespace MichaelDrennen\Geonames\Listeners;

use MichaelDrennen\Geonames\Events\GeonameUpdated;

use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateUsers implements ShouldQueue {
    /**
     * The name of the connection the job should be sent to.
     *
     * @var string|null
     */
    public $connection = 'sqs';

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'listeners';

    /**
     * Create the event listener.
     */
    public function __construct () {
        //
    }

    /**
     * Handle the event.
     *
     * @param  GeonameUpdated $event
     * @return void
     */
    public function handle ( GeonameUpdated $event ) {
        // Access the geoname record using $event->geoname...
    }
}