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
            $table->increments('id');
            $table->char('language_code', 2);
            $table->char('feature_class', 1);
            $table->string('feature_code', 10);
            $table->string('name', 255);
            $table->text('description');
            $table->timestamps();
            $table->index(['language_code',
                           'feature_code']);
            $table->index(['language_code',
                           'feature_class']);
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
