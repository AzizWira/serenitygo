<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\CurrencyRate;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\TaxRecord;
use App\Models\TrackingHistory;
use App\Support\GoStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * OPTIONAL fictional data, exclusively for local/staging-style QA.
 * Run `php artisan db:seed --class=DemoSeeder` in APP_ENV=local/testing.
 * Do not run on a real customer database.
 */
class DemoSeeder extends Seeder
{
    public const MARKER = '[SERENITY_GO_DEMO_V1_1]';

    private const BATCHES_PER_ORIGIN = ['JP' => 36, 'KR' => 36, 'ID' => 24];

    /** Hypothetical demonstration numbers. NOT actual or live exchange rates. */
    private const SAMPLE_RATES = [
        'JPY' => ['Japanese Yen', '118.000000', 'fixed', '2500.00', true],
        'KRW' => ['Korean Won', '12.150000', 'percent', '3.50', true],
        'USD' => ['US Dollar', '16000.000000', 'fixed', '15000.00', true],
        'EUR' => ['Euro', '17200.000000', 'fixed', '12000.00', true],
        'CNY' => ['Chinese Yuan', '2240.000000', 'fixed', '7500.00', true],
        'SGD' => ['Singapore Dollar', '12400.000000', 'percent', '2.00', true],
        'THB' => ['Thai Baht', '480.000000', 'fixed', '5000.00', false],
    ];

    private const CUSTOMER_NAMES = [
        'Alya Putri', 'Nadia Ramadhani', 'Rania Safitri', 'Dinda Maharani',
        'Nabila Zahra', 'Salsa Azzahra', 'Tiara Anindya', 'Keisha Maharani',
        'Nayla Kartika', 'Nisa Febriani', 'Amira Oktaviani', 'Aurel Anggraini',
        'Putri Maharani', 'Natasya Kirana', 'Citra Puspita', 'Kirana Ayuningtyas',
        'Maya Lestari', 'Fira Kurnia', 'Rara Prameswari', 'Ayu Pertiwi',
        'Intan Permata', 'Nindya Amelia', 'Niken Wulandari', 'Sekar Melati',
        'Rizka Anjani', 'Anisa Rahma', 'Zahra Aulia', 'Shafa Faradila',
        'Dewi Anggraeni', 'Elisa Pratama', 'Laras Savira', 'Syifa Khairunnisa',
        'Fatin Nurfadilah', 'Bella Aurelia', 'Vina Amelia', 'Hana Qonita',
        'Raisa Nuraini', 'Mega Pratiwi', 'Yasmin Alika', 'Fauziah Adelia',
        'Nanda Putri', 'Tasya Aulia', 'Sabrina Maharani', 'Lina Oktavia',
        'Kezia Natalia', 'Clara Veronika', 'Monica Felicia', 'Jessica Widya',
        'Felicia Larasati', 'Michelle Aditya', 'Yuni Astuti', 'Diah Puspitasari',
        'Febby Amanda', 'Vania Devina', 'Winda Ayu', 'Yuliana Pertiwi',
        'Ghina Azizah', 'Khalisa Salsabila', 'Najwa Syakira', 'Almira Nasywa',
        'Dea Oktavia', 'Annisa Maritza', 'Aisyah Fadila', 'Farah Nabila',
        'Daffa Saputra', 'Rafi Pradana', 'Fikri Ramadhan', 'Bagas Permana',
        'Arif Maulana', 'Reza Firmansyah', 'Rizky Wahyu', 'Fajar Mahendra',
        'Raka Aditya', 'Galang Pratama', 'Dimas Nugraha', 'Yoga Prasetyo',
        'Rangga Kurniawan', 'Bima Arya', 'Agil Ramadhan', 'Adit Surya',
    ];

