# MySQL Workbench import en koppeling

De meegeleverde SQL dump is bedoeld voor import in MySQL Workbench in een database met naam `project_web`.

## Aanpak

1. Maak in Workbench een verbinding met je lokale MySQL-server.
2. Voer de SQL-bestanden uit in deze volgorde:
   - `project_web_country.sql`
   - `project_web_companies.sql`
   - `project_web_userroles.sql`
   - `project_web_users.sql`
   - `project_web_subscription_types.sql`
   - `project_web_subscriptions.sql`
   - `project_web_station.sql`
   - `project_web_nearestlocation.sql`
   - `project_web_geolocation.sql`
   - `project_web_relations.sql`
   - `project_web_subscription_station.sql`
   - `project_web_measurement.sql`
   - `project_web_original_measurement.sql`
   - `project_web_endpoint_activity.sql`
3. Controleer daarna tabellen zoals `companies`, `relations`, `subscriptions`, `subscription_station`, `station`, `nearestlocation` en `measurement`.

## Huidige webapp

Deze webapp blijft bewust lokaal en lichtgewicht werken op JSON-opslag, zodat generator-ingest zonder extra installatie blijft functioneren.

Voor de schermen voor bedrijven, abonnementen en stationmetadata is de dump al vertaald naar JSON datasets. Daarmee kun je de user stories functioneel bekijken en testen in de webapp.

## Volgende stap richting Laravel/MySQL

Voor een volledige Laravel-opzet kun je de repositories in deze app later vervangen door PDO of Eloquent-repositories die direct uit MySQL lezen. De pagina's en endpoints zijn daar al logisch op voorbereid.
