<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRevisionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('revision_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->time('old_start_time')->nullable();
            $table->time('new_start_time')->nullable();
            $table->time('old_end_time')->nullable();
            $table->time('new_end_time')->nullable();
            $table->json('break_modifications')->nullable();
            $table->text('note');
            $table->enum('status',['pending', 'approved'])->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['attendance_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('revision__requests');
    }
}
