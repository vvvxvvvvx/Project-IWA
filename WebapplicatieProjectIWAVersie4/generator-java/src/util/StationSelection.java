package util;

import java.io.BufferedReader;
import java.io.FileReader;
import java.io.IOException;
import java.util.ArrayList;

public class StationSelection {
    private boolean hasList = false;
    private ArrayList<String> selectedSations = new ArrayList<String>();

    public StationSelection(){
        this.readList();
    }

    private void readList() {
        try (BufferedReader infile = new BufferedReader(new FileReader("selected_stations.txt"))) {
            String line = null;
            while ((line = infile.readLine()) != null) {
                String value = line.trim();
                this.selectedSations.add(value);
            }
            infile.close();
        } catch (IOException e) {
            e.printStackTrace();
        }
        this.hasList = (!this.selectedSations.isEmpty()); 
    }

    public boolean hasSelectedStations() {
        return this.hasList;
    }

    public String getStation(int index) {
        String station = null;
        if (index<this.selectedSations.size()) {
            station = selectedSations.get(index);            
        }
        return station;
    }
}
