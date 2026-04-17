<?php

declare(strict_types=1);

use App\Stations\Controllers\AdminStationController;
use App\Dashboard\Api\DashboardMetricsApiController;
use App\Stations\Api\StationDataApiController;
use App\Subscriptions\Api\SubscriptionApiController;
use App\Ingestion\Api\WeatherIngestApiController;
use App\Companies\Controllers\CompanyDetailsPageController;
use App\Companies\Controllers\CompanyOverviewPageController;
use App\Contracts\Controllers\ContractDetailsPageController;
use App\Contracts\Controllers\ContractOverviewPageController;
use App\Dashboard\Controllers\DashboardPageController;
use App\Dashboard\Controllers\MetricInsightPageController;
use App\Accounts\Controllers\SessionAuthController;
use App\Stations\Controllers\StationDetailsPageController;
use App\Stations\Controllers\StationOverviewPageController;
use App\Subscriptions\Controllers\SubscriptionDetailsPageController;
use App\Subscriptions\Controllers\SubscriptionOverviewPageController;
use App\Subscriptions\Controllers\SubscriptionTypeDetailsPageController;
use App\Subscriptions\Controllers\SubscriptionTypeOverviewPageController;
use App\Core\Http\HttpRequest;
use App\Core\Http\HttpRequestRouter;
use App\Core\Http\HttpResponse;
use App\Core\Middleware\RequireAuthenticatedUser;
use App\Core\Middleware\RequireRole;

$config = require __DIR__ . '/../bootstrap.php';
$request = HttpRequest::capture();
$httpRouter = new HttpRequestRouter();
$authenticatedRoutes = [new RequireAuthenticatedUser()];
$employeeRoutes = [new RequireAuthenticatedUser(), new RequireRole(['employee', 'admin'])];
$adminRoutes = [new RequireAuthenticatedUser(), new RequireRole(['admin'])];

$httpRouter->get('/login', static fn () => (new SessionAuthController())->showLogin());
$httpRouter->post('/login', static fn (HttpRequest $request) => (new SessionAuthController())->login($request));
$httpRouter->post('/logout', static fn () => (new SessionAuthController())->logout(), $authenticatedRoutes);

$httpRouter->get('/', static fn () => (new DashboardPageController())->index(), $authenticatedRoutes);
$httpRouter->get('/stations', static fn (HttpRequest $request) => (new StationOverviewPageController())->index($request), $authenticatedRoutes);
$httpRouter->get('/stations/{stn}', static fn (HttpRequest $request, array $params) => (new StationDetailsPageController())->show($params['stn']), $authenticatedRoutes);
$httpRouter->get('/stations/{stn}/download', static fn (HttpRequest $request, array $params) => (new StationDetailsPageController())->downloadCsv($request, $params['stn']), $authenticatedRoutes);
$httpRouter->post('/admin/stations/{stn}/location', static fn (HttpRequest $request, array $params) => (new AdminStationController())->updateLocation($request, $params['stn']), $adminRoutes);

$httpRouter->get('/insights/{slug}', static fn (HttpRequest $request, array $params) => (new MetricInsightPageController())->show($params['slug']), $authenticatedRoutes);

$httpRouter->get('/subscriptions', static fn (HttpRequest $request) => (new SubscriptionOverviewPageController())->index($request), $employeeRoutes);
$httpRouter->get('/subscriptions/{identifier}', static fn (HttpRequest $request, array $params) => (new SubscriptionDetailsPageController())->show($params['identifier']), $employeeRoutes);
$httpRouter->post('/subscriptions/{identifier}/regenerate-token', static fn (HttpRequest $request, array $params) => (new SubscriptionDetailsPageController())->regenerateToken($params['identifier']), $adminRoutes);
$httpRouter->post('/subscriptions/{identifier}/update', static fn (HttpRequest $request, array $params) => (new SubscriptionDetailsPageController())->update($request, $params['identifier']), $adminRoutes);

$httpRouter->get('/subscription-types', static fn (HttpRequest $request) => (new SubscriptionTypeOverviewPageController())->index($request), $employeeRoutes);
$httpRouter->get('/subscription-types/{id}', static fn (HttpRequest $request, array $params) => (new SubscriptionTypeDetailsPageController())->show($params['id']), $employeeRoutes);
$httpRouter->post('/subscription-types/{id}/update', static fn (HttpRequest $request, array $params) => (new SubscriptionTypeDetailsPageController())->update($request, $params['id']), $adminRoutes);

$httpRouter->get('/contracts', static fn () => (new ContractOverviewPageController())->index(), $employeeRoutes);
$httpRouter->get('/contracts/{identifier}', static fn (HttpRequest $request, array $params) => (new ContractDetailsPageController())->show($params['identifier']), $employeeRoutes);

