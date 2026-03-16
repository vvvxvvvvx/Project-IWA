package generatorhttp;
import java.io.IOException;
import java.net.URL;
import java.nio.ByteBuffer;

import generator.IClient;
import generator.IClientListener;
import util.Log;

public class HttpClusterClient implements IClient, IClientListener {
    private IClient client;
    private URL url;
    private HttpClusterClientSelector selector;
    private boolean active;
    private boolean connected;
    private boolean writable;
    private boolean error;
    private String lastErrorMessage = "";

    public HttpClusterClient(IClient client, URL url, HttpClusterClientSelector selector) {
        this.client = client;
        this.url = url;
        this.selector = selector;
        this.active = false;
        this.connected = false;
        this.writable = false;
        this.error = false;
        selector.addClientListener(this);
    }

    public boolean isActive() { return this.active; }
    public boolean isConnected() { return this.connected; }
    public boolean isWritable() { return this.writable; }
    public boolean isWriting() { return this.active && this.connected && !this.writable; }
    public boolean hasError() { return this.error; }
    public String getLastErrorMessage() { return this.lastErrorMessage; }

    public void connect() {
        if (!this.active) {
            this.active = true;
            this.error = false;
            this.lastErrorMessage = "";
            try {
                this.selector.connectClient(this, this.url);
            } catch (IOException e) {
                this.active = false;
                this.error = true;
                this.lastErrorMessage = "Could not register HTTP client for " + this.url + ": " + e.getMessage();
                Log.ERROR.printf("Error registering client for connecting: %s", new Object[] { e });
            }
        }
    }

    public boolean write() {
        boolean writing = false;
        if (isWritable()) {
            this.writable = false;
            this.selector.setWritable(this);
            writing = true;
        }
        return writing;
    }

    public void disconnect() {
        if (this.active) {
            this.selector.disconnectClient(this);
        }
    }

    public int getId() { return this.client.getId(); }
    public ByteBuffer getWriteBuffer() { return this.client.getWriteBuffer(); }

    public void onConnected(IClient client) {
        if (client != this) return;
        this.connected = true;
        this.writable = true;
        this.error = false;
        this.lastErrorMessage = "";
    }

    public void onDisconnected(IClient client) {
        if (client != this) return;
        this.active = false;
        this.connected = false;
        this.writable = false;
    }

    public void onError(IClient client) {
        if (client != this) return;
        HttpConnectionManager connectionManager = this.selector.getConnectionManager(this);
        this.error = true;
        if (connectionManager != null && connectionManager.getLastErrorMessage() != null && !connectionManager.getLastErrorMessage().isEmpty()) {
            this.lastErrorMessage = connectionManager.getLastErrorMessage();
        } else {
            this.lastErrorMessage = "Error while posting cluster data to " + this.url;
        }
        System.out.println(this.lastErrorMessage);
    }

    public void onWriteComplete(IClient client) {
        if (client != this) return;
        this.writable = true;
    }

    public boolean equals(Object obj) { return this.client.equals(obj); }
    public int hashCode() { return this.client.hashCode(); }
}
