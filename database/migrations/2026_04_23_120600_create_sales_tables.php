<?php

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('ordered_by')->constrained('users')->restrictOnDelete();
            $table->string('status')->default(OrderStatus::Pending->value)->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->decimal('subtotal_tzs', 14, 2)->default(0);
            $table->decimal('discount_tzs', 14, 2)->default(0);
            $table->decimal('total_tzs', 14, 2)->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['location_id', 'status']);
            $table->index(['ordered_by', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_tzs', 14, 2);
            $table->decimal('line_total_tzs', 14, 2);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->string('status')->default(InvoiceStatus::Issued->value)->index();
            $table->string('payment_status')->default(PaymentStatus::Unpaid->value)->index();
            $table->decimal('subtotal_tzs', 14, 2)->default(0);
            $table->decimal('tax_tzs', 14, 2)->default(0);
            $table->decimal('discount_tzs', 14, 2)->default(0);
            $table->decimal('total_tzs', 14, 2)->default(0);
            $table->decimal('amount_paid_tzs', 14, 2)->default(0);
            $table->decimal('balance_tzs', 14, 2)->default(0);
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['location_id', 'payment_status']);
            $table->unique('order_id');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_tzs', 14, 2);
            $table->decimal('line_total_tzs', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
