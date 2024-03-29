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
            $table->id();
            $table->string('name')->index();
            $table->string('slug')->index();
            $table->string('type');
            $table->string('tag')->nullable();
            $table->string('require')->nullable();
            $table->text('description')->nullable();
            $table->string('permission')->nullable();
            $table->longtext('config')->nullable();
            $table->boolean('enabled')->default(1);
            $table->integer('parent_id')->unsigned()->nullable();
            $table->foreign('parent_id')->references('id')->on(Config::get('amethyst.data-view.data.data-view.table'))->onDelete('cascade');
            $table->timestamps();
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
