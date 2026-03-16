package generator;
import businessobject.WeatherStationModel;
import java.nio.ByteBuffer;
import java.util.ArrayList;
import java.util.Random;

public class WeatherStationCluster
implements IClient
{
	private static ArrayList<Integer> idList = new ArrayList<Integer>();
	private static Random idRandom = new Random();
	private ArrayList<WeatherStationModel> stationModelList;
	private String name;
	private int id;
	private ByteBuffer buffer;

	public WeatherStationCluster(String name, ArrayList<WeatherStationModel> stationModelList) {
		this.stationModelList = stationModelList;
		this.name = name;
		this.id = getUid();
		this.buffer = ByteBuffer.allocateDirect(4096);
	}

	public int getId() {
		return this.id;
	}

	public ByteBuffer getWriteBuffer() {
		return this.buffer;
	}

	public ArrayList<WeatherStationModel> getStationModels() {
		return this.stationModelList;
	}

	public String getName() {
		return this.name;
	}

	public synchronized int prepareWriteBuffer(int tempPeaks) {
		this.buffer.clear();
		int missingDataAmount = WeatherStationClusterJsonWriter.writeCluster(this, tempPeaks);
		this.buffer.flip();
		return missingDataAmount;
	}

	public synchronized boolean checkNextWrite() {
		boolean nextWrite = true;
		for (WeatherStationModel station : this.stationModelList) {
			nextWrite = nextWrite & station.getCalculatedNext();
		}
		return nextWrite;		
	}
	
	public synchronized void clearNextWrite() {
		for (WeatherStationModel station : this.stationModelList) {
			station.setCalculatedNext(false);
		}
	}
	
	private static int getUid() {
		int uid;
		synchronized (idList) { do {  }
		while (idList.contains(Integer.valueOf(uid = idRandom.nextInt(2147483647))));
		idList.add(Integer.valueOf(uid)); }
		return uid;
	}

	public boolean equals(Object obj) {
		if (obj == null) {
			return false;
		}
		if (!(obj instanceof IClient)) {
			return false;
		}
		IClient other = (IClient)obj;
		return (other.getId() == getId());
	}

	public int hashCode() {
		return getId();
	}
}