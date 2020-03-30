<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeonamesAlternateNamesTable extends Migration {


    const TABLE = 'geonames_alternate_names';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create( self::TABLE, function ( Blueprint $table ) {
            $table->engine = 'MyISAM';

            /**
             * alternateNameId   : the id of this alternate name, int
             */
            $table->integer( 'alternateNameId', FALSE, TRUE );
            $table->integer( 'geonameid', FALSE, TRUE );

            /**
             * isolanguage: iso 639 language code 2- or 3-characters; 4-characters 'post' for postal codes and
             * 'iata','icao' and faac for airport codes, fr_1793 for French Revolution names,  abbr for abbreviation,
             * link for a website, varchar(7)
             */
            $table->string( 'isolanguage', 7 )
                  ->nullable();

            /**
             * alternate_name: alternate name or name variant, varchar(400)
             */
            $table->string( 'alternate_name', 400 )
                  ->nullable();

            /**
             * isPreferredName: '1', if this alternate name is an official/preferred name
             */
            $table->tinyInteger( 'isPreferredName', FALSE, TRUE )
                  ->nullable();

            /**
             * isShortName: '1', if this is a short name like 'California' for 'State of California'
             */
            $table->tinyInteger( 'isShortName', FALSE, TRUE )
                  ->nullable();

            /**
             * isColloquial: '1', if this alternate name is a colloquial or slang term
             */
            $table->tinyInteger( 'isColloquial', FALSE, TRUE )
                  ->nullable();

            /**
             * isHistoric: '1', if this alternate name is historic and was used in the past
             */
            $table->tinyInteger( 'isHistoric', FALSE, TRUE )
                  ->nullable();

            /**
             * Laravel's created_at and updated_at timestamp fields.
             */
            $table->timestamps();
            $table->primary( 'alternateNameId' );
            $table->index( 'geonameid' );

            $table->index( [ \Illuminate\Support\Facades\DB::raw( 'alternate_name(250)' ) ] );
        } );


        /**
         * I have to use the following code in place of the "Laravel way"...
         * $table->index( 'alternate_name' );
         * There is a problem with MySQL unable to create indexes over a certain length.
         * @see https://github.com/michaeldrennen/Geonames/issues/30
         * This was similar to the error that I was getting:
         * Illuminate\Database\QueryException  : SQLSTATE[42000]: Syntax error or
         * access violation: 1071 Specified key was too long; max key length is 1000
         * bytes (SQL: alter table `geonames_alternate_names` add index
         * `geonames_alternate_names_alternate_name_index`(`alternate_name`))
         */


        //\Illuminate\Support\Facades\DB::statement( 'CREATE INDEX alt_name_part ON ' . self::TABLE . '(alternate_name(250));' );
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
