<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class JournalEntry extends BaseModel
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'transaction_id', 'reference', 'description', 'entry_date', 'posted_by'];

    protected function casts(): array
    {
        return ['entry_date' => 'date'];
    }


    public function lines() { return $this->hasMany(JournalLine::class); }
}
