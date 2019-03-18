<?php

$start_time = microtime(true);

function dropOldTables(Sqlite3 $db)
{
    echo 'Dropping old tables...' . PHP_EOL;
	$drop_script = "DROP TABLE ";
	foreach (['airports', 'carriers', 'statistics', 'statistics_flights', 'statistics_delays', 'statistics_minutes_delayed'] as $table_name) {
	    echo "\tDropping " . $table_name . PHP_EOL;
	    $db->query($drop_script . $table_name);
	}
	echo 'Done.' . PHP_EOL;
}

function createTables(Sqlite3 $db)
{
    $tables = <<<SQL
CREATE TABLE airports(
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  airport_code VARCHAR(10) NOT NULL UNIQUE,
  airport_name VARCHAR(254) NOT NULL UNIQUE
);

CREATE TABLE carriers(
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  carrier_code VARCHAR(10) NOT NULL UNIQUE,
  carrier_name VARCHAR(254) NOT NULL UNIQUE
);

CREATE TABLE statistics(
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  airport_id INTEGER,
  carrier_id INTEGER,
  time_label varchar(254),
  time_year INTEGER,
  time_month INTEGER,
  FOREIGN KEY (airport_id) REFERENCES airports(id),
  FOREIGN KEY (carrier_id) REFERENCES carriers(id)
);

CREATE TABLE statistics_flights(
  statistic_id INTEGER PRIMARY KEY,
  cancelled INTEGER,
  on_time INTEGER,
  delayed INTEGER,
  diverted INTEGER,
  total INTEGER,
  FOREIGN KEY (statistic_id) REFERENCES statistics(id)
);

CREATE TABLE statistics_delays(
  statistic_id INTEGER PRIMARY KEY,
  late_aircraft INTEGER,
  weather INTEGER,
  security INTEGER,
  national_aviation_system INTEGER,
  carrier INTEGER,
  FOREIGN KEY (statistic_id) REFERENCES statistics(id)
);

CREATE TABLE statistics_minutes_delayed(
  statistic_id INTEGER PRIMARY KEY,
  late_aircraft INTEGER,
  weather INTEGER,
  carrier INTEGER,
  security INTEGER,
  total INTEGER,
  national_aviation_system INTEGER,
  FOREIGN KEY (statistic_id) REFERENCES statistics(id)
);
SQL;
    echo 'Creating tables...' . PHP_EOL;
    $db->exec($tables);
    echo 'Done.' . PHP_EOL;
}

ini_set('memory_limit', '-1');

$db = new SQLite3("fly_ATG.sqlite"); // Andrew Tom George

if(!$db) {
    die($db->lastErrorMsg());
}

dropOldTables($db);
createTables($db);

