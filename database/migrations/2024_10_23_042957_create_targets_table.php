<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTargetsTable extends Migration
{
    public function up()
    {
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->comment('Year for the target');
            $table->string('month', 20)->comment('Month for the target (e.g., January)');
            $table->integer('target_growth')->comment('Target Growth for the selected month/year');
            $table->integer('target_rkap')->comment('Target RKAP for the selected month/year');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('targets');
    }
}
