<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogLastLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
public function handle(Login $event): void
{
    // Log para debug
    logger('User class: ' . get_class($event->user));
    logger('User id: ' . $event->user->id);

    \App\Models\User::where('id', $event->user->id)
        ->update(['last_login_at' => now()]);
}


}
