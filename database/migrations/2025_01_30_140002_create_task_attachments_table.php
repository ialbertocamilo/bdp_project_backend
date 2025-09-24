<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->integer('file_size');
            $table->string('file_type');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_attachments');
    }
};