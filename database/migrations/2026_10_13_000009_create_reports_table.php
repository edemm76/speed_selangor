<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->foreignId('application_id')
                ->constrained('applications', 'application_id')
                ->cascadeOnDelete();
            $table->foreignId('generated_by_admin_id')->nullable()
                ->constrained('administrators', 'admin_id')
                ->nullOnDelete();
            $table->string('report_type')->default('application'); // application
            $table->string('file_path');
            $table->dateTime('generated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};