<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdmin1CodesTable extends Migration {
    /**
     * Run the migrations.
     * Source of data: http://download.geonames.org/export/dump/admin1CodesASCII.txt
     * Sample data:
     * US.CO    Colorado    Colorado    5417618
     * @return void
     */
    public function up () {
        // In the command that I run to fill this table, I split the concatenated values in column 1 into
        // country_code and admin1_code
        Schema::create( 'geonames_admin_1_codes', function ( Blueprint $table ) {
            $table->integer( 'geonameid' );
            $table->char( 'country_code', 2 );
            $table->string( 'admin1_code', 20 );
            $table->string( 'name', 255 );
            $table->string( 'asciiname', 255 );
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down () {
        Schema::dropIfExists( 'geonames_admin_1_codes' );
    }
}
