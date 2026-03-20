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
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                  ->constrained('applications')
                  ->onDelete('cascade');

            $table->enum('document_type', [
                'photo',
                'id_proof',
                'address_proof',
                'hslc_admit',
                'marksheet',
                'offer_letter',
                'experience_certificate',
                'resume',
            ]);

            $table->string('original_name');   // original filename from user
            $table->string('stored_path');     // path on disk / S3 key
            $table->string('disk')->default('local'); // local | s3
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');

            $table->timestamps();

            // One document type per application
            $table->unique(['application_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
