<?php

use App\Enums\InternalMovementStatus;
use App\Enums\StockBucket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_movement_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('max_quantity_per_item')->nullable();
            $table->unsignedInteger('max_quantity_per_transaction')->nullable();
            $table->unsignedInteger('max_daily_quantity_per_user')->nullable();
            $table->boolean('after_hours_blocked')->default(false);
            $table->time('after_hours_start')->nullable();
            $table->time('after_hours_end')->nullable();
            $table->boolean('requires_reason')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('internal_movements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('status')->default(InternalMovementStatus::Draft->value)->index();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('total_value_tzs', 14, 2)->default(0);
            $table->string('escalation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['location_id', 'status']);
        });

        Schema::create('internal_movement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_movement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('source_bucket')->default(StockBucket::Wholesale->value);
            $table->string('destination_bucket')->default(StockBucket::Retail->value);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_value_tzs', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['internal_movement_id', 'product_variant_id'], 'internal_movement_variant_unique');
        });

        Schema::create('internal_movement_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_movement_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status')->index();
            $table->foreignId('acted_by')->constrained('users')->restrictOnDelete();
            $table->string('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_movement_histories');
        Schema::dropIfExists('internal_movement_items');
        Schema::dropIfExists('internal_movements');
        Schema::dropIfExists('internal_movement_rules');
    }
};
