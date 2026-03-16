package generatorhttp;
import java.util.ArrayList;
import java.util.Iterator;

import app.WeatherGeneratorApplication;
import util.RandomIterator;
import util.StationSelectionIterator;

public class HttpClusterClientManager
{
	private ArrayList<HttpClusterClient> clientList;

	public HttpClusterClientManager(ArrayList<HttpClusterClient> clientList) {
		this.clientList = clientList;
	}

	public void setActiveClients(int amount) {
		int activeClients = getActiveClusterCount();
		amount -= activeClients;
		if (amount < 0) {
			disconnectClients(Math.abs(amount));
		} else if (amount > 0) {
			connectClients(amount);
		}
	}

	public int getActiveClusterCount() {
		int count = 0;
		for (HttpClusterClient client : this.clientList) {
			if (client.isActive()) {
				count++;
			}
		} 
		return count;
	}

	public int getDisabledClusterCount() {
		int count = 0;
		for (HttpClusterClient client : this.clientList) {
			if (!client.isActive()) {
				count++;
			}
		} 
		return count;
	}

	public int getErrorClusterCount() {
		int count = 0;
		for (HttpClusterClient client : this.clientList) {
			if (client.hasError()) {
				count++;
			}
		} 
		return count;
	}

	private void connectClients(int amount) {
		Iterator<HttpClusterClient> clusterIterator;
		boolean useSelected = WeatherGeneratorApplication.getInstance().getSettings().useSelectedEnabled();
		if (useSelected) {
			clusterIterator = new StationSelectionIterator<HttpClusterClient>(this.clientList, true);
		}
		else {
			clusterIterator = new RandomIterator<HttpClusterClient>(this.clientList);
		} 
		while (clusterIterator.hasNext() && amount > 0) {
			HttpClusterClient client = clusterIterator.next();
			if (!client.isActive()) {
				client.connect();
				amount--;
			} 
		} 				
	}

	private void disconnectClients(int amount) {
		Iterator<HttpClusterClient> clusterIterator;
		boolean useSelected = WeatherGeneratorApplication.getInstance().getSettings().useSelectedEnabled();
		if (useSelected) {
			clusterIterator = new StationSelectionIterator<HttpClusterClient>(this.clientList, false);
		}
		else {
			clusterIterator = new RandomIterator<HttpClusterClient>(this.clientList);
		} 
		while (clusterIterator.hasNext() && amount > 0) {
			HttpClusterClient client = clusterIterator.next();
			if (client.isActive()) {
				client.disconnect();
				amount--;
			} 
		} 				
	}
}