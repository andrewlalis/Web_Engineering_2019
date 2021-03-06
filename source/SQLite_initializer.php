<?php

$start_time = microtime(true);

function dropOldTables(Sqlite3 $db)
{
    //echo 'Dropping old tables...' . PHP_EOL;
	$drop_script = "DROP TABLE ";
	foreach (['airports', 'carriers', 'statistics', 'statistics_flights', 'statistics_delays', 'statistics_minutes_delayed'] as $table_name) {
	    //echo "\tDropping " . $table_name . PHP_EOL;
	    $db->query($drop_script . $table_name);
	}
	//echo 'Done.' . PHP_EOL;
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
  carrier_name VARCHAR(254) NOT NULL /* A duplicate: ExpressJet Airlines Inc. */
);

CREATE TABLE statistics(
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  airport_id INTEGER NOT NULL,
  carrier_id INTEGER NOT NULL,
  time_label varchar(254),
  time_year INTEGER NOT NULL,
  time_month INTEGER NOT NULL,
  UNIQUE(airport_id, carrier_id, time_year, time_month),
  FOREIGN KEY (airport_id) REFERENCES airports(id),
  FOREIGN KEY (carrier_id) REFERENCES carriers(id)
);

CREATE TABLE statistics_flights(
  statistic_id INTEGER PRIMARY KEY,
  cancelled INTEGER NOT NULL DEFAULT 0,
  on_time INTEGER NOT NULL DEFAULT 0,
  delayed INTEGER NOT NULL DEFAULT 0,
  diverted INTEGER NOT NULL DEFAULT 0,
  total INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (statistic_id) REFERENCES statistics(id)
);

CREATE TABLE statistics_delays(
  statistic_id INTEGER PRIMARY KEY,
  late_aircraft INTEGER NOT NULL DEFAULT 0,
  weather INTEGER NOT NULL DEFAULT 0,
  security INTEGER NOT NULL DEFAULT 0,
  national_aviation_system INTEGER NOT NULL DEFAULT 0,
  carrier INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (statistic_id) REFERENCES statistics(id)
);

CREATE TABLE statistics_minutes_delayed(
  statistic_id INTEGER PRIMARY KEY,
  late_aircraft INTEGER NOT NULL DEFAULT 0,
  weather INTEGER NOT NULL DEFAULT 0,
  carrier INTEGER NOT NULL DEFAULT 0,
  security INTEGER NOT NULL DEFAULT 0,
  total INTEGER NOT NULL DEFAULT 0,
  national_aviation_system INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (statistic_id) REFERENCES statistics(id)
);

CREATE TABLE users(
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  address TEXT NOT NULL UNIQUE
);

CREATE TABLE user_requests(
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  endpoint_uri TEXT NOT NULL,
  request_type INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id)
                          ON UPDATE CASCADE 
                          ON DELETE CASCADE
);
SQL;
    //echo 'Creating tables...' . PHP_EOL;
    $db->exec($tables);
    //echo 'Done.' . PHP_EOL;
}

ini_set('memory_limit', '-1');

$db = new SQLite3("fly_ATG.sqlite"); // Andrew Tom George

if(!$db) {
    die($db->lastErrorMsg());
}

dropOldTables($db);
createTables($db);

$insert_airport_stmt = $db->prepare("
INSERT INTO airports (airport_code, airport_name)
VALUES (:airport_code, :airport_name);
");
$insert_airport_stmt->bindParam(':airport_code', $airport_code);
$insert_airport_stmt->bindParam(':airport_name', $airport_name);

$insert_carrier_stmt = $db->prepare("
INSERT INTO carriers (carrier_code, carrier_name)
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
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_late_aircraft', $minutes_delayed_late_aircraft);
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_weather', $minutes_delayed_weather);
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_carrier', $minutes_delayed_carrier);
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_security', $minutes_delayed_carrier);
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_total', $minutes_delayed_total);
$insert_minutes_delayed_stmt->bindParam(':minutes_delayed_national_aviation_system', $minutes_delayed_national_aviation_system);

$dataset = json_decode(file_get_contents("../dataset/airlines.json"), true);

//echo 'Inserting data...' . PHP_EOL;
$count = 0;
$db->exec("BEGIN;");
foreach ($dataset as $key => $object) {
    $airport_code = $object['airport']['code'];
    $airport_name = $object['airport']['name'];
    $result = $db->querySingle("SELECT id FROM airports WHERE airport_code = '" . $airport_code . "';");
    if ($result) {
        $airport_id = $result;
    } else {
        //echo "New airport found: {$airport_code}, {$airport_name}" . PHP_EOL;
        $insert_airport_stmt->execute();
        $airport_id = $db->lastInsertRowID();
    }

    $carrier_code = $object['carrier']['code'];
    $carrier_name = $object['carrier']['name'];
    $result = $db->querySingle("SELECT id FROM carriers WHERE carrier_code = '" . $carrier_code . "';");
    if ($result) {
        $carrier_id = $result;
    } else {
        //echo "New carrier found: {$carrier_code}, {$carrier_name}" . PHP_EOL;
        $insert_carrier_stmt->execute();
        $carrier_id = $db->lastInsertRowID();
    }

    $time_label = $object['time']['label'];
    $time_year = $object['time']['year'];
    $time_month = $object['time']['month'];
    $result = $insert_statistic_stmt->execute();
    if (!$result) {
        die("Could not insert new statistic with data: [airport_id={$airport_id}, carrier_id={$carrier_id}, time_label={$time_label}, time_year={$time_year}, time_month={$time_month}].");
    }
    $statistic_id = $db->lastInsertRowID();

    $flights = $object['statistics']['flights'];
    $flights_cancelled = $flights['cancelled'];
    $flights_on_time = $flights['on time'];
    $flights_total = $flights['total'];
    $flights_delayed = $flights['delayed'];
    $flights_diverted = $flights['diverted'];
    $result = $insert_flight_stmt->execute();
    if (!$result) {
        die("Could not insert flight statistics for statistic {$statistic_id}.");
    }

    $delays = $object['statistics']['# of delays'];
    $delays_late_aircraft = $delays['late aircraft'];
    $delays_weather = $delays['weather'];
    $delays_security = $delays['security'];
    $delays_national_aviation_system = $delays['national aviation system'];
    $delays_carrier = $delays['carrier'];
    $result = $insert_delay_stmt->execute();
    if (!$result) {
        die("Could not insert delay statistics for statistic {$statistic_id}.");
    }

    $minutes_delayed = $object['statistics']['minutes delayed'];
    $minutes_delayed_late_aircraft = $minutes_delayed['late aircraft'];
    $minutes_delayed_weather = $minutes_delayed['weather'];
    $minutes_delayed_carrier = $minutes_delayed['carrier'];
    $minutes_delayed_security = $minutes_delayed['security'];
    $minutes_delayed_total = $minutes_delayed['total'];
    $minutes_delayed_national_aviation_system = $minutes_delayed['national aviation system'];
    $result = $insert_minutes_delayed_stmt->execute();
    if (!$result) {
        die("Could not insert minutes_delayed statistics for statistic {$statistic_id}.");
    }

    $count++;

    $insert_airport_stmt->reset();
    $insert_carrier_stmt->reset();
    $insert_statistic_stmt->reset();
    $insert_flight_stmt->reset();
    $insert_delay_stmt->reset();
    $insert_minutes_delayed_stmt->reset();
}
$db->exec("COMMIT;");
$end_time = microtime(true);
//echo 'Done. Processed ' . $count . ' objects. Time: ' . ($end_time - $start_time) . 's' . PHP_EOL;

$db->close();
