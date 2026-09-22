<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'shop_id', 'start_time', 'end_time', 
        'starting_cash', 'total_sales', 'total_income', 'total_expense', 'expected_balance', 
        'actual_cash', 'difference', 'status', 'note'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function shop() { return $this->belongsTo(Shop::class); }
}
