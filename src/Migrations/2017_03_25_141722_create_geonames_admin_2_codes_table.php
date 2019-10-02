<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeonamesAdmin2CodesTable extends Migration {

    const TABLE = 'geonames_admin_2_codes';

    /**
     * Run the migrations.
     * Source of data: http://download.geonames.org/export/dump/admin2Codes.txt
     * Sample data:
     * US.CO.107    Routt County    Routt County    5581553
     *
     * @return void
     */
    public function up() {
        // Format : concatenated codes <tab>name <tab> asciiname <tab> geonameId
        Schema::create( self::TABLE, function ( Blueprint $table ) {
            $table->engine = 'MyISAM';
            $table->integer( 'geonameid', FALSE, TRUE )->primary();                // 5581553
            $table->char( 'country_code', 2 )->nullable();              // US
            $table->string( 'admin1_code', 20 )->nullable();            // CO
            $table->string( 'admin2_code', 80 )->nullable();            // 107
            $table->string( 'name', 255 )->nullable();                  // Routt County
            $table->string( 'asciiname', 255 )->nullable();             // Routt County
            $table->timestamps();

            $table->index( 'country_code' );
            $table->index( 'admin1_code' );
            $table->index( 'admin2_code' );
            $table->index( [ 'country_code', 'admin1_code' ] );
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
