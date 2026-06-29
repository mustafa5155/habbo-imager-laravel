<?php

namespace App\Providers;

use App\Http\Livewire\HabboImager;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Livewire::component('habbo-imager', HabboImager::class);
    }
}
