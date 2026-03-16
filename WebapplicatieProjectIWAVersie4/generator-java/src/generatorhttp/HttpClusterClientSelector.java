package generatorhttp;
import java.io.IOException;
import java.net.URL;
import java.nio.ByteBuffer;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.ConcurrentLinkedQueue;

import generator.IClient;
import generator.IClientListener;
import util.KeyValuePair;
import util.Log;

public class HttpClusterClientSelector
implements Runnable
{
	private HttpSelector selector = HttpSelector.open();
	private ConcurrentHashMap<IClient, HttpConnectionManager> clientChannelMap = new ConcurrentHashMap<IClient, HttpConnectionManager>();
	private ConcurrentLinkedQueue<KeyValuePair<HttpConnectionManager, IClient>> pendingConnections = new ConcurrentLinkedQueue<KeyValuePair<HttpConnectionManager, IClient>>();
	private ArrayList<IClientListener> clientListenerList = new ArrayList<IClientListener>();
	public HttpClusterClientSelector() {
		Thread thread = new Thread(this, "ClientSelector-Thread");
		thread.setDaemon(true);
		thread.start();
	}

	public void run() {
		while (true) {
			KeyValuePair<HttpConnectionManager, IClient> pendingConnection;
			while ((pendingConnection = this.pendingConnections.poll()) != null) {
				HttpConnectionManager channel = (HttpConnectionManager)pendingConnection.getKey();
				IClient client = (IClient)pendingConnection.getValue();
				this.clientChannelMap.put(client, channel);
				channel.register(this.selector, client);
			} 
			ArrayList<HttpConnectionManager> keys = this.selector.getConnectionManagers();
			Iterator<HttpConnectionManager> i = keys.iterator();
			while (i.hasNext()) {
				HttpConnectionManager key = i.next();					
				if (key == null) {
					continue;
				}
				if (!key.isValid()) {
					disconnect(key);
					continue;
				} 
				boolean connecting = false;
				try {
					if (key.isConnectable()) {
						connecting = true;
						handleConnect(key);
						continue;
					} 
					if (key.isWritable()) {
						handleWrite(key);
					}
				}
				catch (IOException e) {
					String cause = null;
					if (connecting) { cause = "connecting"; }
					else { cause = "writing"; }
					Log.ERROR.printf("Error %s to client: %s", new Object[] { cause, e });
					handleError(key);
				} catch (Exception e) {
					String cause = null;
					if (connecting) { cause = "connecting"; }
					else { cause = "writing"; }
					Log.ERROR.printf("Unexpected exception while %s to client: %s", new Object[] { cause, e });
					handleError(key);
				} 
			} 
		} 
	}

	public synchronized void connectClient(IClient client, URL url) throws IOException {
		HttpConnectionManager channel = new HttpConnectionManager();
		channel.setConnectionURL(url);
		this.clientChannelMap.put(client, channel);
		this.pendingConnections.add(new KeyValuePair(channel, client));
	}

	public synchronized void disconnectClient(IClient client) {
		HttpConnectionManager key = this.clientChannelMap.get(client);
		if (key != null) {
			disconnect(key);
		} 
	}

	public synchronized void setWritable(IClient client) {
		HttpConnectionManager channel = this.clientChannelMap.get(client);
		channel.setIsWritable(true);
	}

	public synchronized HttpConnectionManager getConnectionManager(IClient client) {
		return this.clientChannelMap.get(client);
	}

	public void addClientListener(IClientListener clientListener) {
		this.clientListenerList.add(clientListener);
	}

	private void handleConnect(HttpConnectionManager key) throws IOException {
		boolean connected = key.openConnection();
		if (connected) {
			notifyConnected(key);
		} 
	}

	private void handleWrite(HttpConnectionManager key) {
		key.setIsWritable(false);
		IClient client = (IClient)key.attachment();
		ByteBuffer writeBuffer = client.getWriteBuffer();
		int writeSize = key.sendPost(writeBuffer);
		if (writeSize < 0) {
			handleError(key);
		}
		if (!writeBuffer.hasRemaining()) {
			notifyWriteComplete(key);
		} 
	}

	private void handleError(HttpConnectionManager key) {
		notifyError(key);
		disconnect(key);
	}

	private void disconnect(HttpConnectionManager key) {
		key.cancel();
		notifyDisconnected(key);
		key.attach(null);
	}

	private void notifyConnected(HttpConnectionManager key) {
		key.setIsConnected(true);
		IClient client = (IClient)key.attachment();
		for (IClientListener clientListener : this.clientListenerList) {
			clientListener.onConnected(client);
		}
	}

	private void notifyWriteComplete(HttpConnectionManager key) {
		IClient client = (IClient)key.attachment();
		for (IClientListener clientListener : this.clientListenerList) {
			clientListener.onWriteComplete(client);
		}
	}

	private void notifyError(HttpConnectionManager key) {
		IClient client = (IClient)key.attachment();
		for (IClientListener clientListener : this.clientListenerList) {
			clientListener.onError(client);
		}
	}

	private void notifyDisconnected(HttpConnectionManager key) {
		key.setIsConnected(false);
		IClient client = (IClient)key.attachment();
		for (IClientListener clientListener : this.clientListenerList)
			clientListener.onDisconnected(client); 
	}
}