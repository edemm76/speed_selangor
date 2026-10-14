<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id('user_notification_id');
            $table->foreignId('notification_id')
                ->constrained('notifications', 'notification_id')
                ->cascadeOnDelete();
            // One of vendor_id / admin_id is filled, depending on who receives it
            $table->foreignId('vendor_id')->nullable()
                ->constrained('vendors', 'vendor_id')
                ->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()
                ->constrained('administrators', 'admin_id')
                ->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()
                ->constrained('applications', 'application_id')
                ->cascadeOnDelete();
            $table->dateTime('read_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};