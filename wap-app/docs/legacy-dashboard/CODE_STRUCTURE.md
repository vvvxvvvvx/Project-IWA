
# Code-structuur

Deze webapp is opgesplitst per functioneel domein zodat een ontwikkelaar meteen ziet waar code thuishoort.

## Core
- `src/Core/Http` – request, response en router
- `src/Core/Middleware` – authenticatie- en rolchecks
- `src/Core/Support` – view rendering en JSON-opslag

## Accounts
- `src/Accounts/Controllers` – login/logout en account-gerelateerde pagina's
- `src/Accounts/Services` – authenticatie
- `src/Accounts/Repositories` – gebruikersdata

## Dashboard
- `src/Dashboard/Controllers` – dashboard- en insightpagina's
- `src/Dashboard/Api` – realtime dashboard API
- `src/Dashboard/Services` – metrics en samenvattingen

## Stations
- `src/Stations/Controllers` – stationspagina's en stationbeheer
- `src/Stations/Api` – stations-API
- `src/Stations/Repositories` – station- en stationmetadata

## Ingestion
- `src/Ingestion/Api` – endpoint voor generator ingest
- `src/Ingestion/Services` – correcties en parsing van meetdata
- `src/Ingestion/Repositories` – readings, batches, originele waarden en correctielogs

## Subscriptions
- `src/Subscriptions/Controllers` – abonnementen en abonnementtypes
- `src/Subscriptions/Api` – REST API voor abonnementhouders
- `src/Subscriptions/Repositories` – abonnementdata, types en endpoint-activiteit

## Companies
- `src/Companies/Controllers` – bedrijvenoverzichten en detailpagina's
- `src/Companies/Repositories` – bedrijven en contactpersonen

## Contracts
- `src/Contracts/Controllers` – contractoverzicht en details
- `src/Contracts/Repositories` – contractdata

## Views
Views zijn op dezelfde manier opgesplitst in mappen per domein onder `views/`.
