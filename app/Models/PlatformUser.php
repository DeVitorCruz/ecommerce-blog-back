<?php

namespace App\Models;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlatformUser extends User
{
    protected $connection = 'mercatura';
    protected $table = 'users';
    protected string $guard_name = 'platform';

    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class, 'user_id');
    }
}
