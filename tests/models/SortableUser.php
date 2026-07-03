<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

class SortableUser extends Model
{
    protected $table = 'test_users';

    public function sortableItems()
    {
        return $this->hasMany(SortableItem::class, 'user_id');
    }
}
