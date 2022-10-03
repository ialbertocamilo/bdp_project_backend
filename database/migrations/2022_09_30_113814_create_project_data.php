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
        Schema::create('project_data', function (Blueprint $table) {
            $table->id();
            $table->string('step_name', 100); // Implementacion, cierre, etc
            $table->string('substep_name', 50); // registro, actividades
            $table->integer('percentage')->nullable(); // registro, actividades
            $table->json('content'); // Contenido de la seccion escogida
            $table->foreignId('project_id')->constrained('projects');
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
        Schema::dropIfExists('project_data');
    }
};
