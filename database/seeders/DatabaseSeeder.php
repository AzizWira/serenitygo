<?php
namespace Database\Seeders;
use App\Models\CurrencyRate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['JPY'=>'Japanese Yen','KRW'=>'Korean Won','USD'=>'US Dollar'] as $currency=>$label) {
            CurrencyRate::firstOrCreate(['currency'=>$currency], ['label'=>$label,'idr_rate'=>null,'fee_type'=>'fixed','fee_value'=>0,'personal_order_fee_idr'=>['JPY'=>10000,'KRW'=>25000][$currency]??null,'request_sharing_fee_idr'=>['JPY'=>12000,'KRW'=>27000][$currency]??null,'is_active'=>true]);
        }
        $email = trim((string) env('OWNER_EMAIL'));
        $password = (string) env('OWNER_PASSWORD');
        if ($email === '' && $password === '') { $this->command?->warn('Owner belum dibuat. Isi OWNER_EMAIL dan OWNER_PASSWORD (min. 12 karakter) lalu jalankan db:seed.'); return; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
            throw new RuntimeException('OWNER_EMAIL harus valid dan OWNER_PASSWORD minimal 12 karakter.');
        }
        if (User::query()->where('email','!=',$email)->exists()) throw new RuntimeException('Sudah ada Owner dengan email berbeda. Hentikan untuk menjaga kebijakan satu Owner.');
        User::firstOrCreate(['email'=>$email], ['name'=>env('OWNER_NAME','Serenity Owner'), 'password'=>Hash::make($password)]);
        // Dataset pengujian hanya diaktifkan secara eksplisit; tidak untuk database asli.
        if (filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(DemoSeeder::class);
        }
    }
}
