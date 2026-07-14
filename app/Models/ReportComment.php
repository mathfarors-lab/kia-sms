<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class ReportComment extends Model
{
    use BelongsToBranch;

    protected $fillable = ['category', 'text_en', 'text_km'];
}
