<?php

/*
array(4) {
  ["airport"]=>
  array(2) {
    ["code"]=>
    string(3) "ATL"
    ["name"]=>
    string(53) "Atlanta, GA: Hartsfield-Jackson Atlanta International"
  }
  ["statistics"]=>
  array(3) {
    ["flights"]=>
    array(5) {
      ["cancelled"]=>
      int(5)
      ["on time"]=>
      int(561)
      ["total"]=>
      int(752)
      ["delayed"]=>
      int(186)
      ["diverted"]=>
      int(0)
    }
    ["# of delays"]=>
    array(5) {
      ["late aircraft"]=>
      int(18)
      ["weather"]=>
      int(28)
      ["security"]=>
      int(2)
      ["national aviation system"]=>
      int(105)
      ["carrier"]=>
      int(34)
    }
    ["minutes delayed"]=>
    array(6) {
      ["late aircraft"]=>
      int(1269)
      ["weather"]=>
      int(1722)
      ["carrier"]=>
      int(1367)
      ["security"]=>
      int(139)
      ["total"]=>
      int(8314)
      ["national aviation system"]=>
      int(3817)
    }
  }
  ["time"]=>
  array(3) {
    ["label"]=>
    string(6) "2003/6"
    ["year"]=>
    int(2003)
    ["month"]=>
    int(6)
  }
  ["carrier"]=>
  array(2) {
    ["code"]=>
    string(2) "AA"
    ["name"]=>
    string(22) "American Airlines Inc."
  }
}
*/

ini_set('memory_limit', '-1');

$dataset = json_decode(file_get_contents("airlines.json"), true);

var_dump($dataset[0]);

$airports = [];
$carriers = [];


foreach ($dataset as $key => $value) {
    $object = $value;
    $airport = $object['airport'];
    $airports[$airport['code']] = $airport['name'];
    $carrier = $object['carrier'];
    $carriers[$carrier['code']] = $carrier['name'];
}

echo 'Airlines: ' . PHP_EOL;
foreach ($airports as $airport) {
    echo $airport . PHP_EOL;
}

echo 'Carriers: ' . PHP_EOL;
foreach ($carriers as $carrier) {
    echo $carrier . PHP_EOL;
}

$db = new SQLite3("fly_ATG.sqlite"); // Andrew Tom George

if(!$db) {
    echo $db->lastErrorMsg();
} else {
    echo "Opened database successfully\n";
}

$drop_script = "DROP TABLE ";
foreach (['airports', 'carriers', 'airport_carrier'] as $table_name) {
    $db->query($drop_script . $table_name);
}

$db->exec("CREATE TABLE airports(airport_code varchar(10), airport_name varchar(254))");
$db->exec("CREATE TABLE carriers(carrier_code varchar(10), carrier_name varchar(254))");
$db->exec("CREATE TABLE airport_carrier(airport_code varchar(10), carrier_code varchar(254), flights_cancelled int, flights_on_time int, flights_delayed int, flights_diverted int, delays_late_aircraft int, delays_weather int, delays_security int, delays_national_aviation_system int, delays_carrier int, minutes_delayed_late_aircraft int, minutes_delayed_weather int, minutes_delayed_carrier int, minutes_delayed_security int, minutes_delayed_total int, minutes_delayed_national_aviation_system int, time_label varchar(254), time_year int, time_month int)");

$sql = 'INSERT INTO airports(airport_code, airport_name)'
      .'VALUES (:airport_code, :airport_name)';
$stmt = $db->prepare($sql);
echo 'Inserting airports...' . PHP_EOL;
foreach ($airports as $airport_code => $airport_name) {
    $stmt->bindValue(':airport_code', $airport_code);
    $stmt->bindValue(':airport_name', $airport_name);
    $stmt->execute();
}
echo 'Inserted all airports.' . PHP_EOL;

$sql = 'INSERT INTO `carriers` (carrier_code, carrier_name)'
      .'VALUES (:carrier_code, :carrier_name)';
$stmt = $db->prepare($sql);
echo 'Inserting carriers...' . PHP_EOL;
foreach ($carriers as $carrier_code => $carrier_name) {
    $stmt->bindValue(':carrier_code', $carrier_code);
    $stmt->bindValue(':carrier_name', $carrier_name);
    $stmt->execute();
}
echo 'Inserted all carriers.' . PHP_EOL;


