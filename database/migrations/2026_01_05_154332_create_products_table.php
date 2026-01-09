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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            /* ================== RELATION ================== */
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            /* ================== BASIC INFO ================== */
            $table->string('name');
            $table->string('slug')->unique();

            /* ================== PRICE ================== */
            $table->integer('price')->nullable();
            $table->integer('sale_price')->nullable();

            /* ================== IMAGE ================== */
            $table->string('images')->nullable(); // ảnh đại diện

            /* ================== CONTENT ================== */
            $table->text('short_desc')->nullable();
            $table->longText('content')->nullable();

            /* ================== STATS ================== */
            $table->integer('view_count')->default(0);
            $table->integer('sold_count')->default(0);

            /* ================== STATUS ================== */
            $table->boolean('is_hot')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
