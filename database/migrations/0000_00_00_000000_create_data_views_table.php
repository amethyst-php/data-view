<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class CreateDataViewsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create(Config::get('amethyst.data-view.data.data-view.table'), function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('type');
            $table->text('description')->nullable();
            $table->string('permission')->nullable();
            $table->longtext('config')->nullable();
            $table->boolean('enabled')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists(Config::get('amethyst.data-view.data.data-view.table'));
    }
}
