<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeoDeletesTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('geo_deletes_codes', function (Blueprint $table) {
            $table->increments('id');       // Primary key of this table. Possible that we could use geonameid. Can a record be added after it's deleted?
            $table->date('date');           // The date that this record was removed from the geonames database.
            $table->integer('geonameid');   // geonameid         : integer id of record in geonames database
            $table->string('name', 200);    // name              : name of geographical point (utf8) varchar(200)
            $table->string('reason', 255);   // The reason that this record was deleted.
            $table->timestamps();           // Laravel's created_at and updated_at timestamp fields.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('geo_deletes_codes');
    }
}
