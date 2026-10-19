<?php

namespace Tests\Feature;

use App\Models\{Batch, Customer, OrderItem, TaxRecord, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBatchRecapTest extends TestCase
{
    use RefreshDatabase;

    private function fixtures(): array
    {
        $batch = Batch::create([
            'code' => 'JP 88', 'origin' => 'JP', 'status' => 'Shipping ID',
            'show_items_public' => false, 'show_customer_names_public' => false,
            'internal_note' => 'INTERNAL-SECRET-BATCH', 'status_updated_at' => now(),
        ]);
        $alice = Customer::create(['name' => 'Alya Rahasia']);
        $bima = Customer::create(['name' => 'Bima Rahasia']);
        OrderItem::create(['batch_id' => $batch->id, 'customer_id' => $alice->id,
            'item_name' => 'Card Pack Galaxy', 'qty' => 2, 'internal_note' => 'PRIVATE-NOTE-ITEM']);
        OrderItem::create(['batch_id' => $batch->id, 'customer_id' => $alice->id,
            'item_name' => 'Acrylic Stand', 'qty' => 1]);
        OrderItem::create(['batch_id' => $batch->id, 'customer_id' => $bima->id,
            'item_name' => 'Photo Card', 'qty' => 4]);
        TaxRecord::create(['batch_id' => $batch->id, 'customer_id' => $alice->id,
            'amount_idr' => 99000, 'is_fixed' => true, 'note' => 'BILLING-PRIVATE-SECRET']);
        return [$batch, $alice, $bima];
    }

    public function test_status_visible_when_items_are_private(): void
    {
        $this->fixtures();
        $this->get('/track?q=JP%2088')->assertOk()
            ->assertSee('Shipping ID')->assertSee('Rekap belum dipublikasikan')
            ->assertDontSee('Card Pack Galaxy')->assertDontSee('Alya Rahasia')
            ->assertDontSee('BILLING-PRIVATE-SECRET')->assertDontSee('INTERNAL-SECRET-BATCH');
    }

    public function test_public_recap_has_multiple_buyers_search_filters_qty_and_aliases_by_default(): void
    {
        [$batch] = $this->fixtures();
        $batch->update(['show_items_public' => true]);

        $this->get('/track?q=JP%2088')->assertOk()
            ->assertSee('Rekap pesanan')->assertSee('NAMA BARANG')
            ->assertSee('Card Pack Galaxy')->assertSee('Acrylic Stand')->assertSee('Photo Card')
            ->assertSee('batchItemSearch')->assertSee('batchCustomerFilter')->assertSee('batchItemSort')
            ->assertSee('Pembeli 01')->assertSee('Pembeli 02')
            ->assertDontSee('Alya Rahasia')->assertDontSee('Bima Rahasia')
            ->assertDontSee('BILLING-PRIVATE-SECRET')->assertDontSee('PRIVATE-NOTE-ITEM')
            ->assertDontSee('99000');
    }

    public function test_owner_can_explicitly_publish_names_without_exposing_tax(): void
    {
        [$batch] = $this->fixtures();
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'hashed-test-password']);
        $this->actingAs($owner)->put(route('owner.batches.update', $batch), [
            'show_items_public' => 1, 'show_customer_names_public' => 1,
        ])->assertRedirect(route('owner.batches.show', $batch));
        $this->assertTrue($batch->fresh()->show_customer_names_public);
        $this->get('/track?q=JP%2088')->assertOk()
            ->assertSee('Alya Rahasia')->assertSee('Bima Rahasia')
            ->assertDontSee('BILLING-PRIVATE-SECRET')->assertDontSee('99000')
            ->assertDontSee('PRIVATE-NOTE-ITEM');
    }

    public function test_cannot_publish_names_without_publishing_items(): void
    {
        [$batch] = $this->fixtures();
        $owner = User::create(['name' => 'Owner', 'email' => 'owner2@example.test', 'password' => 'hashed-test-password']);
        $this->actingAs($owner)->put(route('owner.batches.update', $batch), [
            'show_customer_names_public' => 1,
        ])->assertRedirect();
        $this->assertFalse($batch->fresh()->show_customer_names_public);
        $this->get('/track?q=JP%2088')->assertOk()->assertDontSee('Alya Rahasia');
    }

    public function test_new_batch_has_private_recap_by_default(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner3@example.test', 'password' => 'hashed-test-password']);
        $this->actingAs($owner)->post(route('owner.batches.store'), ['code' => 'KR 77'])
            ->assertRedirect();
        $batch = Batch::where('code', 'KR 77')->firstOrFail();
        $this->assertFalse($batch->show_items_public);
        $this->assertFalse($batch->show_customer_names_public);
    }
}
