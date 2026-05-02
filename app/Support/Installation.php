<?php

namespace App\Support;

class Installation
{
    public static function complete(): bool
    {
        $installed = env('APP_INSTALLED');

        if ($installed !== null) {
            return filter_var($installed, FILTER_VALIDATE_BOOL);
        }

        return is_file(base_path('.env')) && filled(config('app.key'));
    }
}
