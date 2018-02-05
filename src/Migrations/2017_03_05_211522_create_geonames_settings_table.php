<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use MichaelDrennen\Geonames\Models\GeoSetting;

class CreateGeonamesSettingsTable extends Migration {

    const TABLE_NAME = 'geonames_settings';


    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create( self::TABLE_NAME, function ( Blueprint $table ) {
            $table->engine = 'MyISAM';
            // We should only ever have one record in this table.
            $table->increments( GeoSetting::DB_COLUMN_ID );

            // A json encoded array of the countries we want to maintain in our database.
            $table->text( GeoSetting::DB_COLUMN_COUNTRIES )->nullable();

            // These are countries that need to be added after the initial install.
            $table->text( GeoSetting::DB_COLUMN_COUNTRIES_TO_BE_ADDED )->nullable();

            // A json encoded array of the languages. This really only has bearing on what version of the
            // feature codes file that we pull down. It's the only file that is language dependent.
            $table->text( GeoSetting::DB_COLUMN_LANGUAGES )->nullable();

            // Let the user specify a database connection name for the geonames system. Leave NULL for default.
            $table->string( GeoSetting::DB_COLUMN_CONNECTION )->nullable();

            // The date and time when this database was first filled with geonames records.
            $table->dateTime( GeoSetting::DB_COLUMN_INSTALLED_AT )->nullable();

            // The date and time when the geonames table was last updated with the modifications file.
            $table->dateTime( GeoSetting::DB_COLUMN_LAST_MODIFIED_AT )->nullable();

            // Is it live? Currently updating? Offline?
            $table->string( GeoSetting::DB_COLUMN_STATUS, 255 )->nullable();

            // The name of a directory under storage_dir()
            $table->string( GeoSetting::DB_COLUMN_STORAGE_SUBDIR, 255 )->nullable();

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
        Schema::dropIfExists( self::TABLE_NAME );
    }
}
