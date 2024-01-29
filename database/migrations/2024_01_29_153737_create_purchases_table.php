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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_category_id')->constrained('purchase_category');
            $table->foreignId('purchase_status_id')->constrained('purchase_status');
            $table->string('project');
            $table->string('doc_no');
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->string('subtotal');
            $table->string('ppn');
            $table->string('total');
            $table->string('file')->nullable();
            $table->date('date');
            $table->date('due_date');
            $table->string('created_by');
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
