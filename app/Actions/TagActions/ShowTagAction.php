<?php

namespace App\Actions\TagActions;

use App\Models\Tag;

class ShowTagAction
{
    public static function execute(int $id): Tag
    {
        return Tag::findOrFail($id);
    }
}
