<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('application_education', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                  ->constrained('applications')
                  ->onDelete('cascade');

            $table->enum('qualification', ['HSLC (10th)', 'HS (10+2)', 'Graduation', 'Others']);
            $table->string('stream')->nullable();
            $table->string('board')->nullable();          // University / Board
            $table->decimal('percentage', 5, 2)->nullable();
            $table->enum('division', ['1st', '2nd', '3rd', 'Pass'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_education');
    }
};
