<?php

namespace App\Providers;

use App\Support\ImageOwners;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The superadmin implicitly holds every permission.
        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });

        // Store lore morphs as a short alias ("kota", "character") instead of
        // the FQCN, so *_type lines up with the {tier} used in routes.
        // Deliberately partial: classes outside this map (e.g. User in Spatie's
        // model_has_roles) keep storing their class name.
        Relation::morphMap(ImageOwners::types());
    }
}
