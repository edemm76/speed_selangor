<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->foreignId('application_id')
                ->constrained('applications', 'application_id')
                ->cascadeOnDelete();
            $table->string('document_type'); // key from PermitType::requiredDocuments()
            $table->string('file_name');     // original file name
            $table->string('file_path');     // path on the public disk
            $table->dateTime('uploaded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};