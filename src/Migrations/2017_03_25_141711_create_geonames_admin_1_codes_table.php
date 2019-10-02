<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeonamesAdmin1CodesTable extends Migration {

    const TABLE = 'geonames_admin_1_codes';

    /**
     * Run the migrations.
     * Source of data: http://download.geonames.org/export/dump/admin1CodesASCII.txt
     * Sample data:
     * US.CO    Colorado    Colorado    5417618
     * @return void
     */
    public function up() {
        // In the command that I run to fill this table, I split the concatenated values in column 1 into
        // country_code and admin1_code
        Schema::create( self::TABLE, function ( Blueprint $table ) {
            $table->engine = 'MyISAM';
            $table->integer( 'geonameid', FALSE, TRUE )->primary();         // 5417618
            $table->char( 'country_code', 2 );      // US
            $table->string( 'admin1_code', 20 );    // CO
            $table->string( 'name', 255 );          // Colorado
            $table->string( 'asciiname', 255 );     // Colorado
            $table->timestamps();

            $table->index( 'country_code' );
            $table->index( 'admin1_code' );
            $table->index( 'asciiname' );
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
