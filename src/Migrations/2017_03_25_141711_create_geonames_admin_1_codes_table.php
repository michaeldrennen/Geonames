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
        } );

        /**
         * I have to use the following code in place of the "Laravel way"...
         * $table->index( 'asciiname' );
         * There is a problem with MySQL unable to create indexes over a certain length.
         * @see https://github.com/michaeldrennen/Geonames/issues/30
         * This was similar to the error that I was getting:
         * Illuminate\Database\QueryException  : SQLSTATE[42000]: Syntax error or
         * access violation: 1071 Specified key was too long; max key length is 1000
         * bytes (SQL: alter table `geonames_alternate_names` add index
         * `geonames_alternate_names_alternate_name_index`(`alternate_name`))
         */
        \Illuminate\Support\Facades\DB::statement( 'CREATE INDEX asciiname_part ON ' . self::TABLE . ' asciiname(250);' );
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
