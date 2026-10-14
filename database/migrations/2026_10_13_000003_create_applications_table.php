<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id('application_id');
            $table->foreignId('vendor_id')
                ->constrained('vendors', 'vendor_id')
                ->cascadeOnDelete();
            $table->string('reference_no')->nullable()->unique(); // given on submit, e.g. SS-2026-0001
            $table->string('permit_type');                         // App\Enums\PermitType
            $table->string('project_title');
            $table->text('project_description')->nullable();
            $table->string('site_address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('current_status')->default('draft')->index(); // App\Enums\ApplicationStatus
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};