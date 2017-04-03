<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeonamesAlternateNamesTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create( 'geonames_alternate_names', function ( Blueprint $table ) {
            $table->engine = 'MyISAM';
            $table->integer( 'alternateNameId', false, true );                  // alternateNameId   : the id of this alternate name, int
            $table->integer( 'geonameid', false, true );
            $table->string( 'isolanguage', 7 )
                  ->nullable();                     // isolanguage       : iso 639 language code 2- or 3-characters; 4-characters 'post' for postal codes and 'iata','icao' and faac for airport codes, fr_1793 for French Revolution names,  abbr for abbreviation, link for a website, varchar(7)
            $table->string( 'alternate_name', 400 )
                  ->nullable();                // alternate_name    : alternate name or name variant, varchar(400)
            $table->tinyInteger( 'isPreferredName', false, true )
                  ->nullable();  // isPreferredName   : '1', if this alternate name is an official/preferred name
            $table->tinyInteger( 'isShortName', false, true )
                  ->nullable();      // isShortName       : '1', if this is a short name like 'California' for 'State of California'
            $table->tinyInteger( 'isColloquial', false, true )
                  ->nullable();     // isColloquial      : '1', if this alternate name is a colloquial or slang term
            $table->tinyInteger( 'isHistoric', false, true )
                  ->nullable();       // isHistoric        : '1', if this alternate name is historic and was used in the past
            $table->timestamps();                                               // Laravel's created_at and updated_at timestamp fields.
            $table->primary('alternateNameId');
            $table->index( 'geonameid' );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists( 'geonames_alternate_names' );
    }
}
