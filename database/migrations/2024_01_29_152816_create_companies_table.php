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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_type_id')->nullable();
            $table->foreign('contact_type_id')->references('id')->on('contact_type')->onDelete('cascade');
            $table->string('name');
            $table->string('address');
            $table->string('npwp');
            $table->string('pic_name');
            $table->string('phone');
            $table->string('email');
            $table->string('file');
            $table->string('bank_name');
            $table->string('branch');
            $table->string('account_name');
            $table->string('currency');
            $table->string('account_number');
            $table->string('swift_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
