package app;
import businessobject.WeatherStation;
import businessobject.WeatherStationModel;
import generator.IGenerator;
import generatorhttp.HttpWeatherGenerator;

import java.io.File;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.TimeZone;

import ui.GeneratorDashboardFrame;
import util.DataLoader;
import util.SettingsFile;
import util.StationSelection;

public class WeatherGeneratorApplication
{
	private static WeatherGeneratorApplication instance = null;
	private File path;
	private GeneratorSettings settings;
	private StationSelection stationSelection;
	private IGenerator generator;
	private GeneratorDashboardFrame generatorGui;
	private DataLoader dataLoader;

	public static WeatherGeneratorApplication getInstance() {
		if (instance == null) {
			instance = new WeatherGeneratorApplication();
		}
		return instance;
	}

	private WeatherGeneratorApplication() {
		this.path = new File((new File("")).getAbsoluteFile(), "");
		this.settings = new GeneratorSettings(new SettingsFile(getLocalFile("settings.conf")));
		this.stationSelection = new StationSelection();
		instance = this;
		boolean logging = getInstance().getSettings().loggingEnabled();
		this.dataLoader = getInstance().initDataLoader(logging);	
		this.generator = getInstance().initGenerator(logging);
		this.generatorGui = new GeneratorDashboardFrame((IGenerator)this.generator);
	}

	private DataLoader initDataLoader(boolean logging) {
		if (logging) {
			System.out.println("Initializing data loader...");
		}
		DataLoader dataLoader = new DataLoader(getLocalFile("full_stations_data.dat"));
		dataLoader.start(Calendar.getInstance(TimeZone.getTimeZone("UTC")).get(6) - 1);		
		if (logging) {
			System.out.println("Done.");
			System.out.println();
		} 
		return dataLoader;
	}
	
	private ArrayList<WeatherStationModel> initDataModels(boolean logging) {
		if (logging) {
			System.out.println("Initializing data models...");
		}
		ArrayList<WeatherStation> stationList = this.dataLoader.getStations();
		ArrayList<WeatherStationModel> stationModels = new ArrayList<WeatherStationModel>();
		for (WeatherStation station : stationList) {
			WeatherStationModel stationModel = new WeatherStationModel(station, this.dataLoader);
			stationModels.add(stationModel);
		} 		
		if (logging) {
			System.out.println("Done.");
			System.out.println();
		} 
		return stationModels;
	}
	
	private IGenerator initGenerator(boolean logging) {
		ArrayList<WeatherStationModel> stationModels = getInstance().initDataModels(logging);
		if (logging) {
			System.out.println("Initializing generator...");
		} 
		IGenerator generator = new HttpWeatherGenerator(stationModels);
		if (logging) {
			System.out.println("Done.");
			System.out.println();
		}
		return generator;
	}

	public void exit() {
		this.settings.save();
		try {
			this.generator.stop();			
			while (this.generator.getActiveClusterCount() > 0) {
				Thread.sleep(100L);
				}
		} catch (InterruptedException e) {
			e.printStackTrace();
		} 
		this.generatorGui.exit();
		System.exit(0);
	}

	public GeneratorSettings getSettings() {
		return this.settings;
	}

	public StationSelection getStationSelection() {
		return this.stationSelection;
	}

	public GeneratorDashboardFrame getGeneratorGui() {
		return this.generatorGui;
	}

	public File getLocalFile(String filename) {
		return new File(this.path, filename);
	}

	public static void main(String[] args) {
		getInstance();
	}
}
