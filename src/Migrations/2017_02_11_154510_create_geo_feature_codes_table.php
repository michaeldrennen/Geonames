<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeoFeatureCodesTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('geo_feature_codes', function (Blueprint $table) {
            $table->string('id', 11);
            $table->char('feature_class', 1);
            $table->string('feature_code', 10);
            $table->string('name', 50);
            $table->string('description', 255);
            $table->timestamps();
            $table->primary('id');
            $table->index('feature_class');
            $table->index('feature_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('geo_feature_codes');
    }
}
