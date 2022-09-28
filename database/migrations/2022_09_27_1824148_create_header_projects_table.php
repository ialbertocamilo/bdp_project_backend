<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('header_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();  
            $table->string('name', 250); // Implementacion, cierre, etc
            $table->timestamps();
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('header_project_id')->unsigned()->nullable();
            $table->foreign('header_project_id')->references('id')->on('header_projects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('header_projects');
    }
};
