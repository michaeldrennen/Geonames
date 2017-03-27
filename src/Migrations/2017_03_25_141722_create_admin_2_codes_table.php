<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdmin2CodesTable extends Migration {
    /**
     * Run the migrations.
     * Source of data: http://download.geonames.org/export/dump/admin2Codes.txt
     * Sample data:
     * US.CO.107    Routt County    Routt County    5581553
     *
     * @return void
     */
    public function up () {
        // Format : concatenated codes <tab>name <tab> asciiname <tab> geonameId
        Schema::create( 'geonames_admin_2_codes', function ( Blueprint $table ) {
            $table->integer( 'geonameid', false, true );

            $table->char( 'country_code', 2 );
            $table->string( 'admin1_code', 20 );
            $table->string( 'admin2_code', 80 );

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
        Schema::dropIfExists( 'geonames_admin_2_codes' );
    }
}