    private const CATALOGS = [
        'JP' => [
            'Acrylic stand karakter', 'Photocard set edisi Jepang', 'Keychain karakter',
            'Mini plush character', 'Artbook edisi Jepang', 'Clear file A4',
            'Badge pin koleksi', 'Figure blind box', 'Trading card pack',
            'Sticker pack eksklusif', 'Tote bag official', 'Gantungan tas anime',
            'Mug ilustrasi karakter', 'Poster ukuran A3', 'Hand towel character',
            'Notebook merchandise', 'Card sleeve binder', 'Mascot plush keyring',
            'Desk calendar Jepang', 'Photo holder acrylic', 'Album limited Japan',
            'Charm strap koleksi', 'Pouch karakter', 'Rubber coaster',
        ],
        'KR' => [
            'Album K-pop versi A', 'Album K-pop versi B', 'Photocard lucky draw',
            'Official lightstick', 'Lightstick pouch', 'Season greeting set',
            'Mini poster member', 'Fansign event photocard', 'Acrylic photocard holder',
            'Trading card member', 'Official slogan towel', 'Concert postcard set',
            'Mini doll keyring', 'Sticker deco pack', 'Binder photocard',
            'Album digipack', 'Preorder benefit card', 'Magazine cover edition',
            'Fanclub membership kit', 'Official wristband', 'Concept photo book',
            'Mini bag merchandise', 'Concert ticket holder', 'Merchandise postcard',
        ],
        'ID' => [
            'Binder photocard lokal', 'Sleeve photocard 100 pcs', 'Toploader set',
            'Custom photocard holder', 'Hologram sticker sheet', 'Acrylic display stand',
            'Pouch merchandise', 'Photo card frame', 'Keychain resin custom',
            'Totebag fanmade', 'Mini display rack', 'Fanmade slogan',
            'Album sleeve protector', 'Packaging bubble wrap', 'Collector zip bag',
            'Bookmark ilustrasi', 'Pin button fanmade', 'Mini postcard set',
            'Charm keyring lokal', 'Notebook koleksi', 'Photo binder A5',
            'Lanyard fanmade', 'Sticker decoration kit', 'Premium paper sleeve',
        ],
    ];

    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoSeeder ditolak: hanya boleh pada APP_ENV=local/testing dengan database khusus pengujian.');
        }

        // A single transaction prevents a partially generated dataset.
        DB::transaction(function (): void {
            $this->assertNoCollisions();
            $this->seedCurrencies();
            $today = Carbon::today('Asia/Jakarta');
            $globalIndex = 0;

            foreach (self::BATCHES_PER_ORIGIN as $origin => $total) {
                for ($sequence = 1; $sequence <= $total; $sequence++) {
                    $code = sprintf('%s %d', $origin, 9000 + $sequence);
                    $globalIndex++;
                    // Existing demo batches are left untouched to preserve manual QA edits.
                    if (Batch::query()->where('code', $code)->exists()) {
                        continue;
                    }
                    $this->createBatch($origin, $sequence, $globalIndex, $total, $today);
                }
            }
        }, 3);

        $this->command?->info('DemoSeeder selesai. Batch JP 9001–9036, KR 9001–9036, ID 9001–9024. Data simulasi saja.');
    }

    private function assertNoCollisions(): void
    {
        $codes = [];
        foreach (self::BATCHES_PER_ORIGIN as $origin => $total) {
            for ($i = 1; $i <= $total; $i++) {
                $codes[] = $origin.' '.(9000 + $i);
            }
        }
        foreach (Batch::query()->whereIn('code', $codes)->get(['code', 'internal_note']) as $batch) {
            if (!str_starts_with((string) $batch->internal_note, self::MARKER)) {
                throw new RuntimeException('Kode '.$batch->code.' sudah digunakan order bukan demo. Proses dihentikan tanpa mengubah data.');
            }
        }
    }

    private function seedCurrencies(): void
    {
        foreach (self::SAMPLE_RATES as $currency => [$label, $rate, $feeType, $fee, $active]) {
            $record = CurrencyRate::query()->firstOrCreate(
                ['currency' => $currency],
                ['label' => $label, 'idr_rate' => $rate, 'fee_type' => $feeType,
                 'fee_value' => $fee, 'is_active' => $active,
                 'personal_order_fee_idr'=>['JPY'=>10000,'KRW'=>25000][$currency]??null,
                 'request_sharing_fee_idr'=>['JPY'=>12000,'KRW'=>27000][$currency]??null]
            );
            // The original DatabaseSeeder sets JPY/KRW/USD rate to null.
            // Fill only empty rates, never replace a rate the Owner has set.
            // Seed missing JP/KR service fees, never overwrite an Owner-configured value.
            $defaultFees = ['JPY'=>[10000,12000], 'KRW'=>[25000,27000]];
            if (isset($defaultFees[$currency])) {
                $newFees = [];
                if ($record->personal_order_fee_idr === null) $newFees['personal_order_fee_idr'] = $defaultFees[$currency][0];
                if ($record->request_sharing_fee_idr === null) $newFees['request_sharing_fee_idr'] = $defaultFees[$currency][1];
                if ($newFees) $record->update($newFees);
            }
            if ($record->idr_rate === null) {
                $changes = ['idr_rate' => $rate];
                if ((float) $record->fee_value === 0.0) {
                    $changes['fee_type'] = $feeType;
                    $changes['fee_value'] = $fee;
                }
                $record->update($changes);
            }
        }
    }

    private function createBatch(string $origin, int $sequence, int $globalIndex, int $total, Carbon $today): void
    {
        $flow = GoStatus::options($origin);
        $stage = ($sequence - 1) % count($flow);
        $opened = $today->copy()->subDays(30 + (($globalIndex * 7) % 61))->setTime(9 + ($sequence % 8), ($sequence * 7) % 60);
        $lastStatusAt = $opened->copy();
        for ($step = 1; $step <= $stage; $step++) {
            $lastStatusAt->addDays(2 + (($sequence + $step) % 4));
        }
        $empty = $sequence === $total && ($origin === 'JP' || $origin === 'ID');
        $code = $origin.' '.(9000 + $sequence);
        $batch = new Batch([
            'code' => $code,
            'origin' => $origin,
            'status' => $flow[$stage],
            'show_items_public' => !$empty && $sequence % 3 !== 1,
            'status_updated_at' => $lastStatusAt,
            'internal_note' => self::MARKER.' | Order simulasi, bukan pesanan asli. '.($empty ? 'Skenario: batch kosong.' : 'Skenario: multiple customer dan status bersama.'),
        ]);
        $batch->created_at = $opened;
        $batch->updated_at = $lastStatusAt;
        $batch->save();

        $eventAt = $opened->copy();
        TrackingHistory::query()->create([
            'batch_id' => $batch->id, 'from_status' => null,
            'to_status' => $flow[0], 'changed_by' => null, 'created_at' => $eventAt,
        ]);
        for ($step = 1; $step <= $stage; $step++) {
            $eventAt->addDays(2 + (($sequence + $step) % 4));
            TrackingHistory::query()->create([
                'batch_id' => $batch->id, 'from_status' => $flow[$step - 1],
                'to_status' => $flow[$step], 'changed_by' => null, 'created_at' => $eventAt->copy(),
            ]);
        }
        if ($empty) {
            return;
        }

        $customerCount = 2 + (($globalIndex * 7 + strlen($origin)) % 4); // 2–5 distinct customers.
        $catalog = self::CATALOGS[$origin];
        $nameCount = count(self::CUSTOMER_NAMES);
        for ($slot = 0; $slot < $customerCount; $slot++) {
            $nameIndex = (($globalIndex - 1) * 13 + $slot * 17 + strlen($origin)) % $nameCount;
            $customer = Customer::query()->firstOrCreate(['name' => 'DEMO - '.self::CUSTOMER_NAMES[$nameIndex]]);
            $itemsForCustomer = 1 + (($globalIndex + $slot * 2) % 3);
            for ($itemIndex = 0; $itemIndex < $itemsForCustomer; $itemIndex++) {
                $catalogIndex = ($globalIndex * 5 + $slot * 7 + $itemIndex * 11) % count($catalog);
                $item = new OrderItem([
                    'batch_id' => $batch->id,
                    'customer_id' => $customer->id,
                    'item_name' => $catalog[$catalogIndex],
                    'qty' => 1 + (($globalIndex + $slot + $itemIndex) % 4),
                    'internal_note' => self::MARKER.' | Barang fiktif untuk pengujian. Kategori: '.$origin.'.',
                ]);
                $item->created_at = $opened->copy()->addMinutes(10 + $slot * 20 + $itemIndex * 3);
                $item->updated_at = $item->created_at;
                $item->save();
            }
            // Not every customer/order is taxed; test both with and without tax.
            if (($origin === 'ID' && $slot % 3 === 2) || ($origin !== 'ID' && ($sequence + $slot) % 7 === 0)) {
                continue;
            }
            $taxState = ($globalIndex * 5 + $slot * 3 + 2) % 9;
            $tax = $this->taxScenario($taxState, $globalIndex, $slot, $today);
            TaxRecord::query()->create($tax + ['batch_id' => $batch->id, 'customer_id' => $customer->id]);
        }
    }

    private function taxScenario(int $state, int $globalIndex, int $slot, Carbon $today): array
    {
        $amount = 12000 + (($globalIndex * 137 + $slot * 97) % 95) * 1250;
        $posted = static fn (int $days): string => $today->copy()->subDays($days)->toDateString();
        $future = static fn (int $days): string => $today->copy()->addDays($days)->toDateString();
        $base = [
            'amount_idr' => $amount, 'is_fixed' => true,
            'posted_at' => null, 'due_at' => null, 'paid_at' => null,
            'note' => self::MARKER.' | ',
        ];
        switch ($state) {
            case 0:
                return array_replace($base, ['amount_idr' => null, 'is_fixed' => false,
                    'note' => $base['note'].'Tax belum dihitung; invoice belum di-up.']);
            case 1:
                return array_replace($base, ['amount_idr' => null, 'is_fixed' => false,
                    'posted_at' => $posted(4), 'note' => $base['note'].'Tagihan di-up, nominal belum fix.']);
            case 2:
                return array_replace($base, ['is_fixed' => false, 'posted_at' => $posted(9),
                    'note' => $base['note'].'Estimasi tax tersedia, belum final.']);
            case 3:
                return array_replace($base, ['posted_at' => $posted(22), 'due_at' => $posted(9),
                    'paid_at' => $posted(12), 'note' => $base['note'].'Tax fix, sudah lunas sebelum jatuh tempo.']);
            case 4:
                return array_replace($base, ['posted_at' => $posted(22), 'due_at' => $posted(11),
                    'note' => $base['note'].'Tax fix, belum dibayar dan melewati jatuh tempo.']);
            case 5:
                return array_replace($base, ['posted_at' => $posted(3), 'due_at' => $future(9),
                    'note' => $base['note'].'Tax fix, jatuh tempo masih mendatang.']);
            case 6:
                return array_replace($base, ['posted_at' => $posted(6),
                    'note' => $base['note'].'Tax fix, tanpa jatuh tempo yang ditetapkan.']);
            case 7:
                return array_replace($base, ['posted_at' => $posted(30), 'due_at' => $posted(16),
                    'paid_at' => $posted(5), 'note' => $base['note'].'Tax fix, sudah lunas sesudah jatuh tempo.']);
            default:
                return array_replace($base, ['posted_at' => $today->toDateString(),
                    'due_at' => $today->toDateString(),
                    'note' => $base['note'].'Tax fix, jatuh tempo hari ini.']);
        }
    }
}
