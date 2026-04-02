<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\StationCompareController;
use App\Http\Controllers\ManageStationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WeatherDataController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\Auth\SuperUserViewController;
use App\Http\Controllers\RoleTaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Let op bij wijzigingen:
| - View-bestanden gebruiken deze route-namen direct via route('...').
| - Als je hieronder een naam wijzigt, pas dan ook de gekoppelde Blade view aan.
| - Bedrijven/contactpersonen lopen via CompanyController.
| - Abonnementen/token-acties lopen via SubscriptionController.
|
*/

// Authenticatie
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Stationspagina's.
    Route::middleware('task:view_stations')->group(function () {
        Route::get('/stations', [StationController::class, 'index'])->name('stations.index');
        Route::get('/stations/compare', [StationCompareController::class, 'index'])->name('stations.compare');
        Route::get('/stations/manage', [ManageStationController::class, 'index'])->name('stations.manage.index');
        Route::get('/stations/manage/create', [ManageStationController::class, 'create'])->name('stations.manage.create');
        Route::post('/stations/manage', [ManageStationController::class, 'store'])->name('stations.manage.store');
        Route::get('/stations/manage/{stn}', [ManageStationController::class, 'show'])->name('stations.manage.show');
        Route::get('/stations/manage/{stn}/edit', [ManageStationController::class, 'edit'])->name('stations.manage.edit');
        Route::put('/stations/manage/{stn}', [ManageStationController::class, 'update'])->name('stations.manage.update');
        Route::get('/stations/{stn}', [StationController::class, 'show'])->name('stations.show');
        Route::get('/stations/{stn}/download', [StationController::class, 'download'])->name('stations.download');
    });

    // Contractpagina's.
    Route::middleware('task:view_contracts')->group(function () {
        Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
        Route::get('/contracts/{identifier}', [ContractController::class, 'show'])->name('contracts.show');
    });

    // Bedrijven + contactpersonen.
    Route::middleware('task:view_companies')->group(function () {
        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('/companies/{id}', [CompanyController::class, 'show'])->name('companies.show');
        Route::get('/companies/{id}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies/{id}', [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('/companies/{id}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    });

    // Super Users
    Route::middleware('task:manage_users')->group(function () {
        Route::get('/super-users', [SuperUserViewController::class, 'index'])->name('super-users.index');
        Route::post('/super-users/toevoegen', [SuperUserViewController::class, 'toevoegen'])->name('super-users.toevoegen');
        Route::post('/super-users/{id}', [SuperUserViewController::class, 'verwijder'])->name('super-users.verwijder')->whereNumber('id');
        Route::post('/super-users/{id}/bewerken', [SuperUserViewController::class, 'bewerkenVerify'])->name('super-users.bewerken')->whereNumber('id');
    });

    // Roltaken beheer
    Route::middleware('task:manage_roles')->group(function () {
        Route::get('/role-tasks', [RoleTaskController::class, 'index'])->name('role-tasks.index');
        Route::post('/role-tasks', [RoleTaskController::class, 'store'])->name('role-tasks.store');
        Route::put('/role-tasks/{id}', [RoleTaskController::class, 'update'])->name('role-tasks.update')->whereNumber('id');
        Route::delete('/role-tasks/{id}', [RoleTaskController::class, 'destroy'])->name('role-tasks.destroy')->whereNumber('id');
    });

    // Contactpersonen horen functioneel bij een bedrijf en schrijven naar relations.
    Route::middleware('task:view_companies')->group(function () {
        Route::get('/companies/{id}/contacts/create', [CompanyController::class, 'createContact'])->name('companies.contacts.create');
        Route::post('/companies/{id}/contacts', [CompanyController::class, 'storeContact'])->name('companies.contacts.store');
        Route::get('/companies/{companyId}/contacts/{contactId}/edit', [CompanyController::class, 'editContact'])->name('companies.contacts.edit');
        Route::put('/companies/{companyId}/contacts/{contactId}', [CompanyController::class, 'updateContact'])->name('companies.contacts.update');
        Route::delete('/companies/{companyId}/contacts/{contactId}', [CompanyController::class, 'destroyContact'])->name('companies.contacts.destroy');
    });

    // Abonnementen + token-acties.
    Route::middleware('task:view_subscriptions')->group(function () {
        Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/subscriptions/create', [SubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('/subscriptions/{identifier}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::get('/subscriptions/{identifier}/edit', [SubscriptionController::class, 'edit'])->name('subscriptions.edit');
        Route::put('/subscriptions/{identifier}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::delete('/subscriptions/{identifier}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');
        Route::post('/subscriptions/{identifier}/token', [SubscriptionController::class, 'regenerateToken'])->name('subscriptions.token.regenerate');
        Route::post('/subscriptions/{identifier}/token-sent', [SubscriptionController::class, 'markTokenSent'])->name('subscriptions.token.sent');
    });

    // Abonnementtypes.
    Route::middleware('task:view_subscription_types')->group(function () {
        Route::get('/subscription-types', [SubscriptionController::class, 'typesIndex'])->name('subscription-types.index');
        Route::get('/subscription-types/create', [SubscriptionController::class, 'typesCreate'])->name('subscription-types.create');
        Route::post('/subscription-types', [SubscriptionController::class, 'typesStore'])->name('subscription-types.store');
        Route::get('/subscription-types/{id}/edit', [SubscriptionController::class, 'typesEdit'])->name('subscription-types.edit');
        Route::put('/subscription-types/{id}', [SubscriptionController::class, 'typesUpdate'])->name('subscription-types.update');
        Route::delete('/subscription-types/{id}', [SubscriptionController::class, 'typesDestroy'])->name('subscription-types.destroy');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('account.profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('account.profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('account.profile.destroy');

    Route::post('/weather-data', [WeatherDataController::class, 'store'])->name('weather-data.store');
    Route::get('/measurements', [MeasurementController::class, 'index'])->name('measurements.index');
});
