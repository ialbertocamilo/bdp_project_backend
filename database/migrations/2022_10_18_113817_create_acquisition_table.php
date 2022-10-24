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
        Schema::create('acquisition', function (Blueprint $table) {
            $table->id();
            $table->string('acquisition', 250);
            $table->string('modality', 250);
            $table->date('date_ini');
            $table->date('date_end');
            $table->double('amount'); //USD AMOUNT
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
        Schema::dropIfExists('acquisition');
    }
};
