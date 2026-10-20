<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('serenity_item_syncs',function(Blueprint $t){
            $t->id();$t->string('source_key',64)->unique();$t->foreignId('order_item_id')->unique()->constrained('order_items')->cascadeOnDelete();
            $t->unsignedInteger('last_qty');$t->timestamps();
        });
        Schema::create('serenity_tax_syncs',function(Blueprint $t){
            $t->id();$t->string('source_key',64)->unique();$t->foreignId('tax_record_id')->unique()->constrained('tax_records')->cascadeOnDelete();
            $t->decimal('last_amount_idr',16,2);$t->timestamps();
        });
    }
    public function down(): void {Schema::dropIfExists('serenity_tax_syncs');Schema::dropIfExists('serenity_item_syncs');}
};
