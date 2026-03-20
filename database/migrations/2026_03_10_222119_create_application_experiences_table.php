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
        Schema::create('application_experiences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                  ->constrained('applications')
                  ->onDelete('cascade');

            $table->string('organization');
            $table->string('designation');
            $table->date('from_date');
            $table->date('to_date')->nullable();          // null = present
            $table->decimal('years', 5, 1)->nullable();   // computed or manual

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_experiences');
    }
};
