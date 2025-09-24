<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->decimal('hours', 8, 2);
            $table->date('date');
            $table->boolean('is_billable')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_id');
            $table->index('user_id');
            $table->index('date');
            $table->index(['task_id', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('time_entries');
    }
};