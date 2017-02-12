<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeonamesTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('geonames', function (Blueprint $table) {
            $table->integer('geonameid');               // geonameid         : integer id of record in geonames database
            $table->string('name', 200);                // name              : name of geographical point (utf8) varchar(200)
            $table->string('asciiname', 200);           // asciiname         : name of geographical point in plain ascii characters, varchar(200)
            $table->string('alternatenames', 10000);    // alternatenames    : alternatenames, comma separated, ascii names automatically transliterated, convenience attribute from alternatename table, varchar(10000)
            $table->decimal('latitude', 10, 8);         // latitude          : latitude in decimal degrees (wgs84)
            $table->decimal('longitude', 11, 8);        // longitude         : longitude in decimal degrees (wgs84)
            $table->string('feature_class', 1);         // feature class     : see http://www.geonames.org/export/codes.html, char(1)
            $table->string('feature_code', 10);         // feature code      : see http://www.geonames.org/export/codes.html, varchar(10)
            $table->string('country_code', 2);          // country code      : ISO-3166 2-letter country code, 2 characters
            $table->string('cc2', 200);                 // cc2               : alternate country codes, comma separated, ISO-3166 2-letter country code, 200 characters
            $table->string('admin1_code', 20);          // admin1 code       : fipscode (subject to change to iso code), see exceptions below, see file admin1Codes.txt for display names of this code; varchar(20)
            $table->string('admin2_code', 80);          // admin2 code       : code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80)
            $table->string('admin3_code', 20);          // admin3 code       : code for third level administrative division, varchar(20)
            $table->string('admin4_code', 20);          // admin4 code       : code for fourth level administrative division, varchar(20)
            $table->bigInteger('population');           // population        : bigint (8 byte int)
            $table->integer('elevation', 200);          // elevation         : in meters, integer
            $table->integer('dem');                     // dem               : digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30'' (ca 900mx900m) area in meters, integer. srtm processed by cgiar/ciat.
            $table->string('timezone', 40);             // timezone          : the iana timezone id (see file timeZone.txt) varchar(40)
            $table->date('modification_date');          // modification date : date of last modification in yyyy-MM-dd format
            $table->timestamps();                       // Laravel's created_at and updated_at timestamp fields.
            $table->primary('geonameid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('geonames');
    }
}
