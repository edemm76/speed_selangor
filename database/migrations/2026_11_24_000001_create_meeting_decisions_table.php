<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Committee meeting decision for an application (SSMS-UC-400, after verification).
 * After the officer verifies the documents, the application is brought to a meeting
 * (e.g. JK OSC). The officer records who checked it, when and where it was decided,
 * the minutes, who approved it and the result. One decision per application.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_decisions', function (Blueprint $table) {
            $table->id('meeting_decision_id');
            $table->foreignId('application_id')->unique()
                ->constrained('applications', 'application_id')
                ->cascadeOnDelete();
            $table->string('meeting_title');                 // e.g. Mesyuarat JK OSC Bil. 5/2026
            $table->date('meeting_date');
            $table->string('department');                    // department that handled the meeting
            $table->text('checked_by');                      // names + positions of the officers who checked
            $table->string('approver_name');                 // who gave the decision / approval
            $table->string('approver_position');
            $table->string('result');                        // App\Enums\MeetingResult
            $table->text('reason')->nullable();              // compulsory when rejected
            $table->text('amendments')->nullable();          // compulsory when approved with amendments
            $table->text('minutes_summary');
            $table->string('minutes_file_name')->nullable(); // minutes PDF (officers only)
            $table->string('minutes_file_path')->nullable();
            $table->foreignId('recorded_by_admin_id')->nullable()
                ->constrained('administrators', 'admin_id')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_decisions');
    }
};