$insert_airport_stmt = $db->prepare("
INSERT OR IGNORE INTO airports (airport_code, airport_name)
VALUES (:airport_code, :airport_name);
");
$insert_airport_stmt->bindParam(':airport_code', $airport_code);
$insert_airport_stmt->bindParam(':airport_name', $airport_name);

$insert_carrier_stmt = $db->prepare("
INSERT OR IGNORE INTO carriers (carrier_code, carrier_name)
VALUES (:carrier_code, :carrier_name);
");
$insert_carrier_stmt->bindParam(':carrier_code', $carrier_code);
$insert_carrier_stmt->bindParam(':carrier_name', $carrier_name);

$insert_statistic_stmt = $db->prepare("
INSERT INTO statistics (airport_id, carrier_id, time_label, time_year, time_month)
VALUES (:airport_id, :carrier_id, :time_label, :time_year, :time_month);
");
$insert_statistic_stmt->bindParam(':airport_id', $airport_id);
$insert_statistic_stmt->bindParam(':carrier_id', $carrier_id);
$insert_statistic_stmt->bindParam(':time_label', $time_label);
$insert_statistic_stmt->bindParam(':time_year', $time_year);
$insert_statistic_stmt->bindParam(':time_month', $time_month);

$insert_flight_stmt = $db->prepare("
INSERT INTO statistics_flights (statistic_id, cancelled, on_time, total, delayed, diverted)
VALUES (:statistic_id, :flights_cancelled, :flights_on_time, :flights_total, :flights_delayed, :flights_diverted);
");
$insert_flight_stmt->bindParam(':statistic_id', $statistic_id);
$insert_flight_stmt->bindParam(':flights_cancelled', $flights_cancelled);
$insert_flight_stmt->bindParam(':flights_on_time', $flights_on_time);
$insert_flight_stmt->bindParam(':flights_total', $flights_total);
$insert_flight_stmt->bindParam(':flights_delayed', $flights_delayed);
$insert_flight_stmt->bindParam(':flights_diverted', $flights_diverted);

$insert_delay_stmt = $db->prepare("
INSERT INTO statistics_delays (statistic_id, late_aircraft, weather, security, national_aviation_system, carrier)
VALUES (:statistic_id, :delays_late_aircraft, :delays_weather, :delays_security, :delays_national_aviation_system, :delays_carrier);
");
$insert_delay_stmt->bindParam(':statistic_id', $statistic_id);
$insert_delay_stmt->bindParam(':delays_late_aircraft', $delays_late_aircraft);
$insert_delay_stmt->bindParam(':delays_weather', $delays_weather);
$insert_delay_stmt->bindParam(':delays_security', $delays_security);
$insert_delay_stmt->bindParam(':delays_national_aviation_system', $delays_national_aviation_system);
$insert_delay_stmt->bindParam(':delays_carrier', $delays_carrier);

$insert_minutes_delayed_stmt = $db->prepare("
INSERT INTO statistics_minutes_delayed (statistic_id, late_aircraft, weather, carrier, security, total, national_aviation_system)
VALUES (:statistic_id, :minutes_delayed_late_aircraft, :minutes_delayed_weather, :minutes_delayed_carrier, :minutes_delayed_security, :minutes_delayed_total, :minutes_delayed_national_aviation_system);
");
$insert_minutes_delayed_stmt->bindParam(':statistic_id', $statistic_id);
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_late_aircraft', $statistic_id);
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_weather', $minutes_delayed_weather);
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_carrier', $minutes_delayed_carrier);
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_security', $minutes_delayed_carrier);
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_total', $minutes_delayed_total);
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_national_aviation_system', $minutes_delayed_national_aviation_system);

$dataset = json_decode(file_get_contents("../dataset/airlines.json"), true);

echo 'Inserting data...' . PHP_EOL;
$count = 0;
$db->exec("BEGIN;");
foreach ($dataset as $key => $object) {
    $airport_code = $object['airport']['code'];
    $airport_name = $object['airport']['name'];
    $insert_airport_stmt->execute();
    $result = $db->querySingle("SELECT * FROM airports WHERE airport_code = '" . $airport_code . "';");
    $airport_id = $result;

    $carrier_code = $object['carrier']['code'];
    $carrier_name = $object['carrier']['name'];
    $insert_carrier_stmt->execute();
    $result = $db->querySingle("SELECT * FROM carriers WHERE carrier_code = '" . $carrier_code . "';");
    $carrier_id = $result;

    $time_label = $object['time']['label'];
    $time_year = $object['time']['year'];
    $time_month = $object['time']['month'];
    $insert_statistic_stmt->execute();
    $statistic_id = $db->lastInsertRowID();

    $flights = $object['statistics']['flights'];
    $flights_cancelled = $flights['cancelled'];
    $flights_on_time = $flights['on time'];
    $flights_total = $flights['total'];
    $flights_delayed = $flights['delayed'];
    $flights_diverted = $flights['diverted'];
    $insert_flight_stmt->execute();

    $delays = $object['statistics']['# of delays'];
    $delays_late_aircraft = $delays['late aircraft'];
    $delays_weather = $delays['weather'];
    $delays_security = $delays['security'];
    $delays_national_aviation_system = $delays['national aviation system'];
    $delays_carrier = $delays['carrier'];
    $insert_delay_stmt->execute();

    $minutes_delayed = $object['statistics']['minutes delayed'];
    $minutes_delayed_late_aircraft = $minutes_delayed['late aircraft'];
    $minutes_delayed_weather = $minutes_delayed['weather'];
    $minutes_delayed_carrier = $minutes_delayed['carrier'];
    $minutes_delayed_security = $minutes_delayed['security'];
    $minutes_delayed_total = $minutes_delayed['total'];
    $minutes_delayed_national_aviation_system = $minutes_delayed['national aviation system'];
    $insert_minutes_delayed_stmt->execute();

    $count++;
}
$db->exec("COMMIT;");
$end_time = microtime(true);
echo 'Done. Processed ' . $count . ' objects. Time: ' . ($end_time - $start_time) . 's' . PHP_EOL;

$db->close();
