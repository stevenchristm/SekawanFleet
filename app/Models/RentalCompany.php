<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalCompany extends Model
{
    protected $fillable = ['name', 'phone', 'address'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
