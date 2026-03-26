<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| Settings routes
|--------------------------------------------------------------------------
|
| Deze routes laden Volt/Livewire pagina's uit resources/views/pages/settings.
| Als je hieronder een component-naam wijzigt, pas dan ook de bestandsnaam in
| resources/views/pages/settings aan. De bestandsnamen zijn expres volledig
| uitgeschreven zodat duidelijk is waar iedere instellingenpagina voor dient.
|
*/

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    // Gebruikersprofiel aanpassen.
    Route::livewire('settings/profile', 'pages::settings.user-profile-settings')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Wachtwoordbeveiliging aanpassen.
    Route::livewire('settings/password', 'pages::settings.password-security-settings')->name('user-password.edit');

    // Kleur/uiterlijk van de interface aanpassen.
    Route::livewire('settings/appearance', 'pages::settings.interface-appearance-settings')->name('appearance.edit');

    // Twee-factor-authenticatie beheren.
    Route::livewire('settings/two-factor', 'pages::settings.two-factor-authentication-settings')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