$sql = 'INSERT INTO airport_carrier(airport_code, carrier_code,'
      .'flights_cancelled, flights_on_time, flights_delayed, flights_diverted,'
      .'delays_late_aircraft, delays_weather, delays_security, delays_national_aviation_system, delays_carrier,'
      .'minutes_delayed_late_aircraft, minutes_delayed_weather, minutes_delayed_carrier, minutes_delayed_security, minutes_delayed_total, minutes_delayed_national_aviation_system,'
      .'time_label, time_year, time_month)'
      .'VALUES (:airport_code, :carrier_code,'
      .':flights_cancelled, :flights_on_time, :flights_delayed, :flights_diverted,'
      .':delays_late_aircraft, :delays_weather, :delays_security, :delays_national_aviation_system, :delays_carrier,'
      .':minutes_delayed_late_aircraft, :minutes_delayed_weather, :minutes_delayed_carrier, :minutes_delayed_security, :minutes_delayed_total, :minutes_delayed_national_aviation_system,'
      .':time_label, :time_year, :time_month)';

$stmt = $db->prepare($sql);
$stmt->bindParam(':airport_code', $airport_code);
$stmt->bindParam(':carrier_code', $carrier_code);
$stmt->bindParam(':flights_cancelled', $flights_cancelled);
$stmt->bindParam(':flights_on_time', $flights_on_time);
$stmt->bindParam(':flights_delayed', $flights_delayed);
$stmt->bindParam(':flights_diverted', $flights_diverted);
$stmt->bindParam(':delays_late_aircraft', $delays_late_aircraft);
$stmt->bindParam(':delays_weather', $delays_weather);
$stmt->bindParam(':delays_security',$delays_security);
$stmt->bindParam(':delays_national_aviation_system', $delays_national_aviation_system);
$stmt->bindParam(':delays_carrier', $delays_carrier);
$stmt->bindParam(':minutes_delayed_late_aircraft', $minutes_delayed_late_aircraft);
$stmt->bindParam(':minutes_delayed_weather', $minutes_delayed_weather);
$stmt->bindParam(':minutes_delayed_security', $minutes_delayed_security);
$stmt->bindParam(':minutes_delayed_national_aviation_system', $minutes_delayed_national_aviation_system);
$stmt->bindParam(':minutes_delayed_carrier', $minutes_delayed_carrier);
$stmt->bindParam(':minutes_delayed_total', $minutes_delayed_total);
$stmt->bindParam(':time_label', $time_label);
$stmt->bindParam(':time_year', $time_year);
$stmt->bindParam(':time_month', $time_month);

echo 'Inserting many-to-many data...' . PHP_EOL;
$cnt = 0;
foreach ($dataset as $data) {
    var_dump($cnt);
    $cnt = $cnt + 1;
    $airport_code = $data['airport']['code'];
    $carrier_code = $data['carrier']['code'];
    $flights = $data['statistics']['flights'];
    $delay_counts = $data['statistics']['# of delays'];
    $delay_minutes = $data['statistics']['minutes delayed'];

    if (!empty($flights)) {
        $flights_cancelled = $flights['cancelled'];
        $flights_on_time = $flights['on time'];
        $flights_delayed = $flights['delayed'];
        $flights_diverted = $flights['diverted'];
    }

    if (!empty($delay_counts)) {
        $delays_late_aircraft = $delay_counts['late aircraft'];
        $delays_weather = $delay_counts['weather'];
        $delays_security = $delay_counts['security'];
        $delays_national_aviation_system = $delay_counts['national aviation system'];
        $delays_carrier = $delay_counts['carrier'];
    }

    if (!empty($data['minutes_delayed'])) {
        $minutes_delayed_late_aircraft = $delay_minutes['late aircraft'];
        $minutes_delayed_weather = $delay_minutes['weather'];
        $minutes_delayed_carrier = $delay_minutes['carrier'];
        $minutes_delayed_security = $delay_minutes['security'];
        $minutes_delayed_total = $delay_minutes['total'];
        $minutes_delayed_national_aviation_system = $delay_minutes['national aviation system'];
    }

    $time_label = $data['time']['label'];
    $time_year = $data['time']['year'];
    $time_month = $data['time']['month'];
    $stmt->execute();
}
