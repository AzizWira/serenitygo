<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->decimal('personal_order_fee_idr', 15, 2)->nullable();
            $table->decimal('request_sharing_fee_idr', 15, 2)->nullable();
        });

        Schema::create('calculator_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('rounding_enabled')->default(false);
            $table->unsignedInteger('rounding_increment')->default(1000);
            $table->timestamps();
        });
        // Disabled by default until the Owner explicitly enables the new rounding policy.
        DB::table('calculator_settings')->insert([
            'id' => 1, 'rounding_enabled' => false, 'rounding_increment' => 1000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // The new fee scheme replaces the old fee_type/fee_value in calculations.
        // Legacy columns are deliberately retained so this migration is non-destructive.
        DB::table('currency_rates')->where('currency', 'JPY')->update([
            'personal_order_fee_idr' => 10000, 'request_sharing_fee_idr' => 12000,
        ]);
        DB::table('currency_rates')->where('currency', 'KRW')->update([
            'personal_order_fee_idr' => 25000, 'request_sharing_fee_idr' => 27000,
        ]);

        foreach (['OTW INA' => 'Shipping ID', 'Arrived Ina' => 'Arrived INA', 'Arrived admin' => 'Arrived Admin'] as $old => $new) {
            DB::table('batches')->where('status', $old)->update(['status' => $new]);
            DB::table('tracking_histories')->where('from_status', $old)->update(['from_status' => $new]);
            DB::table('tracking_histories')->where('to_status', $old)->update(['to_status' => $new]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_settings');
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->dropColumn(['personal_order_fee_idr', 'request_sharing_fee_idr']);
        });
        // Deliberately do not rewrite historical status text on rollback.
    }
};
