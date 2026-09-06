<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    /**
     * Keep integer-cast money columns as native ints across the app.
     */
    protected $perPage = 20;
}
