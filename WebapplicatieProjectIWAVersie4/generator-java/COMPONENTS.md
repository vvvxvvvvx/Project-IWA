# Componentverdeling

## 1. Application
- `app/WeatherGeneratorApplication.java`
- verantwoordelijkheid: bootstrap, instellingen laden, UI starten, generator initialiseren

## 2. Configuration
- `app/GeneratorSettings.java`
- verantwoordelijkheid: instellingen uitlezen en bewaren

## 3. HTTP / Internet
- `generatorhttp/HttpWeatherGenerator.java`
- `generatorhttp/HttpClusterClient.java`
- `generatorhttp/HttpClusterClientManager.java`
- `generatorhttp/HttpClusterClientSelector.java`
- `generatorhttp/HttpConnectionManager.java`
- `adapter/WeatherApiPostClient.java`
- verantwoordelijkheid: HTTP-connecties, POST-versturing, clusterverdeling

## 4. Weather domain
- `businessobject/WeatherStation.java`
- `businessobject/WeatherDataRow.java`
- `businessobject/WeatherStationModel.java`
- `businessobject/WeatherStationModelData.java`
- verantwoordelijkheid: weerdata, modelberekeningen, stationdata

## 5. Cluster scheduling
- `generator/WeatherStationCluster.java`
- `generator/WeatherStationClusterJsonWriter.java`
- verantwoordelijkheid: berichten bouwen en clusters bufferen

## 6. UI
- `ui/GeneratorDashboardFrame.java`
- `ui/GeneratorSettingsDialog.java`
- `ui/ClusterLoadAdjuster.java`
- verantwoordelijkheid: lokale generatorinterface

## 7. Utility
- `util/*`
- verantwoordelijkheid: laden van brondata, logging, iterators, tijdsfuncties
