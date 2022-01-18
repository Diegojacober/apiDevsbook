<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('userrelations', function (Blueprint $table) {
            $table->id();
            $table->integer('user_from');
            $table->integer('user_to');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('type',20);
            $table->dateTime('created_at');
            $table->text('body');
        });

        Schema::create('postlikes', function (Blueprint $table) {
            $table->id();
            $table->integer('id_post');
            $table->integer('id_user');
            $table->dateTime('created_at');
        });

        Schema::create('postcomments', function (Blueprint $table) {
            $table->id();
            $table->integer('id_post');
            $table->integer('id_user');
            $table->dateTime('created_at');
            $table->text('body');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('userrelations');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('postlikes');
        Schema::dropIfExists('postcomments');
    }
}
