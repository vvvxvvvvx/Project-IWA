package generatorhttp;
import app.WeatherGeneratorApplication;
import businessobject.WeatherStationModel;
import generator.IGenerator;
import generator.WeatherStationCluster;

import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.LinkedList;
import java.util.Random;
import java.util.TimeZone;
import timing.AccurateTimer;
import util.KeyValuePair;
import util.RandomIterator;

public class HttpWeatherGenerator
implements IGenerator, ActionListener
{
	public static final int TOTAL_CLUSTERS = 800;
	public static final int CLUSTER_SIZE = 10;
	private static final long TIMER_INITIAL_WAIT = 2000L;
	private static final long TIMER_INTERVAL = 1000L;
	private AccurateTimer timer;
	private int timer_interval;
	private HttpClusterClientSelector clientSelector;
	private HttpClusterClientManager clientManager;
	private ArrayList<WeatherStationCluster> clusterList;
	private ArrayList<HttpClusterClient> clientList;
	private ArrayList<KeyValuePair<WeatherStationCluster, HttpClusterClient>> clusterClientList;
	private int requestedClusters;
	private Random random;
	private long peakTempCount;
	private long missingValueCount;
	private long writtenClusters;

	public HttpWeatherGenerator(ArrayList<WeatherStationModel> dataModels) {
		this.clientSelector = new HttpClusterClientSelector();
		initializeClusterList(dataModels);
		initializeClientManager();
		this.clusterClientList = new ArrayList<KeyValuePair<WeatherStationCluster, HttpClusterClient>>(this.clusterList.size());
		for (int i = 0; i < this.clusterList.size(); i++) {
			WeatherStationCluster cluster = this.clusterList.get(i);
			HttpClusterClient client = this.clientList.get(i);
			this.clusterClientList.add(new KeyValuePair(cluster, client));
		} 
		calculateNext();
		this.random = new Random();
		this.peakTempCount = 0L;
		this.missingValueCount = 0L;
		this.requestedClusters = 800;
		this.writtenClusters = 0L;
		this.timer = new AccurateTimer(System.currentTimeMillis() + TIMER_INITIAL_WAIT, this);
	}
	
	private void initializeClientManager() {
		this.clientList = new ArrayList<HttpClusterClient>(this.clusterList.size());
		for (WeatherStationCluster cluster : this.clusterList) {
			HttpClusterClient client = new HttpClusterClient(cluster, WeatherGeneratorApplication.getInstance().getSettings().getHTTPClient(), this.clientSelector);
			this.clientList.add(client);
		} 
		this.clientManager = new HttpClusterClientManager(this.clientList);
	}

	private void initializeClusterList(ArrayList<WeatherStationModel> dataModels) {
		this.clusterList = createClusterList(dataModels);
		this.timer_interval = WeatherGeneratorApplication.getInstance().getSettings().getStationUpdateInterval();
		Calendar now = Calendar.getInstance(TimeZone.getTimeZone("UTC"));
		now.add(14, (int) this.TIMER_INITIAL_WAIT);
		Random r = new Random();
		for (WeatherStationCluster cluster : this.clusterList) {
			Calendar stationstart = (Calendar) now.clone();
			int step = r.nextInt(this.timer_interval)*1000;
			stationstart.add(14, step);
			for (WeatherStationModel dataModel : cluster.getStationModels()) {
				dataModel.setStart(stationstart, this.timer_interval);
			}			
		}
	}

	private ArrayList<WeatherStationCluster> createClusterList(ArrayList<WeatherStationModel> stationModels) {
		ArrayList<WeatherStationCluster> clusterList = new ArrayList<WeatherStationCluster>();
		for (int i = 0; i < 800; i++) {
			ArrayList<WeatherStationModel> clusterModelList = new ArrayList<WeatherStationModel>(stationModels.subList(i * 10, (i + 1) * 10));
			String clusterName = String.format("Cluster-%03d", new Object[] { Integer.valueOf(i + 1) });
			clusterList.add(new WeatherStationCluster(clusterName, clusterModelList));
		} 
		return clusterList;
	}

	public void setActiveClusters(int amount) {
		this.requestedClusters = amount;
		this.clientManager.setActiveClients(amount);
	}

	public void start() {
		setActiveClusters(this.requestedClusters);
	}

	public void stop() {
		int tmpRequestedClusters = this.requestedClusters;
		setActiveClusters(0);
		this.requestedClusters = tmpRequestedClusters;
	}

	private void calculateNext() {
		Calendar now = Calendar.getInstance(TimeZone.getTimeZone("UTC"));
		for (WeatherStationCluster cluster : this.clusterList) {
			for (WeatherStationModel dataModel : cluster.getStationModels()) {
				dataModel.calculateNext(now);
			}
		}
	}

	private int writeData() {
		int peakTempProbability = WeatherGeneratorApplication.getInstance().getSettings().getPeakTempProbability();
		int minimalPeakTempCount = WeatherGeneratorApplication.getInstance().getSettings().getMinimalPeakTempCount();
		double peakProbability = peakTempProbability / 100.0D * 10.0D;
		if (peakProbability > 1.0D) peakProbability = 1.0D;
		int peakTemps = 0;
		int writingCount = 0;
		RandomIterator<KeyValuePair<WeatherStationCluster, HttpClusterClient>> it = new RandomIterator(this.clusterClientList);
		while (it.hasNext()) {
			KeyValuePair<WeatherStationCluster, HttpClusterClient> clusterClient = (KeyValuePair<WeatherStationCluster, HttpClusterClient>)it.next();
			WeatherStationCluster cluster = (WeatherStationCluster)clusterClient.getKey();
			if (cluster.checkNextWrite()) {
				HttpClusterClient client = (HttpClusterClient)clusterClient.getValue();
				boolean doPeakTemp = (this.random.nextDouble() < peakProbability);
				int i = (doPeakTemp | (peakTemps < minimalPeakTempCount)) ? 1 : 0;
				if (client.isWritable()) {
					int peakTempAmount = (i != 0) ? 1 : 0;
					int missingValueAmount = cluster.prepareWriteBuffer(peakTempAmount);
					boolean writing = client.write();
					if (writing) {
						writingCount++;
						peakTemps += peakTempAmount;
						this.peakTempCount += peakTempAmount;
						this.missingValueCount += missingValueAmount;
					} 
				}
				cluster.clearNextWrite();
			}
		} 
		return writingCount;
	}

	public void actionPerformed(ActionEvent e) {
		boolean logging = WeatherGeneratorApplication.getInstance().getSettings().loggingEnabled();
		long totalStartTime = System.currentTimeMillis();
		if (e.getSource() != this.timer) {
			return;
		}
		long startTime = System.currentTimeMillis();
		int writingCount = writeData();
		long writeTime = System.currentTimeMillis() - startTime;
		startTime = System.currentTimeMillis();
		calculateNext();
		long calcTime = System.currentTimeMillis() - startTime ;
		startTime = System.currentTimeMillis();
		waitForWriting(writingCount);
		long waitTime = System.currentTimeMillis() - startTime;
		long lastTime = this.timer.getTime();
		if (lastTime + TIMER_INTERVAL > System.currentTimeMillis()) {
			this.timer = new AccurateTimer(lastTime + TIMER_INTERVAL, this);
		} else {
			this.timer = new AccurateTimer(System.currentTimeMillis(), this);
		} 
		long totalEndTime = System.currentTimeMillis();
		if (logging) {
			System.out.println(System.currentTimeMillis());
			System.out.println(String.format("Writing clients: %d (%s)", new Object[] { Integer.valueOf(writingCount), Long.valueOf(writeTime) }));
			System.out.println("Calculation: " + (calcTime));
			System.out.println("waitForWriting: " + (waitTime));
			System.out.println("Total: " + (totalEndTime - totalStartTime));
		}
	}
	
	private void waitForWriting(int writingClientCount) {
		LinkedList<HttpClusterClient> writingClients = new LinkedList<HttpClusterClient>();
		for (HttpClusterClient client : this.clientList) {
			if (client.isWriting()) {
				writingClients.add(client);
			}
		} 
		this.writtenClusters += (writingClientCount - writingClients.size());
		while (!writingClients.isEmpty()) {
			try {
				Thread.sleep(10L);
			} catch (InterruptedException e) {
				e.printStackTrace();
			} 
			LinkedList<HttpClusterClient> writtenClients = new LinkedList<HttpClusterClient>();
			for (HttpClusterClient client : writingClients) {
				if (!client.isWriting()) {
					writtenClients.add(client);
					this.writtenClusters++;
				} 
			} 
			writingClients.removeAll(writtenClients);
			writtenClients.clear();
		} 
	}

	public ArrayList<WeatherStationCluster> getStationClusters() {
		return this.clusterList;
	}

	public int getActiveClusterCount() {
		return this.clientManager.getActiveClusterCount();
	}

	public int getDisabledClusterCount() {
		return this.clientManager.getDisabledClusterCount();
	}

	public int getErrorClusterCount() {
		return this.clientManager.getErrorClusterCount();
	}

	public long getMissingValueCount() {
		return this.missingValueCount;
	}

	public long getPeakTempCount() {
		return this.peakTempCount;
	}

	public long getWrittenClusters() {
		return this.writtenClusters;
	}
}