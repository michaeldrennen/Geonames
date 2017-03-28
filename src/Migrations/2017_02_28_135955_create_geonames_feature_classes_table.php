<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class CreateGeonamesFeatureClassesTable extends Migration {
    /**
     * This small table is filled with static data from geonames.org.
     * @throws \Exception
     */
    public function up() {
        Schema::create( 'geonames_feature_classes', function ( Blueprint $table ) {
            $table->char('id', 1);
            $table->string('description', 255);
            $table->primary('id');
        });

        $seeder = new FeatureClassSeeder();
        $seeder->run();


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists( 'geonames_feature_classes' );
    }
}
