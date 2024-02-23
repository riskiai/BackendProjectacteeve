<?php

use App\Models\Company;
use App\Models\Project;
use App\Models\PurchaseCategory;
use App\Models\PurchaseStatus;
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
        Schema::create('purchases', function (Blueprint $table) {
            Schema::dropIfExists('purchases');
            $table->string('doc_no')->primary();
            $table->string('doc_type');
            $table->string('tab')->default('1');
            $table->integer('purchase_id');
            $table->foreignIdFor(PurchaseCategory::class)->nullable()->references('id')->on('purchase_category')->cascadeOnDelete();
            $table->foreignIdFor(Company::class)->nullable()->references('id')->on('companies')->cascadeOnDelete();
            $table->foreignIdFor(Project::class)->nullable()->references('id')->on('projects')->cascadeOnDelete();
            $table->foreignIdFor(PurchaseStatus::class)->nullable()->references('id')->on('purchase_status')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->string('sub_total')->nullable();
            $table->string('ppn')->nullable();
            $table->string('pph')->nullable();
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
