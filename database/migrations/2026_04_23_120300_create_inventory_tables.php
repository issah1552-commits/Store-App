<?php

use App\Enums\StockBucket;
use App\Enums\StockMovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('bucket')->default(StockBucket::Warehouse->value)->index();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['location_id', 'product_variant_id', 'bucket'], 'stocks_unique_balance_key');
            $table->index(['bucket', 'quantity']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_type')->default(StockMovementType::ManualAdjustment->value)->index();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('source_bucket')->nullable()->index();
            $table->unsignedInteger('source_quantity_before')->nullable();
            $table->unsignedInteger('source_quantity_after')->nullable();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('destination_bucket')->nullable()->index();
            $table->unsignedInteger('destination_quantity_before')->nullable();
            $table->unsignedInteger('destination_quantity_after')->nullable();
            $table->unsignedInteger('quantity');
            $table->string('reference_type')->nullable()->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->index();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
            $table->index(['product_variant_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stocks');
    }
};
