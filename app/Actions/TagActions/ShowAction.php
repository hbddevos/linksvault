<?php

namespace App\Actions\TagActions;

use App\Models\Tag;

class ShowAction
{
    public static function execute(int $id): Tag
    {
        return Tag::findOrFail($id);
    }
}
