<?php

namespace App\Actions\LinkActions;

use App\Models\Link;

class ShowAction
{
    public static function execute(int $id): Link
    {
        return Link::findOrFail($id);
    }
}
