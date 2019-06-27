<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScriptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scripts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string("language");
            $table->string("encoding");
            $table->string("root");
            $table->string("name");
            $table->string("description");
            $table->string("version");
            $table->string("extensions");
            $table->string("inputs");
            $table->string("type");
            $table->string("authors");
            $table->string("support_email");
            $table->string("company");
            $table->string("unique_code")->unique();
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
        Schema::dropIfExists('scripts');
    }
}
