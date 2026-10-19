<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalculatorSetting extends Model
{
    protected $fillable = ['rounding_enabled', 'rounding_increment'];

    protected function casts(): array
    {
        return ['rounding_enabled' => 'boolean', 'rounding_increment' => 'integer'];
    }
}
