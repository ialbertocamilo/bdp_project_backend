<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('risk', function (Blueprint $table) {
            $table->id();
            $table->string('type', 250)->nullable();
            $table->string('description', 500)->nullable();
            $table->integer('i')->nullable();
            $table->integer('p')->nullable();
            $table->integer('c')->nullable();
            $table->integer('value')->nullable();
            $table->string('level', 250)->nullable();
            $table->foreignId('edt_id')->constrained('edt');
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
        Schema::dropIfExists('risk');
    }
};
