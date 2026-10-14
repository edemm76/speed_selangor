<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id('status_id');
            $table->foreignId('application_id')
                ->constrained('applications', 'application_id')
                ->cascadeOnDelete();
            // null = change made by the vendor (e.g. submit / resubmit)
            $table->foreignId('updated_by_admin_id')->nullable()
                ->constrained('administrators', 'admin_id')
                ->nullOnDelete();
            $table->string('status');
            $table->text('remarks')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
    }
};