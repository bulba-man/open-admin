<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

class SortableItem extends Model
{
    protected $table = 'test_sortable_items';

    public function user()
    {
        return $this->belongsTo(SortableUser::class, 'user_id');
    }
}
