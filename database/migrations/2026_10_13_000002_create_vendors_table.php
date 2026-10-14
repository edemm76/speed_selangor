<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id('vendor_id');
            $table->string('company_name');
            $table->string('registration_no')->unique(); // SSM number
            $table->string('pic_name');                  // person in charge
            $table->string('email')->unique();
            $table->string('no_phone')->nullable();
            $table->string('address')->nullable();
            $table->string('password_hash');
            $table->string('status')->default('pending')->index(); // pending | active | suspended
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};