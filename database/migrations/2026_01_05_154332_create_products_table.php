<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();

            $table->integer('price')->nullable();
            $table->integer('sale_price')->nullable();

            $table->string('images')->nullable();

            $table->string('type', 255)->nullable();
            $table->string('voltage', 255)->nullable();
            $table->string('capacity', 255)->nullable();
            $table->string('size', 255)->nullable();

            $table->text('short_desc')->nullable();
            $table->longText('content')->nullable();

            $table->integer('view_count')->default(0);
            $table->integer('sold_count')->default(0);

            $table->boolean('is_hot')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
