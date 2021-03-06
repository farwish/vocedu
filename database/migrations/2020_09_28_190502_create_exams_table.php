<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable(false);
            $table->unsignedTinyInteger('status')->nullable(false)->default(0);
            $table->timestamp('expired_at')->nullable();

            $table->unsignedBigInteger('paper_id')->nullable(false)->index();

            $table->unsignedBigInteger('category_id')->nullable(false)->index();

            $table->string('area')->nullable();

            $table->unsignedBigInteger('guide_id')->nullable();
            $table->unsignedBigInteger('outline_id')->nullable();

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
        Schema::dropIfExists('exams');
    }
}
