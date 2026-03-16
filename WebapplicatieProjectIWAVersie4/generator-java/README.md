# Generator Java

Dit is de refactorde versie van de oorspronkelijke generatorcode.

## Doel

- bestaande functionaliteit behouden
- duidelijkere klassennamen
- standaard klaarzetten voor koppeling met het dashboard op `localhost:8080/postWeatherData`

## Belangrijkste componenten

- **WeatherGeneratorApplication**  
  bootstrap en opstartlogica
- **GeneratorSettings**  
  config in `settings.conf`
- **HttpWeatherGenerator**  
  plant cluster-updates en verstuurt berichten
- **HttpClusterClient / HttpClusterClientManager / HttpClusterClientSelector**  
  beheer van HTTP-clients per cluster
- **WeatherApiPostClient**  
  losse POST-client voor JSON berichten
- **WeatherStation / WeatherStationModel / WeatherStationModelData**  
  domein rond stations en metingen
- **GeneratorDashboardFrame**  
  bestaande Swing interface
- **ClusterLoadAdjuster**  
  regeling voor aantal actieve clusters

## Standaard endpoint

`http://localhost:8080/postWeatherData`
