# Project IWA – Weerstation Webapplicatie

Een Laravel/Livewire webapplicatie voor het monitoren en beheren van weerstations, meetdata, contracten en gebruikersrollen.

---

## Vereisten

| Vereiste      | Versie       |
|---------------|--------------|
| PHP           | >= 8.2       |
| Composer      | >= 2.x       |
| Node.js       | >= 18.x      |
| npm           | >= 9.x       |
| Database      | SQLite / MySQL / PostgreSQL |

---

## Installatie

### 1. Navigeer naar de webapp-map

```bash
cd wap-app
```

### 2. Installeer PHP-dependencies

```bash
composer install
```

### 3. Kopieer en configureer het `.env` bestand

```bash
cp .env.example .env
php artisan key:generate
```

Pas de databaseinstellingen aan in `.env`:

```env
DB_CONNECTION=sqlite
# of voor MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=iwa
# DB_USERNAME=root
# DB_PASSWORD=
```

### 4. Voer de migraties uit

```bash
php artisan migrate
```

### 5. Voer de seeders uit (optioneel)

Vul de database met standaard/testdata:

```bash
php artisan db:seed
```

Of combineer migratie en seeding in één stap:

```bash
php artisan migrate --seed
```

### 6. Installeer JavaScript-dependencies en bouw assets

```bash
npm install
npm run build
```

---

## Lokaal draaien

Start de ontwikkelomgeving (server + queue + Vite):

```bash
composer run dev
```

Of apart:

```bash
php artisan serve
npm run dev
```

De applicatie is dan bereikbaar op: [http://localhost:8000](http://localhost:8000)

---

## Data-generator configuratie

De externe datagenerator stuurt meetdata naar de applicatie via:

```
POST http://localhost:8000/postWeatherData
```

Instellingen voor de generator staan in `settings.conf` (in de projectroot):

| Instelling                | Beschrijving                            |
|---------------------------|-----------------------------------------|
| `client_http_hostname`    | Hostname van de webserver               |
| `client_http_port`        | Poort van de webserver                  |
| `client_http_path`        | Endpoint pad (`postWeatherData`)        |
| `stats_update_interval`   | Interval statistieken (ms)              |
| `station_update_interval` | Interval stationupdates (seconden)      |
| `use_selected_stations`   | Gebruik alleen geselecteerde stations   |

Geselecteerde stations kunnen worden opgegeven in `selected_stations.txt`.

---

## Testen

```bash
php artisan test
```

Of via Composer:

```bash
composer run test
```

---

## Projectstructuur

```
Project-IWA/
├── wap-app/          # Laravel applicatie
│   ├── app/          # Controllers, Models, Livewire componenten
│   ├── database/     # Migrations en seeders
│   ├── resources/    # Blade views, CSS, JS
│   ├── routes/       # Web- en API-routes
│   └── tests/        # Feature- en unittests
├── settings.conf     # Generatorconfiguratie
├── selected_stations.txt
└── full_stations_data.dat
```

---

## Functionaliteiten

- Inloggen, registreren en wachtwoord resetten
- Rolgebaseerde toegangscontrole (taken/permissies per rol)
- Dashboard met live meetdata van weerstations
- Stations beheren, vergelijken en storingen registreren
- Contract- en bedrijfsbeheer
- Abonnementenbeheer met tokensysteem
- Externe dataingest via POST-endpoint (geen authenticatie vereist)
