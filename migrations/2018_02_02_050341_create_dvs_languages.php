<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDvsLanguages extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('dvs_languages', function ($table) {
      $table->increments('id');
      $table->string('code', 2);
      $table->string('human_name', 255)->nullable();
      $table->string('regional_human_name')->nullable();
      $table->boolean('active')->default('0');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::drop('dvs_languages');
  }
}
