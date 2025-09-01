<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;

    protected $primaryKey = 'expense_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'expense_id',
        'date',
        'account_name',
        'vendor_name',
        'amount',
        'description',
        'status',
    ];//
}
