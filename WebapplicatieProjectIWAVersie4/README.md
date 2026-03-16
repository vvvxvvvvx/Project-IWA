# IWA Integrated Platform

Deze codebase is opgezet rond de informatiestroom:

**Generator → opslaglaag / database → webapplicatie**

In de huidige demo-versie werkt dat als volgt:

- de **Java generator** verstuurt JSON naar `POST /postWeatherData`
- de **dashboard-app** valideert en corrigeert metingen
- de verwerkte data wordt in de demo opgeslagen in JSON-datasets in `dashboard-app/storage/data`
- de webapp leest die datasets uit en presenteert ze in dashboard-, analyse-, abonnementen- en contractpagina's

Voor de onderwijs- of productiedoelstelling kun je dit later migreren naar:

**Generator → MySQL / MariaDB → Laravel webapp (doelarchitectuur)**

Daarvoor zijn al extra documenten aanwezig in:

- `dashboard-app/docs/MYSQL_WORKBENCH_IMPORT.md`
- `dashboard-app/docs/LARAVEL_PORTING_GUIDE.md`

## Snelle start

### 1. Start de webapp

```bash
cd dashboard-app
php -S 127.0.0.1:8080 server-router.php
```

Test daarna:

```text
http://127.0.0.1:8080/health
```

### 2. Start de generator

Open een tweede terminal:

```bash
cd generator-java
build.bat
run.bat
```

De generator post standaard naar:

```text
http://127.0.0.1:8080/postWeatherData
```

## Generator-handleiding

### Configuratiebestand

Bestand:

```text
generator-java/settings.conf
```

Belangrijkste regels:

```text
client_http_hostname: 127.0.0.1
client_http_port: 8080
client_http_path: postWeatherData
```

### Praktische volgorde

1. Start eerst de webapp.
2. Controleer `http://127.0.0.1:8080/health`.
3. Start pas daarna de generator.
4. Open daarna het dashboard en bekijk live updates.

### Wat de generator verstuurt

De generator verstuurt per batch een JSON-cluster met ongeveer tien weerstations, bijvoorbeeld:

```json
{
  "WEATHERDATA": [
    {
      "STN": "637200",
      "DATE": "2022-02-09",
      "TIME": "00:00:58",
      "TEMP": 10.1,
      "DEWP": 1.5,
      "STP": 984.1,
      "SLP": 1012.6,
      "VISIB": 23.4,
      "WDSP": 13.8,
      "PRCP": 0.00,
      "SNDP": 0.0,
      "FRSHTT": "000000",
      "CLDC": 96.8,
      "WNDDIR": 228
    }
  ]
}
```

### Waar je de data daarna ziet

- dashboard-overzicht
- primaire stationspagina
- stationdetailpagina
- registratie & controles
- abonnementen / contracten / REST-API secties

## Accounts

### Admin
- e-mail: `admin@iwa.local`
- wachtwoord: `admin123`
- rechten: alles zien + wijzigen

### Medewerker
- e-mail: `medewerker@iwa.local`
- wachtwoord: `medewerker123`
- rechten: weerinformatie, registratie, abonnementen, contracten

### Klant
- e-mail: `klant@iwa.local`
- wachtwoord: `klant123`
- rechten: alleen relevante weerinformatie

## Wat er functioneel is toegevoegd

### Dashboard / analyse
- klikbare hoofdkaarten voor Stations online, Readings, Gemiddelde temperatuur en Datakwaliteit
- aparte stationspagina naast het dashboard
- stationdetailpagina met trendgrafiek en CSV-export per periode
- realtime refresh van dashboardgegevens

### Klanten / abonnementen / contracten
- abonnementenoverzicht
- aanbodpagina voor abonnementtypes
- detailpagina per abonnementtype met gekoppelde klanten
- contractenoverzicht
- contractdetailpagina met REST-API bron en activiteit
- bedrijfsoverzicht en bedrijfdetail met contactpersonen

### REST-API
- `GET /IWA/abonnement/{identifier}/stations?token=...`
- `GET /IWA/abonnement/{identifier}/station/{naam}?token=...`
- `GET /IWA/abonnement/{identifier}/files?token=...`
- `GET /IWA/abonnement/{identifier}/files/{bestand}?token=...`

## Database / SQL / Workbench

De meegeleverde dump `IWADBDump.zip` is bedoeld voor gebruik in MySQL Workbench. De aanbevolen werkwijze is:

1. importeer de SQL-dump in MySQL Workbench
2. controleer tabellen zoals stations, bedrijven, abonnementen en contractgerelateerde gegevens
3. gebruik de documentatie in `dashboard-app/docs/` om een Laravel- of PDO-implementatie te koppelen

In deze demo-webapp wordt nog gewerkt met JSON-datasets die uit de dump zijn afgeleid, zodat de applicatie lokaal direct werkt zonder extra database-installatie.

## Laravel

Deze code is geen volledige Laravel-app. De structuur is wel opgesplitst in controllers, services, repositories en views zodat migratie naar Laravel later mogelijk blijft.

Gebruik de porting guide als je wilt doorgroeien naar:

**Generator → SQL database → Laravel webapp**


## Bron per domein

- **Abonnementen** worden in de huidige opzet getoond vanuit geïmporteerde bestanden / file-transfer data.
- **Contracten** worden gepresenteerd als eigen sectie met nadruk op REST-API gebruik en endpoint-activiteit.

Zo blijft het onderscheid zichtbaar tussen commerciële abonnementinformatie en contractinformatie die gekoppeld is aan API-toegang.


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
