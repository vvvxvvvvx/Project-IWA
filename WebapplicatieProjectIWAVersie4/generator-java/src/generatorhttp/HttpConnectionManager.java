package generatorhttp;

import app.WeatherGeneratorApplication;
import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.ByteBuffer;
import java.nio.charset.StandardCharsets;
import generator.IClient;

public class HttpConnectionManager {
    private HttpSelector selector;
    private IClient client;
    private URL url;
    private HttpURLConnection httpConnection;
    private boolean isConnected;
    private boolean isValid;
    private boolean isWritable;
    private String lastErrorMessage = "";

    public void register(HttpSelector selector, IClient client) {
        this.selector = selector;
        this.client = client;
        this.isValid = true;
        this.isConnected = false;
        this.isWritable = false;
        this.selector.addConectionManager(this);
    }

    public boolean isValid() { return isValid; }
    public void setIsConnected(boolean isConnected) { this.isConnected = isConnected; }
    public void setIsWritable(boolean isWritable) { this.isWritable = isWritable; }
    public String getLastErrorMessage() { return lastErrorMessage; }
    public URL getUrl() { return url; }

    public void cancel() {
        this.isValid = false;
        this.isConnected = false;
        this.selector.remove(this);
        if (this.httpConnection != null) {
            this.httpConnection.disconnect();
        }
    }

    public IClient attachment() { return client; }

    public void attach(Object object) {
        this.client = object != null ? (IClient) object : null;
    }

    public boolean isConnectable() {
        return this.client != null && !this.isConnected;
    }

    public boolean isWritable() { return this.isWritable; }
    public void setConnectionURL(URL url) { this.url = url; }
    public HttpURLConnection getConnection() { return this.httpConnection; }

    public boolean openConnection() throws IOException {
        this.httpConnection = (HttpURLConnection) this.url.openConnection();
        this.httpConnection.setRequestMethod("POST");
        this.httpConnection.setRequestProperty("Content-Type", "application/json; charset=utf-8");
        this.httpConnection.setRequestProperty("Accept", "application/json");
        this.httpConnection.setConnectTimeout(WeatherGeneratorApplication.getInstance().getSettings().getConnectTimeoutMs());
        this.httpConnection.setReadTimeout(WeatherGeneratorApplication.getInstance().getSettings().getReadTimeoutMs());
        this.httpConnection.setDoOutput(true);
        this.httpConnection.setDoInput(true);
        this.httpConnection.setUseCaches(false);
        return true;
    }

    public int sendPost(ByteBuffer writeBuffer) {
        this.isWritable = false;
        int written = 0;
        try (OutputStream os = this.httpConnection.getOutputStream()) {
            byte[] input = extractBytes(writeBuffer);
            written = input.length;
            os.write(input, 0, written);
            os.flush();

            int statusCode = this.httpConnection.getResponseCode();
            if (statusCode < 200 || statusCode >= 300) {
                this.lastErrorMessage = "HTTP " + statusCode + " from " + this.url + ": " + readResponseBody(true);
                this.isConnected = false;
                return -1;
            }

            if (WeatherGeneratorApplication.getInstance().getSettings().httpLoggingEnabled()) {
                System.out.println("POST " + this.url + " -> HTTP " + statusCode + " " + readResponseBody(false));
            }

            this.lastErrorMessage = "";
            this.isConnected = false;
            return written;
        } catch (IOException e) {
            this.lastErrorMessage = "Unable to reach " + this.url + ": " + e.getMessage();
            this.isConnected = false;
            return -1;
        } catch (Exception e) {
            this.lastErrorMessage = "Unexpected HTTP error for " + this.url + ": " + e.getMessage();
            this.isConnected = false;
            return -1;
        }
    }

    private byte[] extractBytes(ByteBuffer writeBuffer) {
        StringBuilder message = new StringBuilder(writeBuffer.remaining());
        while (writeBuffer.hasRemaining()) {
            message.append((char) writeBuffer.get());
        }
        return message.toString().getBytes(StandardCharsets.UTF_8);
    }

    private String readResponseBody(boolean errorStream) {
        try (BufferedReader br = new BufferedReader(new InputStreamReader(
            errorStream && this.httpConnection.getErrorStream() != null
                ? this.httpConnection.getErrorStream()
                : this.httpConnection.getInputStream(),
            StandardCharsets.UTF_8
        ))) {
            StringBuilder response = new StringBuilder();
            String responseLine;
            while ((responseLine = br.readLine()) != null) {
                response.append(responseLine.trim());
            }
            return response.toString();
        } catch (IOException ignored) {
            return "";
        }
    }
}
