<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeonamesIsoLanguageCodesTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up () {
        /**
         * ISO 639-3    ISO 639-2    ISO 639-1    Language Name
         */
        Schema::create( 'geonames_iso_language_codes', function ( Blueprint $table ) {
            $table->engine = 'MyISAM';
            $table->char( 'iso_639_3', 3 );
            $table->string( 'iso_639_2', 255 )->nullable();
            $table->char( 'iso_639_1', 2 )->nullable();
            $table->string( 'language_name', 255 );
            $table->timestamps();
            $table->primary( 'iso_639_3' );
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down () {
        Schema::dropIfExists( 'geonames_iso_language_codes' );
    }
}
