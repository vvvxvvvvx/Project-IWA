# Volledige migratie van `WebapplicatieProjectIWAVersie4/dashboard-app`

Deze codebasis is aangepast zodat `WebapplicatieProjectIWAVersie4/dashboard-app` functioneel verwijderd kan worden.

## Waar de inhoud nu staat

- Oude volledige boom: `wap-app/legacy_dashboard_archive/dashboard-app-original`
- Oude documentatie: `wap-app/docs/legacy-dashboard`
- Oude broncode als naslag: `wap-app/docs/legacy-dashboard-source-reference`
- Oude JSON-opslag: `wap-app/storage/app/legacy-dashboard-data`
- Oude scripts: `wap-app/scripts/legacy-dashboard`
- Oude SQL bootstrap: `wap-app/database/sql/legacy-dashboard/create_mysql_database.sql`

## Functionele Laravel-doelen

- Dashboardfunctionaliteit staat in `app/Http/Controllers/DashboardController.php`
- Stationsfunctionaliteit staat in `app/Http/Controllers/StationController.php`
- Bedrijvenfunctionaliteit staat in `app/Http/Controllers/CompanyController.php`
- Abonnementenfunctionaliteit staat in `app/Http/Controllers/SubscriptionController.php`
- Contractfunctionaliteit staat in `app/Http/Controllers/ContractController.php`
- Views staan onder `resources/views/*`
- Routes zijn centraal verbonden in `routes/web.php` en `routes/api.php`

## Contractuitbreidingen

Contracten hebben nu ook:

- CRUD voor contracten
- overzichtspagina
- geautoriseerde gebruikers per contract (CRUD)
- queries per contract (CRUD)
- tokenbeheer
- koppeling met bestaande stations en endpoint-activiteit
