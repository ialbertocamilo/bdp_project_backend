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
        Schema::create('file_data', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('project_id')->nullable()->constrained('projects');
            $table->string('project_name_field');
            $table->string('step')->nullable();
            $table->string('sub_step')->nullable();
            $table->text('realname');
            $table->string('route')->nullable(false);
            $table->string('size')->nullable(false);
            $table->boolean('multiple')->nullable(false)->default(false);
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
        Schema::dropIfExists('file_data');
    }
};
