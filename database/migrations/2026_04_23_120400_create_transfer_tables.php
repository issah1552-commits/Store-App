<?php

use App\Enums\TransferStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('source_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')->constrained('locations')->restrictOnDelete();
            $table->string('status')->default(TransferStatus::Draft->value)->index();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->index();
            $table->timestamp('dispatched_at')->nullable()->index();
            $table->timestamp('received_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->boolean('has_variance')->default(false)->index();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['destination_location_id', 'status']);
            $table->index(['source_location_id', 'status']);
        });

        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('requested_quantity');
            $table->unsignedInteger('approved_quantity')->nullable();
            $table->unsignedInteger('dispatched_quantity')->default(0);
            $table->unsignedInteger('received_quantity')->default(0);
            $table->unsignedInteger('variance_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['transfer_id', 'product_variant_id']);
        });

        Schema::create('transfer_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('transfer_status_histories');
        Schema::dropIfExists('transfer_items');
        Schema::dropIfExists('transfers');
    }
};
