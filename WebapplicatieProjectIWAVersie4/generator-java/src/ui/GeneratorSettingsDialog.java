package ui;

import app.WeatherGeneratorApplication;
import app.GeneratorSettings;
import java.awt.Container;
import java.awt.Dimension;
import java.awt.Frame;
import java.awt.GridLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.WindowAdapter;
import java.awt.event.WindowEvent;
import javax.swing.BorderFactory;
import javax.swing.JButton;
import javax.swing.JCheckBox;
import javax.swing.JDialog;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JTextField;
import javax.swing.SpringLayout;
import util.UIUtil;

public class GeneratorSettingsDialog
extends JDialog
implements ActionListener
{
	private static final long serialVersionUID = 1L;
	private JTextField httpHostField;
	private JTextField httpPathField;
	private JTextField httpPortField;	
	private JTextField missingDataProbabilityField;
	private JTextField peakTempProbabilityField;
	private JTextField minimalPeakTempCountField;
	private JTextField statsUpdateIntervalField;
	private JTextField stationUpdateIntervalField;
	private JCheckBox loggingCheckBox;
	private JCheckBox httpLoggingCheckBox;
	private JCheckBox createFileCheckBox;
	private JCheckBox useSelectedCheckBox;
	private JButton saveButton;
	private JButton cancelButton;

	public GeneratorSettingsDialog(Frame owner) {
		super(owner, "GeneratorSettings", false);
		setVisible(false);
		JPanel httpPanel = createHttpSettingsPanel();
		JPanel generatorPanel = createGeneratorSettingsPanel();
		this.saveButton = new JButton("Save");
		this.cancelButton = new JButton("Cancel");
		this.saveButton.addActionListener(this);
		this.cancelButton.addActionListener(this);
		Container contentPane = getContentPane();
		SpringLayout layout = new SpringLayout();
		contentPane.setLayout(layout);
		contentPane.add(httpPanel);
		layout.putConstraint("East", httpPanel, -5, "East", contentPane);
		layout.putConstraint("West", httpPanel, 5, "West", contentPane);
		contentPane.add(generatorPanel);
		layout.putConstraint("North", generatorPanel, 5, "South", httpPanel);
		layout.putConstraint("East", generatorPanel, -5, "East", contentPane);
		layout.putConstraint("West", generatorPanel, 5, "West", contentPane);
		contentPane.add(this.saveButton);
		layout.putConstraint("West", this.saveButton, 5, "West", generatorPanel);
		contentPane.add(this.cancelButton);
		layout.putConstraint("East", this.cancelButton, -5, "East", generatorPanel);
		layout.putConstraint("South", this.saveButton, -5, "South", contentPane);
		layout.putConstraint("South", this.cancelButton, -5, "South", contentPane);
		setVisible(false);
		setSize(new Dimension(330, 493));
		setResizable(true);
		UIUtil.center(this);
		setDefaultCloseOperation(0);
		addWindowListener(new WindowAdapter() {
			public void windowClosing(WindowEvent we) {
				GeneratorSettingsDialog.this.onCancel();
			}
		});
	}

	private JPanel createGeneratorSettingsPanel() {
		JPanel contentPane = new JPanel(new GridLayout(1, 1));
		contentPane.setBorder(BorderFactory.createTitledBorder("Generator"));
		JPanel panel = new JPanel(new GridLayout(9, 2));
		panel.setBorder(BorderFactory.createEmptyBorder(5, 6, 5, 5));
		contentPane.add(panel);
		this.missingDataProbabilityField = new JTextField(3);
		this.peakTempProbabilityField = new JTextField(3);
		this.minimalPeakTempCountField = new JTextField(3);
		this.statsUpdateIntervalField = new JTextField(4);
		this.stationUpdateIntervalField = new JTextField(4);
		this.loggingCheckBox = new JCheckBox();
		this.httpLoggingCheckBox = new JCheckBox();
		this.createFileCheckBox = new JCheckBox();
		this.useSelectedCheckBox = new JCheckBox();
		JLabel missingDataProbabilityLabel = new JLabel("Missing data probability (%):");
		JLabel peakTempProbabilityLabel = new JLabel("Peak temp. probability (%):");
		JLabel minimalPeakTempCountLabel = new JLabel("Min. temp. peaks per sec.:");
		JLabel statUpdateIntervalLabel = new JLabel("Stats update interval (ms):");
		JLabel stationUpdateIntervalLabel = new JLabel("WeatherStation update interval (sec):");
		JLabel loggingLabel = new JLabel("Enable logging:");
		JLabel httpLoggingLabel = new JLabel("Http Errors:");
		JLabel createFileLabel = new JLabel("Write stations:");
		JLabel useSelectedLabel = new JLabel("Use selected:");
		panel.add(missingDataProbabilityLabel);
		panel.add(this.missingDataProbabilityField);
		panel.add(peakTempProbabilityLabel);
		panel.add(this.peakTempProbabilityField);
		panel.add(minimalPeakTempCountLabel);
		panel.add(this.minimalPeakTempCountField);
		panel.add(statUpdateIntervalLabel);
		panel.add(this.statsUpdateIntervalField);
		panel.add(stationUpdateIntervalLabel);
		panel.add(this.stationUpdateIntervalField);
		panel.add(loggingLabel);
		panel.add(this.loggingCheckBox);
		panel.add(httpLoggingLabel);
		panel.add(this.httpLoggingCheckBox);
		panel.add(createFileLabel);
		panel.add(this.createFileCheckBox);
		panel.add(useSelectedLabel);
		panel.add(this.useSelectedCheckBox);
		return contentPane;
	}

	private JPanel createHttpSettingsPanel() {
		this.httpHostField = new JTextField(15);
		this.httpPathField = new JTextField(25);
		this.httpPortField = new JTextField(6);
		JLabel httpHostLabel = new JLabel("HTTP Hostname:");
		JLabel httpPathLabel = new JLabel("Path:");
		JLabel httpPortLabel = new JLabel("Port:");
		SpringLayout layout = new SpringLayout();
		JPanel panel = new JPanel(layout);
		panel.setBorder(BorderFactory.createTitledBorder("HTTP"));
		panel.add(httpHostLabel);
		panel.add(httpPortLabel);
		panel.add(this.httpHostField);
		panel.add(this.httpPortField);
		panel.add(httpPathLabel);
		panel.add(this.httpPathField);
		layout.putConstraint("West", httpHostLabel, 1, "West", this.httpHostField);
		layout.putConstraint("West", httpPortLabel, 1, "West", this.httpPortField);
		layout.putConstraint("West", httpPathLabel, 1, "West", this.httpPathField);
		layout.putConstraint("South", httpHostLabel, -1, "North", this.httpHostField);
		layout.putConstraint("South", httpPortLabel, -1, "North", this.httpPortField);
		layout.putConstraint("South", httpPathLabel, -1, "North", this.httpPathField);
		layout.putConstraint("East", this.httpHostField, -5, "West", this.httpPortField);
		layout.putConstraint("South", this.httpHostField, 0, "South", this.httpPortField);
		layout.putConstraint("West", this.httpHostField, 5, "West", panel);
		layout.putConstraint("West", this.httpPathField, 5, "West", panel);
		layout.putConstraint("North", httpHostLabel, 5, "North", panel);
		layout.putConstraint("East", this.httpPortField, -5, "East", panel);
		layout.putConstraint("South", this.httpHostField, -5, "North", httpPathLabel);
		layout.putConstraint("South", this.httpPortField, -5, "North", httpPathLabel);
		layout.putConstraint("South", this.httpPathField, -5, "South", panel);
		panel.setPreferredSize(new Dimension(0, 115));
		return panel;
	}

	public void showDialog() {
		if (!isVisible()) {
			load();
			setVisible(true);
		} 
	}

	public void hideDialog() {
		setVisible(false);
	}

	private void onSave() {
		save();
		hideDialog();
	}

	private void onCancel() {
		hideDialog();
	}

	private void load() {
		GeneratorSettings settings = WeatherGeneratorApplication.getInstance().getSettings();
		this.httpHostField.setText(settings.getHTTPHost());
		this.httpPathField.setText(settings.getHTTPPath());
		this.httpPortField.setText(Integer.toString(settings.getHTTPPort()));
		this.missingDataProbabilityField.setText(Integer.toString(settings.getMissingDataProbability()));
		this.peakTempProbabilityField.setText(Integer.toString(settings.getPeakTempProbability()));
		this.minimalPeakTempCountField.setText(Integer.toString(settings.getMinimalPeakTempCount()));
		this.statsUpdateIntervalField.setText(Integer.toString(settings.getStatsUpdateInterval()));
		this.stationUpdateIntervalField.setText(Integer.toString(settings.getStationUpdateInterval()));
		this.loggingCheckBox.setSelected(settings.loggingEnabled());
		this.httpLoggingCheckBox.setSelected(settings.httpLoggingEnabled());
		this.createFileCheckBox.setSelected(settings.createListEnabled());
		this.useSelectedCheckBox.setSelected(settings.useSelectedEnabled());
	}

	private void save() {
		GeneratorSettings settings = WeatherGeneratorApplication.getInstance().getSettings();
		String httpHostname = this.httpHostField.getText();
		String httpPath = this.httpPathField.getText();
		int httpPort = 0;
		int missingDataProbability = 0;
		int peakTempProbability = 0;
		int minimalPeakTempCount = 0;
		int statsUpdateInterval = 0;
		int stationUpdateInterval = 0;
		try {
			httpPort = Integer.parseInt(this.httpPortField.getText().trim());
			missingDataProbability = Integer.parseInt(this.missingDataProbabilityField.getText().trim());
			peakTempProbability = Integer.parseInt(this.peakTempProbabilityField.getText().trim());
			minimalPeakTempCount = Integer.parseInt(this.minimalPeakTempCountField.getText().trim());
			statsUpdateInterval = Integer.parseInt(this.statsUpdateIntervalField.getText().trim());
			stationUpdateInterval = Integer.parseInt(this.stationUpdateIntervalField.getText().trim());
		}
		catch (Exception e) {
			JOptionPane.showMessageDialog(this, "Please check your fields for invalid numbers.", "Error", 0);
		} 
		settings.setHTTPHost(httpHostname);
		settings.setHTTPPath(httpPath);
		settings.setHTTPPort(httpPort);
		settings.setMinimalPeakTempCount(minimalPeakTempCount);
		settings.setMissingDataProbability(missingDataProbability);
		settings.setPeakTempProbability(peakTempProbability);
		settings.setStatsUpdateInterval(statsUpdateInterval);
		settings.setStationUpdateInterval(stationUpdateInterval);
		settings.setLoggingEnabled(this.loggingCheckBox.isSelected());
		settings.setHttpLogging(this.httpLoggingCheckBox.isSelected());
		settings.setCreateList(this.createFileCheckBox.isSelected());
		settings.setUseSelected(this.useSelectedCheckBox.isSelected());
	}

	public void actionPerformed(ActionEvent e) {
		if (e.getSource() == this.saveButton) {
			onSave();
		}
		if (e.getSource() == this.cancelButton)
			onCancel(); 
	}
}
