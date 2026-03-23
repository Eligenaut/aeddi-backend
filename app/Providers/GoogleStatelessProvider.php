<?php

namespace App\Providers;

use Laravel\Socialite\Two\GoogleProvider;

class GoogleStatelessProvider extends GoogleProvider
{
    protected function hasInvalidState(): bool
    {
        return false;
    }
}
