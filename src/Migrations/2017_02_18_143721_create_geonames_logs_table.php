<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateGeoLogsTable
 * Create a table for us to log the activity of our Geonames package.
 * For instance, if there is an error downloading an update file from the
 * geonames.org website it would be nice to have a record of that for debugging
 * purposes.
 */
class CreateGeonamesLogsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create( 'geonames_logs', function ( Blueprint $table ) {
            $table->engine = 'MyISAM';
            $table->increments('id');
            $table->string( 'url', 255 )->nullable();      // The url we were trying to retrieve.
            $table->string('type', 255);
            $table->string('tag', 255);     // A short string that lets us query/filter for specific types of log messages.
            $table->text('message');        // Verbose explanation as to what happened.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists( 'geonames_logs' );
    }
}
