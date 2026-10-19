<?php
namespace Tests\Feature;

use App\Models\{Batch, CalculatorSetting, CurrencyRate, TrackingHistory, User};
use App\Support\PriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Release120Test extends TestCase
{
    use RefreshDatabase;

    private function seedRates(): void
    {
        CurrencyRate::query()->create(['currency'=>'JPY','label'=>'Japanese Yen',
            'idr_rate'=>1, 'fee_type'=>'fixed','fee_value'=>999999, 'personal_order_fee_idr'=>10000,
            'request_sharing_fee_idr'=>12000,'is_active'=>true]);
        CurrencyRate::query()->create(['currency'=>'KRW','label'=>'Korean Won',
            'idr_rate'=>1,'fee_type'=>'fixed','fee_value'=>999999,'personal_order_fee_idr'=>25000,
            'request_sharing_fee_idr'=>27000,'is_active'=>true]);
        CurrencyRate::query()->create(['currency'=>'USD','label'=>'US Dollar',
            'idr_rate'=>1,'fee_type'=>'fixed','fee_value'=>0,'is_active'=>true]);
        CurrencyRate::query()->where('currency','JPY')->update(['idr_rate'=>1]);
        CurrencyRate::query()->where('currency','KRW')->update(['idr_rate'=>1]);
    }

    private function owner(): User
    {
        return User::query()->create([
            'name'=>'Test Owner', 'email'=>'owner@example.test',
            'password'=>Hash::make('a long secure test password'),
        ]);
    }

    public function test_no_owner_login_link_on_public_pages_and_only_secret_route_exists(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Owner Login')->assertDontSee('secretkya');
        $this->get('/owner/login')->assertNotFound();
        $this->get('/secretkya')->assertOk()->assertSee('Masuk ke Dashboard');
        $this->get('/owner')->assertRedirect(route('login'));
        $this->post('/secretkya',['email'=>'no@example.test','password'=>'wrong'])
            ->assertSessionHasErrors('email');
    }

    public function test_authenticated_owner_opening_secret_login_redirects_to_owner_not_home(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->get('/secretkya')
            ->assertRedirect(route('owner.dashboard'));
        $this->actingAs($owner)->post('/secretkya', [
            'email' => 'ignored@example.test', 'password' => 'ignored',
        ])->assertRedirect(route('owner.dashboard'));
        $this->actingAs($owner)->get('/owner')->assertOk();
    }

    public function test_guest_owner_entry_goes_to_secret_login(): void
    {
        $this->get('/owner')->assertRedirect(route('login'));
        $this->get('/secretkya')->assertOk()->assertSee('Masuk ke Dashboard');
    }

    public function test_login_still_requires_password_and_redirects_to_owner_dashboard(): void
    {
        $user=$this->owner();
        $this->post('/secretkya',['email'=>$user->email,'password'=>'a long secure test password'])
            ->assertRedirect(route('owner.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_order_type_fees_replace_legacy_fees_for_jp_and_kr(): void
    {
        $this->seedRates();
        $this->getJson('/api/calculate?currency=JPY&price=53100&order_type=personal')
            ->assertOk()->assertJsonPath('fee',10000)->assertJsonPath('total',63100)
            ->assertJsonMissingPath('rounding_enabled')->assertJsonMissingPath('rounding_increment');
        $this->getJson('/api/calculate?currency=JPY&price=53100&order_type=sharing')
            ->assertOk()->assertJsonPath('fee',12000)->assertJsonPath('total',65100);
        $this->getJson('/api/calculate?currency=KRW&price=53100&order_type=personal')
            ->assertOk()->assertJsonPath('fee',25000)->assertJsonPath('total',78100);
        $this->getJson('/api/calculate?currency=KRW&price=53100&order_type=sharing')
            ->assertOk()->assertJsonPath('fee',27000)->assertJsonPath('total',80100);
    }

    public function test_only_owner_can_enable_rounding_and_public_cannot_see_setting(): void
    {
        $this->seedRates();
        $payload=['rounding_enabled'=>1,'rounding_increment'=>1000];
        $this->put(route('owner.settings.rounding'),$payload)->assertRedirect(route('login'));
        $this->assertFalse(CalculatorSetting::query()->firstOrFail()->rounding_enabled);
        $owner=$this->owner();
        $this->actingAs($owner)->put(route('owner.settings.rounding'),$payload)->assertRedirect();
        $this->assertTrue(CalculatorSetting::query()->firstOrFail()->rounding_enabled);
        $this->getJson('/api/calculate?currency=JPY&price=53100&order_type=personal')
            ->assertOk()->assertJsonPath('total',64000)
            ->assertJsonMissingPath('rounding_enabled')->assertJsonMissingPath('rounding_increment');
        $this->get('/')->assertOk()->assertDontSee('Pengaturan Pembulatan');
        $this->actingAs($owner)->get(route('owner.settings.index'))
            ->assertOk()->assertSee('Pengaturan Pembulatan');
    }

    public function test_rounding_custom_increment_and_bad_value_validation(): void
    {
        $owner=$this->owner();
        $this->actingAs($owner)->put(route('owner.settings.rounding'),[
            'rounding_enabled'=>1,'rounding_increment'=>500,
        ])->assertRedirect();
        $this->assertSame(63500.0,PriceCalculator::calculate(53100,1,10000,true,500));
        $this->actingAs($owner)->put(route('owner.settings.rounding'),[
            'rounding_enabled'=>1,'rounding_increment'=>0,
        ])->assertSessionHasErrors('rounding_increment');
    }

    public function test_legacy_statuses_are_normalized_in_migration_and_display(): void
    {
        $batch=Batch::query()->create(['code'=>'JP 1','origin'=>'JP','status'=>'Shipping ID','show_items_public'=>false]);
        TrackingHistory::query()->create(['batch_id'=>$batch->id,'from_status'=>'Arrived JP/KR',
            'to_status'=>'Shipping ID','created_at'=>now()]);
        $this->get('/track?q=JP%201')->assertOk()->assertSee('Shipping ID')
            ->assertDontSee('OTW INA');
        $this->assertSame('Arrived Admin',\App\Support\GoStatus::label('Arrived admin'));
    }

    public function test_unconfigured_currencies_are_not_publicly_available(): void
    {
        $this->seedRates();
        $this->get('/')->assertOk()->assertDontSee('value="USD"',false);
        $this->getJson('/api/calculate?currency=USD&price=100&order_type=personal')
            ->assertUnprocessable();
    }
}
