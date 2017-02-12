<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeoAlternateNamesTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('geo_alternate_names', function (Blueprint $table) {
            $table->integer('alternateNameId');     // alternateNameId   : the id of this alternate name, int
            $table->string('isolanguage', 7);        // isolanguage       : iso 639 language code 2- or 3-characters; 4-characters 'post' for postal codes and 'iata','icao' and faac for airport codes, fr_1793 for French Revolution names,  abbr for abbreviation, link for a website, varchar(7)
            $table->string('alternate name', 400);   // alternate name    : alternate name or name variant, varchar(400)
            $table->tinyInteger('isPreferredName'); // isPreferredName   : '1', if this alternate name is an official/preferred name
            $table->string('isShortName');          // isShortName       : '1', if this is a short name like 'California' for 'State of California'
            $table->string('isColloquial');         // isColloquial      : '1', if this alternate name is a colloquial or slang term
            $table->string('isHistoric');           // isHistoric        : '1', if this alternate name is historic and was used in the past
            $table->timestamps();                   // Laravel's created_at and updated_at timestamp fields.
            $table->primary('alternateNameId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('geo_alternate_names');
    }
}
