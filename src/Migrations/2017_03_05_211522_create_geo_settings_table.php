<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeoSettingsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('geo_settings', function (Blueprint $table) {
            // We should only ever have one record in this table.
            $table->increments('id');

            // A json encoded array of the countries we want to maintain in our database.
            $table->text('countries')->nullable();

            // The date and time when this database was first filled with geonames records.
            $table->dateTime('installed_at')->nullable();

            // The date and time when the geonames table was last updated with the modifications file.
            $table->dateTime('last_modified_at')->nullable();

            // Is it live? Currently updating? Offline?
            $table->string('status', 255)->nullable();

            // Laravel created_at and updated_at timestamp fields.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('geo_settings');
    }
}
