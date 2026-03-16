# Architectuur en werking

## Overzicht

De applicatie werkt volgens deze keten:

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
                                             | PDO / SQL opslaglaag    |
                                             | - MySQL (primair)       |
                                             | - SQLite (fallback/dev) |
                                             +-----------+-------------+
                                                         |
                                                         v
                           +-----------------------------+-----------------------------+
                           |                                                           |
                           v                                                           v
              +---------------------------+                               +---------------------------+
              | Webapp / Dashboard        |                               | REST API voor klanten     |
              | - dashboard               |                               | /IWA/abonnement/...       |
              | - stations                |                               | - stationslijst           |
              | - abonnementen            |                               | - stationdetail           |
              | - contracten              |                               | - files/downloads         |
              +---------------------------+                               +---------------------------+
```

## Domeinen in de webapp

- **Accounts**: login, sessie, rollen
- **Dashboard**: monitoring, KPI's, uitlegpagina's
- **Stations**: stationsoverzicht, detail, CSV-export
- **Ingestion**: generator-ingest, correcties, batches
- **Subscriptions**: abonnementen, aanbod, tokens, klant-REST-API
- **Companies**: bedrijven en contactpersonen
- **Contracts**: contractoverzicht en REST-API activiteit

## Dataherkomst per sectie

- **Dashboard / stations**: komt uit generator-data via ingest
- **Abonnementen**: getoond vanuit file-transfer / geïmporteerde datasets
- **Contracten**: getoond als eigen domein met nadruk op REST-API gebruik en endpoint-activiteit

## Startvolgorde

1. MySQL database aanmaken
2. Bootstrap-script uitvoeren om seeddata in MySQL te zetten
3. Webapp starten
4. Generator starten
5. Dashboard openen

## Technische flow in 1 zin

**Generator -> ingest endpoint -> validatie/correctie -> PDO/MySQL -> dashboard en klant-API**
