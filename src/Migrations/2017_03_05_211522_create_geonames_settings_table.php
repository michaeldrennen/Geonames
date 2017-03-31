<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeonamesSettingsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create( 'geonames_settings', function ( Blueprint $table ) {
            $table->engine = 'MyISAM';
            // We should only ever have one record in this table.
            $table->increments('id');

            // A json encoded array of the countries we want to maintain in our database.
            $table->text('countries')->nullable();

            // These are countries that need to be added after the initial install.
            $table->text('countries_to_be_added')->nullable();

            // A json encoded array of the languages. This really only has bearing on what version of the
            // feature codes file that we pull down. It's the only file that is language dependent.
            $table->text('languages')->nullable();

            // The date and time when this database was first filled with geonames records.
            $table->dateTime('installed_at')->nullable();

            // The date and time when the geonames table was last updated with the modifications file.
            $table->dateTime('last_modified_at')->nullable();

            // Is it live? Currently updating? Offline?
            $table->string('status', 255)->nullable();

            // The name of a directory under storage_dir()
            $table->string('storage_subdir', 255)->nullable();

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
        Schema::dropIfExists( 'geonames_settings' );
    }
}