$httpRouter->get('/companies', static fn (HttpRequest $request) => (new CompanyOverviewPageController())->index($request), $employeeRoutes);
$httpRouter->get('/companies/{id}', static fn (HttpRequest $request, array $params) => (new CompanyDetailsPageController())->show($params['id']), $employeeRoutes);

$httpRouter->post('/subscriptions/create', static fn (HttpRequest $request) => (new SubscriptionOverviewPageController())->store($request), $adminRoutes);
$httpRouter->post('/subscriptions/{identifier}/send-token', static fn (HttpRequest $request, array $params) => (new SubscriptionDetailsPageController())->sendToken($params['identifier']), $adminRoutes);
$httpRouter->post('/subscriptions/{identifier}/delete', static fn (HttpRequest $request, array $params) => (new SubscriptionDetailsPageController())->destroy($params['identifier']), $adminRoutes);

$httpRouter->post('/subscription-types/create', static fn (HttpRequest $request) => (new SubscriptionTypeOverviewPageController())->store($request), $adminRoutes);
$httpRouter->post('/subscription-types/{id}/delete', static fn (HttpRequest $request, array $params) => (new SubscriptionTypeDetailsPageController())->destroy($params['id']), $adminRoutes);

$httpRouter->post('/companies/create', static fn (HttpRequest $request) => (new CompanyOverviewPageController())->store($request), $adminRoutes);
$httpRouter->post('/companies/{id}/update', static fn (HttpRequest $request, array $params) => (new CompanyDetailsPageController())->update($request, $params['id']), $adminRoutes);
$httpRouter->post('/companies/{id}/delete', static fn (HttpRequest $request, array $params) => (new CompanyDetailsPageController())->destroy($params['id']), $adminRoutes);
$httpRouter->post('/companies/{id}/contacts/create', static fn (HttpRequest $request, array $params) => (new CompanyDetailsPageController())->addContact($request, $params['id']), $adminRoutes);
$httpRouter->post('/companies/{id}/contacts/{contactId}/update', static fn (HttpRequest $request, array $params) => (new CompanyDetailsPageController())->updateContact($request, $params['id'], $params['contactId']), $adminRoutes);
$httpRouter->post('/companies/{id}/contacts/{contactId}/delete', static fn (HttpRequest $request, array $params) => (new CompanyDetailsPageController())->deleteContact($params['id'], $params['contactId']), $adminRoutes);

$httpRouter->post('/postWeatherData', static fn (HttpRequest $request) => (new WeatherIngestApiController())->store($request));
$httpRouter->post('/api/ingest/weather', static fn (HttpRequest $request) => (new WeatherIngestApiController())->store($request));
$httpRouter->get('/health', static fn () => HttpResponse::json(['status' => 'ok']));
$httpRouter->get('/api/overview', static fn () => (new DashboardMetricsApiController())->overview(), $authenticatedRoutes);
$httpRouter->get('/api/stations', static fn () => (new StationDataApiController())->index(), $authenticatedRoutes);
$httpRouter->get('/api/stations/{stn}', static fn (HttpRequest $request, array $params) => (new StationDataApiController())->show($params['stn']), $authenticatedRoutes);

$httpRouter->get('/IWA/abonnement/{identifier}/stations', static fn (HttpRequest $request, array $params) => (new SubscriptionApiController())->stations($request, $params['identifier']));
$httpRouter->get('/IWA/abonnement/{identifier}/station/{name}', static fn (HttpRequest $request, array $params) => (new SubscriptionApiController())->stationDetails($request, $params['identifier'], $params['name']));
$httpRouter->get('/IWA/abonnement/{identifier}/files', static fn (HttpRequest $request, array $params) => (new SubscriptionApiController())->files($request, $params['identifier']));
$httpRouter->get('/IWA/abonnement/{identifier}/files/{file}', static fn (HttpRequest $request, array $params) => (new SubscriptionApiController())->downloadFile($request, $params['identifier'], $params['file']));

$assetPath = null;
if (str_starts_with($request->path, '/assets/')) {
    $assetPath = realpath(__DIR__ . '/..' . $request->path);
}
$assetRoot = realpath(__DIR__ . '/../assets');
if ($assetPath !== false && $assetPath !== null && $assetRoot !== false && str_starts_with($assetPath, $assetRoot) && is_file($assetPath)) {
    $extension = pathinfo($assetPath, PATHINFO_EXTENSION);
    $mimeTypes = ['css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png', 'svg' => 'image/svg+xml'];
    header('Content-Type: ' . ($mimeTypes[$extension] ?? 'application/octet-stream'));
    readfile($assetPath);
    exit;
}

$httpRouter->dispatch($request);
