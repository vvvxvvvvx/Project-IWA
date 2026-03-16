# Dashboard app

## Starten

```bash
cd dashboard-app
php -S 127.0.0.1:8080 server-router.php
```

Controleer daarna:

```text
http://127.0.0.1:8080/health
```

## Accounts

- admin: `admin@iwa.local` / `admin123`
- medewerker: `medewerker@iwa.local` / `medewerker123`
- klant: `klant@iwa.local` / `klant123`

## Navigatie

- Dashboard
- Stations
- Abonnementen
- Aanbod
- Contracten
- Bedrijven

## Architectuur

Demo:

**Generator → ingest endpoint → opslaglagen in JSON → webapp**

Doelarchitectuur:

**Generator → SQL database → Laravel webapp**

Zie ook:

- `docs/MYSQL_WORKBENCH_IMPORT.md`
- `docs/LARAVEL_PORTING_GUIDE.md`

## Opmerking over locaties

De generator-payload bevat zelf geen plaatsnaam of land. Daarom wordt stationinformatie verrijkt met metadata uit de database dump. Als er geen mapping is, blijft de locatie onbekend.


## Bron per domein

- **Abonnementen** worden in de huidige opzet getoond vanuit geïmporteerde bestanden / file-transfer data.
- **Contracten** worden gepresenteerd als eigen sectie met nadruk op REST-API gebruik en endpoint-activiteit.

Zo blijft het onderscheid zichtbaar tussen commerciële abonnementinformatie en contractinformatie die gekoppeld is aan API-toegang.


## Code-structuur

De code is nu per domein gegroepeerd zodat direct zichtbaar is waar functionaliteit thuishoort:

- `src/Core` – HTTP, middleware en gedeelde support
- `src/Accounts` – login, accounts en gebruikersauthenticatie
- `src/Dashboard` – dashboardpagina, metrics en dashboard-API
- `src/Stations` – stationsoverzicht, stationdetails en stationbeheer
- `src/Ingestion` – generator-ingest, weerdata-opslag, correcties en batches
- `src/Subscriptions` – abonnementen, abonnementtypes, tokens en REST-API
- `src/Companies` – bedrijven en contactpersonen
- `src/Contracts` – contractpagina
- `views/accounts`, `views/dashboard`, `views/stations`, `views/subscriptions`, `views/companies`, `views/contracts`, `views/shared` – views per domein


## Echte MySQL-configuratie

Deze versie is nu ingericht voor **echte MySQL via PDO**.

### 1. Database aanmaken
Voer in MySQL Workbench of de MySQL command line uit:

```sql
SOURCE database/create_mysql_database.sql;
```

of open het bestand `database/create_mysql_database.sql` en voer het uit.

### 2. Controleer je verbinding
Standaardconfiguratie:

- host: `127.0.0.1`
- port: `3306`
- database: `iwa_dashboard`
- username: `root`
- password: leeg

Pas dit aan via omgevingsvariabelen of gebruik `.env.example` als basis.

### 3. Seeddata naar MySQL schrijven
Voer daarna uit:

```bash
php scripts/mysql_bootstrap.php
```

Hiermee worden de datasets uit `storage/data/*.json` in de MySQL tabel `app_json_store` gezet.

### 4. Webapp starten
```bash
cd dashboard-app
php -S 127.0.0.1:8080 server-router.php
```

Daarna werkt de keten technisch als:

**Generator -> MySQL database -> Webapplicatie**

### 5. Generator starten
De generator blijft posten naar:

```text
http://127.0.0.1:8080/postWeatherData
```

Alle writes en reads lopen dan via de MySQL PDO-laag.


## Architectuurdiagram

```text
Generator -> /postWeatherData -> validatie & correctie -> PDO/MySQL -> Dashboard / REST-API
```

Uitgebreide versie:

```text
+------------------+        HTTP POST         +-----------------------+
| Java Generator   |  --------------------->  | Ingest endpoint       |
| (cluster JSON)   |   /postWeatherData       | dashboard-app         |
+------------------+                          +-----------+-----------+
                                                          |
                                                          v
                                             +-------------------------+
                                             | Validatie & correctie   |
                                             | - ontbrekende waarden   |
                                             | - piekdetectie          |
                                             | - correctielog          |
                                             +-----------+-------------+
                                                         |
                                                         v
                                             +-------------------------+
                                             | PDO / MySQL opslaglaag  |
                                             +-----------+-------------+
                                                         |
                             +---------------------------+--------------------------+
                             |                                                      |
                             v                                                      v
                 +----------------------+                              +---------------------------+
                 | Dashboard / Webapp   |                              | REST API voor klanten     |
                 | - dashboard          |                              | /IWA/abonnement/...       |
                 | - stations           |                              | - stations                |
                 | - abonnementen       |                              | - stationdetail           |
                 | - contracten         |                              | - files                   |
                 +----------------------+                              +---------------------------+
```

## Hoe het praktisch werkt

1. De generator stuurt een JSON-cluster naar `POST /postWeatherData`.
2. De webapp valideert de payload en corrigeert onbetrouwbare waarden.
3. Oorspronkelijke waarden en correcties worden apart gelogd.
4. De gegevens worden via **PDO** opgeslagen in de database.
5. Het dashboard leest de data uit de database en toont die per domein.
6. De klant-REST-API leest dezelfde database voor abonnement- en contractgerelateerde endpoints.

Zie ook `docs/ARCHITECTURE_AND_FLOW.md`.


## Start in 4 stappen

1. Maak de MySQL database aan met `database/create_mysql_database.sql`.
2. Run `php scripts/mysql_bootstrap.php`.
3. Start de webapp met `php -S 127.0.0.1:8080 server-router.php`.
4. Start daarna de generator zodat de live data binnenkomt.


## Runtime data resetten

De meegeleverde weerdata-seed is verwijderd uit de runtime-datasets. Daardoor is de live sectie nu afhankelijk van de generator.

Gebruik bij een schone start:

```bash
php scripts/reset_generator_runtime_data.php
```

Start daarna de webapp en vervolgens de generator. De commerciële importdata (bedrijven, contacten, abonnementen, landen en station-metadata) blijft behouden.
