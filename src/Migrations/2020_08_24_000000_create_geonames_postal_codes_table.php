<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeonamesPostalCodesTable extends Migration {

    const TABLE = 'geonames_postal_codes';

    /**
     * Run the migrations.
     * Source of data: http://download.geonames.org/export/zip/allCountries.zip
     * Sample data:
     * US	99553	Akutan	Alaska	AK	Aleutians East	013			54.143	-165.7854	1
     *
     * @return void
     */
    public function up() {
        Schema::create( self::TABLE, function ( Blueprint $table ) {
            $table->engine = 'MyISAM';
            $table->increments('id');
            $table->char( 'country_code', 2 );
            $table->string( 'postal_code', 20 );
            $table->string( 'place_name', 180 );
            $table->string( 'admin1_name', 100 );
            $table->string( 'admin1_code', 20 );
            $table->string( 'admin2_name', 100 );
            $table->string( 'admin2_code', 20 );
            $table->string( 'admin3_name', 100 );
            $table->string( 'admin3_code', 20 );
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->tinyInteger( 'accuracy' );
            $table->timestamps();

            $table->index( 'postal_code' );
            $table->index( [ 'country_code', 'postal_code' ] );
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists( self::TABLE );
    }
}
