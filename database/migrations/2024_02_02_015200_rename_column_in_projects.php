<?php

use App\Models\Company;
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
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('company_name');
            $table->date('date');
            $table->string('id')->primary();
            $table->foreignIdFor(Company::class)->nullable()->references('id')->on('companies')->cascadeOnDelete();
            $table->timestamps();
        });
    }
};
