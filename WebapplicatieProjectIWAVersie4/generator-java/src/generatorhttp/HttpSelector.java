package generatorhttp;

import java.util.ArrayList;

public class HttpSelector {

	private ArrayList<HttpConnectionManager> connectionManagers;

	public HttpSelector() {
		this.connectionManagers = new ArrayList<HttpConnectionManager>();
	}

	public synchronized static HttpSelector open() {
		return new HttpSelector();
	}
	
	public synchronized void addConectionManager(HttpConnectionManager connectionManager) {
		this.connectionManagers.add(connectionManager);
	}
	
	public synchronized ArrayList<HttpConnectionManager> getConnectionManagers(){
		return (ArrayList<HttpConnectionManager>) this.connectionManagers.clone();
	}

	public synchronized void remove(HttpConnectionManager httpConnectionManager) {
		if((httpConnectionManager!=null)&&this.connectionManagers.contains(httpConnectionManager)) {
			this.connectionManagers.remove(httpConnectionManager);			
		}
	}
}
