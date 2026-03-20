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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            // Personal Details
            $table->string('name');
            $table->string('email')->unique();
            $table->string('mobile', 15);
            $table->date('dob');

            // Present Address
            $table->text('present_address');
            $table->string('present_district');
            $table->string('present_pin', 10);

            // Permanent Address
            $table->text('permanent_address');
            $table->string('permanent_district');
            $table->string('permanent_pin', 10);

            // Health Insurance Experience
            $table->enum('health_insurance_experience', ['yes', 'no']);
            $table->decimal('health_experience_years', 5, 1)->nullable();

            // Status
            $table->enum('status', ['draft', 'submitted', 'under_review', 'accepted', 'rejected'])
                  ->default('submitted');

            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
