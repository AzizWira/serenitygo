<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\CurrencyRate;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\TaxRecord;
use App\Models\TrackingHistory;
use App\Models\User;
use App\Support\GoStatus;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_complete_and_consistent_fixtures(): void
    {
        $this->artisan('db:seed', ['--class' => DemoSeeder::class])->assertSuccessful();

        $this->assertDatabaseCount('batches', 96);
        $this->assertDatabaseCount('customers', 80);
        $this->assertDatabaseCount('order_items', 658);
        $this->assertDatabaseCount('tracking_histories', 260);
        $this->assertDatabaseCount('tax_records', 273);
        $this->assertDatabaseCount('currency_rates', 7);
        $this->assertSame(1718, (int) OrderItem::query()->sum('qty'));
        $this->assertSame(36, Batch::query()->where('origin', 'JP')->count());
        $this->assertSame(36, Batch::query()->where('origin', 'KR')->count());
        $this->assertSame(24, Batch::query()->where('origin', 'ID')->count());
        $this->assertSame(2, Batch::query()->doesntHave('items')->count());
        $this->assertSame(62, Batch::query()->where('show_items_public', true)->count());
        $this->assertSame(0, \App\Models\User::query()->count()); // Demo does not create a fake Owner.

        foreach (Batch::query()->with('histories')->get() as $batch) {
            $this->assertContains($batch->status, GoStatus::options($batch->origin));
            $history = $batch->histories->sortBy('created_at')->values();
            $this->assertSame(GoStatus::initial($batch->origin), $history->first()->to_status);
            $this->assertSame($batch->status, $history->last()->to_status);
            $this->assertSame($batch->status_updated_at->timestamp, $history->last()->created_at->timestamp);
        }
        foreach (TaxRecord::query()->get() as $tax) {
            $this->assertTrue(OrderItem::query()->where('batch_id', $tax->batch_id)
                ->where('customer_id', $tax->customer_id)->exists());
        }
    }

    public function test_includes_all_tracking_and_billing_edge_cases(): void
    {
        $this->artisan('db:seed', ['--class' => DemoSeeder::class])->assertSuccessful();

        $this->assertSame('CO Web', Batch::query()->where('code', 'JP 9001')->firstOrFail()->status);
        $this->assertSame('Arrived JP/KR', Batch::query()->where('code', 'JP 9002')->firstOrFail()->status);
        $this->assertSame('Shipping ID', Batch::query()->where('code', 'JP 9003')->firstOrFail()->status);
        $this->assertSame('Arrived INA', Batch::query()->where('code', 'JP 9004')->firstOrFail()->status);
        $this->assertSame('Arrived Admin', Batch::query()->where('code', 'JP 9005')->firstOrFail()->status);
        $this->assertSame('Need to CO', Batch::query()->where('code', 'ID 9001')->firstOrFail()->status);
        $this->assertSame('Otw Admin', Batch::query()->where('code', 'ID 9002')->firstOrFail()->status);
        $this->assertSame('Arrived Admin', Batch::query()->where('code', 'ID 9003')->firstOrFail()->status);
        $this->assertFalse(Batch::query()->where('code', 'JP 9001')->firstOrFail()->show_items_public);
        $this->assertTrue(Batch::query()->where('code', 'JP 9002')->firstOrFail()->show_items_public);
        $this->assertTrue(TaxRecord::query()->where('is_fixed', false)->whereNull('amount_idr')->exists());
        $this->assertTrue(TaxRecord::query()->where('is_fixed', false)->whereNotNull('amount_idr')->exists());
        $this->assertTrue(TaxRecord::query()->where('is_fixed', true)->whereNotNull('paid_at')->exists());
        $this->assertTrue(TaxRecord::query()->where('is_fixed', true)->whereNull('paid_at')->whereDate('due_at', '<', today('Asia/Jakarta'))->exists());
        $this->assertTrue(TaxRecord::query()->whereDate('due_at', '>', today('Asia/Jakarta'))->exists());
        $this->assertTrue(TaxRecord::query()->whereNull('due_at')->exists());

        $this->get('/track?q=JP%209001')->assertOk()->assertSee('JP 9001')->assertSee('Rekap belum dipublikasikan');
        $this->get('/track?q=JP%209002')->assertOk()->assertSee('JP 9002')->assertSee('Rekap pesanan');
    }

    public function test_calculator_uses_sample_rates_and_rejects_disabled_currency(): void
    {
        $this->artisan('db:seed', ['--class' => DemoSeeder::class])->assertSuccessful();

        $this->getJson('/api/calculate?currency=JPY&price=1000&order_type=personal')->assertOk()
            ->assertJsonPath('subtotal', 118000)
            ->assertJsonPath('fee', 10000)
            ->assertJsonPath('total', 128000);
        $this->getJson('/api/calculate?currency=KRW&price=1000&order_type=sharing')->assertOk()
            ->assertJsonPath('subtotal', 12150)
            ->assertJsonPath('fee', 27000)
            ->assertJsonPath('total', 39150);
        $this->getJson('/api/calculate?currency=THB&price=1000&order_type=personal')->assertStatus(422);
    }

    public function test_status_change_updates_one_shared_batch_and_appends_history(): void
    {
        $this->artisan('db:seed', ['--class' => DemoSeeder::class])->assertSuccessful();
        $owner = User::query()->create([
            'name' => 'QA Owner', 'email' => 'qa-owner@example.test', 'password' => 'password-qa-only-123',
        ]);
        $batch = Batch::query()->where('code', 'JP 9002')->firstOrFail();
        $beforeHistory = $batch->histories()->count();
        $beforeItems = $batch->items()->count();
        $this->actingAs($owner)->post(route('owner.batches.status', $batch), [
            'status' => 'Shipping ID',
        ])->assertRedirect();
        $this->assertSame('Shipping ID', $batch->fresh()->status);
        $this->assertSame($beforeHistory + 1, $batch->histories()->count());
        $this->assertSame($beforeItems, $batch->items()->count());
        $this->get('/track?q=JP%209002')->assertOk()->assertSee('Shipping ID');
    }

    public function test_rerunning_is_idempotent_and_preserves_owner_changes(): void
    {
        $this->artisan('db:seed', ['--class' => DemoSeeder::class])->assertSuccessful();
        $batch = Batch::query()->where('code', 'KR 9002')->firstOrFail();
        $batch->update(['internal_note' => DemoSeeder::MARKER.' | catatan pengujian telah diedit']);
        CurrencyRate::query()->where('currency', 'JPY')->update(['idr_rate' => '135.000000']);
        $this->artisan('db:seed', ['--class' => DemoSeeder::class])->assertSuccessful();

        $this->assertDatabaseCount('batches', 96);
        $this->assertDatabaseCount('customers', 80);
        $this->assertDatabaseCount('order_items', 658);
        $this->assertDatabaseCount('tracking_histories', 260);
        $this->assertDatabaseCount('tax_records', 273);
        $this->assertSame(DemoSeeder::MARKER.' | catatan pengujian telah diedit', $batch->fresh()->internal_note);
        $this->assertEquals(135.0, (float) CurrencyRate::query()->where('currency', 'JPY')->firstOrFail()->idr_rate);
    }

    public function test_rejects_existing_real_batch_collision_before_adding_any_data(): void
    {
        Batch::query()->create([
            'code' => 'JP 9001', 'origin' => 'JP', 'status' => 'CO Web',
            'show_items_public' => false, 'internal_note' => 'Pesanan asli',
        ]);
        try {
            $this->artisan('db:seed', ['--class' => DemoSeeder::class]);
            $this->fail('Seeder seharusnya menolak kode bentrok.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('JP 9001', $e->getMessage());
        }
        $this->assertDatabaseCount('batches', 1);
        $this->assertDatabaseCount('currency_rates', 0);
        $this->assertDatabaseCount('order_items', 0);
    }
}
