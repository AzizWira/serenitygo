<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable(); $t->string('password');
            $t->rememberToken(); $t->timestamps();
        });
        Schema::create('batches', function (Blueprint $t) {
            $t->id(); $t->string('code', 32)->unique(); $t->enum('origin', ['JP','KR','ID']);
            $t->string('status', 40); $t->boolean('show_items_public')->default(false);
            $t->text('internal_note')->nullable(); $t->timestamp('status_updated_at')->nullable(); $t->timestamps();
            $t->index(['origin','status']);
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name', 180); $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->string('item_name', 255); $t->unsignedInteger('qty')->default(1);
            $t->text('internal_note')->nullable(); $t->timestamps();
            $t->index(['batch_id','customer_id']);
        });
        Schema::create('tracking_histories', function (Blueprint $t) {
            $t->id(); $t->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $t->string('from_status', 40)->nullable(); $t->string('to_status', 40);
            $t->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
        });
        Schema::create('currency_rates', function (Blueprint $t) {
            $t->id(); $t->string('currency', 8)->unique(); $t->string('label', 60);
            $t->decimal('idr_rate', 16, 6)->nullable();
            $t->enum('fee_type', ['fixed','percent'])->default('fixed');
            $t->decimal('fee_value', 15, 2)->default(0); $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('tax_records', function (Blueprint $t) {
            $t->id(); $t->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->decimal('amount_idr', 16, 2)->nullable();
            $t->boolean('is_fixed')->default(false);
            $t->date('posted_at')->nullable(); $t->date('due_at')->nullable(); $t->date('paid_at')->nullable();
            $t->text('note')->nullable(); $t->timestamps();
            $t->unique(['batch_id', 'customer_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('tax_records'); Schema::dropIfExists('currency_rates');
        Schema::dropIfExists('tracking_histories'); Schema::dropIfExists('order_items');
        Schema::dropIfExists('customers'); Schema::dropIfExists('batches'); Schema::dropIfExists('users');
    }
};
