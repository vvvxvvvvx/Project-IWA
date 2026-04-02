CREATE DATABASE IF NOT EXISTS iwa;
USE iwa;

CREATE TABLE country (
    country_code VARCHAR(2)  NOT NULL,
    country      VARCHAR(45) NOT NULL,
    PRIMARY KEY (country_code)
);

CREATE TABLE station (
    name      VARCHAR(10) NOT NULL,
    longitude FLOAT       NOT NULL,
    latitude  FLOAT       NOT NULL,
    elevation FLOAT       NOT NULL,
    PRIMARY KEY (name)
);

CREATE TABLE geolocation (
    id             INT          NOT NULL AUTO_INCREMENT,
    station_name   VARCHAR(10)  NOT NULL,
    country_code   VARCHAR(2)   NOT NULL,
    island         VARCHAR(100),
    county         VARCHAR(100),
    place          VARCHAR(100),
    hamlet         VARCHAR(100),
    town           VARCHAR(100),
    municipality   VARCHAR(100),
    state_district VARCHAR(100),
    administrative VARCHAR(100),
    state          VARCHAR(100),
    village        VARCHAR(100),
    region         VARCHAR(100),
    province       VARCHAR(100),
    city           VARCHAR(100),
    locality       VARCHAR(100),
    postcode       VARCHAR(100),
    country        VARCHAR(100),
    PRIMARY KEY (id),
    FOREIGN KEY (station_name) REFERENCES station(name),
    FOREIGN KEY (country_code) REFERENCES country(country_code)
);

CREATE TABLE nearestlocation (
    id                    INT          NOT NULL AUTO_INCREMENT,
    station_name          VARCHAR(10)  NOT NULL,
    name                  VARCHAR(100),
    administrative_region1 VARCHAR(100),
    administrative_region2 VARCHAR(100),
    country_code          VARCHAR(2)   NOT NULL,
    longitude             FLOAT        NOT NULL,
    latitude              FLOAT        NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (station_name) REFERENCES station(name),
    FOREIGN KEY (country_code) REFERENCES country(country_code)
);

CREATE TABLE measurement (
    id                    INT         NOT NULL AUTO_INCREMENT,
    station               VARCHAR(10) NOT NULL,
    date                  DATE        NOT NULL,
    time                  TIME        NOT NULL,
    temperature           FLOAT,
    dewpoint_temperature  FLOAT,
    air_pressure_station  FLOAT,
    air_pressure_sea_level FLOAT,
    visibility            FLOAT,
    wind_speed            FLOAT,
    percipation           FLOAT,
    snow_depth            FLOAT,
    conditions            VARCHAR(6),
    cloud_cover           FLOAT,
    wind_direction        INT,
    PRIMARY KEY (id),
    FOREIGN KEY (station) REFERENCES station(name)
);

CREATE TABLE original_measurement (
    id                   INT         NOT NULL AUTO_INCREMENT,
    corrected_measurement INT        NOT NULL,
    missing_field        VARCHAR(32),
    inavlid_temperature  FLOAT,
    PRIMARY KEY (id),
    FOREIGN KEY (corrected_measurement) REFERENCES measurement(id)
);

CREATE TABLE userroles (
    id          INT          NOT NULL AUTO_INCREMENT,
    role        VARCHAR(45)  NOT NULL,
    description VARCHAR(256),
    PRIMARY KEY (id)
);

CREATE TABLE users (
    id            INT          NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    first_name    VARCHAR(45),
    initials      VARCHAR(12),
    prefix        VARCHAR(10),
    email         VARCHAR(100) NOT NULL,
    employee_code VARCHAR(10),
    user_role     INT          NOT NULL,
    password      VARCHAR(256) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (user_role) REFERENCES userroles(id)
);

CREATE TABLE companies (
    id                INT          NOT NULL AUTO_INCREMENT,
    name              VARCHAR(100) NOT NULL,
    city              VARCHAR(100),
    street            VARCHAR(100),
    number            INT,
    number_additional VARCHAR(15),
    zip_code          VARCHAR(15),
    country           VARCHAR(2),
    email             VARCHAR(100),
    PRIMARY KEY (id),
    FOREIGN KEY (country) REFERENCES country(country_code)
);

CREATE TABLE relations (
    id         INT          NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    first_name VARCHAR(45),
    initials   VARCHAR(12),
    prefix     VARCHAR(10),
    company    INT          NOT NULL,
    `function`   VARCHAR(45),
    title      VARCHAR(45),
    email      VARCHAR(100),
    phone      VARCHAR(25),
    PRIMARY KEY (id),
    FOREIGN KEY (company) REFERENCES companies(id)
);

CREATE TABLE subscription_types (
    id                 INT          NOT NULL AUTO_INCREMENT,
    name               VARCHAR(45)  NOT NULL,
    description        VARCHAR(256),
    nr_stations        INT,
    frequency_in_hours INT,
    frequency_in_days  INT,
    continuous         TINYINT      DEFAULT 0,
    price_per_station  FLOAT,
    valid_through      DATE,
    PRIMARY KEY (id)
);

CREATE TABLE subscriptions (
    id         INT          NOT NULL AUTO_INCREMENT,
    company    INT          NOT NULL,
    type       INT          NOT NULL,
    start_date DATE         NOT NULL,
    end_date   DATE,
    price      FLOAT,
    notes      VARCHAR(256),
    identifier VARCHAR(45)  UNIQUE,
    token      VARCHAR(100),
    PRIMARY KEY (id),
    FOREIGN KEY (company) REFERENCES companies(id),
    FOREIGN KEY (type)    REFERENCES subscription_types(id)
);

CREATE TABLE subscription_station (
    subscription INT         NOT NULL,
    station      VARCHAR(10) NOT NULL,
    PRIMARY KEY (subscription, station),
    FOREIGN KEY (subscription) REFERENCES subscriptions(id),
    FOREIGN KEY (station)      REFERENCES station(name)
);

CREATE TABLE endpoint_activity (
    id               INT          NOT NULL AUTO_INCREMENT,
    identifier       VARCHAR(45),
    endpoint_used    VARCHAR(256),
    files_downloaded INT,
    activity_date    DATE,
    activity_time    TIME,
    authorized       TINYINT      DEFAULT 0,
    data_transferred INT,
    PRIMARY KEY (id),
    FOREIGN KEY (identifier) REFERENCES subscriptions(identifier)
);
