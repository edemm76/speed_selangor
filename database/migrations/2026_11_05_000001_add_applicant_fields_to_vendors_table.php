<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 4: applicants can be an individual OR a company.
 * - adds applicant_type and ic_no
 * - company_name and registration_no are only needed for companies
 * - new accounts are active straight away
 * Existing rows are kept (they become 'company').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('applicant_type')->default('company')->after('vendor_id'); // individual | company
            $table->string('ic_no')->nullable()->unique()->after('registration_no');  // individuals only

            $table->string('company_name')->nullable()->change();
            $table->string('registration_no')->nullable()->change();
            $table->string('status')->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropUnique(['ic_no']);
            $table->dropColumn(['applicant_type', 'ic_no']);

            $table->string('company_name')->nullable(false)->change();
            $table->string('registration_no')->nullable(false)->change();
            $table->string('status')->default('pending')->change();
        });
    }
};