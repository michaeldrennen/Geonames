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
class CreateGeoLogsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('geo_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 255);
            $table->string('tag', 255);
            $table->text('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('geo_logs');
    }
}
