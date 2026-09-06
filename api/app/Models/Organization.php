<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Organization extends BaseModel
{
    use HasUuids;

    protected $fillable = ['name', 'code', 'registration_number', 'phone', 'email', 'address', 'logo_url', 'currency', 'status'];


    public function users() { return $this->hasMany(User::class); }

    public function members() { return $this->hasMany(Member::class); }
}
