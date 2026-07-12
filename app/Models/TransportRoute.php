<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToBranch;

class TransportRoute extends Model
{
    use BelongsToBranch;
    protected $fillable = ['name', 'description', 'fare', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'route_id');
    }
}
