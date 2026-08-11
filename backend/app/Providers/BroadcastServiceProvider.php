<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Use JWT guard
        Broadcast::routes(['middleware' => ['auth:api']]);

        require base_path('routes/channels.php');
    }
}
