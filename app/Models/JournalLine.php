<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class JournalLine extends BaseModel
{
    use HasUuids;

    protected $fillable = ['journal_entry_id', 'account_id', 'debit', 'credit'];

    protected function casts(): array
    {
        return ['debit' => 'int', 'credit' => 'int'];
    }


    public function entry() { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }

    public function account() { return $this->belongsTo(Account::class); }
}
