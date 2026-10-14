<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->id('verification_id');
            $table->foreignId('application_id')
                ->constrained('applications', 'application_id')
                ->cascadeOnDelete();
            $table->foreignId('verified_by_admin_id')->nullable()
                ->constrained('administrators', 'admin_id')
                ->nullOnDelete();
            $table->string('decision'); // action_required | verified
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};