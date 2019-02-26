<?php

/*
object(stdClass)#2 (4) {
  ["airport"]=>
  object(stdClass)#1 (2) {
    ["code"]=>
    string(3) "ATL"
    ["name"]=>
    string(53) "Atlanta, GA: Hartsfield-Jackson Atlanta International"
  }
  ["statistics"]=>
  object(stdClass)#4 (3) {
    ["flights"]=>
    object(stdClass)#3 (5) {
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
    object(stdClass)#5 (5) {
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
    object(stdClass)#6 (6) {
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
  object(stdClass)#7 (3) {
    ["label"]=>
    string(6) "2003/6"
    ["year"]=>
    int(2003)
    ["month"]=>
    int(6)
  }
  ["carrier"]=>
  object(stdClass)#8 (2) {
    ["code"]=>
    string(2) "AA"
    ["name"]=>
    string(22) "American Airlines Inc."
  }
}
*/

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

$conn = new mysqli('localhost', 'root', 'root', 'airlines');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$delete_script = "DELETE FROM ";

foreach (['airports', 'carriers', 'airport_carrier'] as $table_name) {
	$conn->query($delete_script . $table_name);
}

$sql = "INSERT INTO `airports` (code, name) VALUES (?, ?);";
$stmt = $conn->prepare($sql);

$stmt->bind_param("ss", $code, $name);

echo 'Inserting airports...' . PHP_EOL;
foreach ($airports as $airport_code => $airport_name) {
	$code = $airport_code;
	$name = $airport_name;
	$stmt->execute();
}
echo 'Inserted all airports.' . PHP_EOL;

$sql = "INSERT INTO `carriers` (code, name) VALUES (?, ?);";
$stmt = $conn->prepare($sql);

$stmt->bind_param("ss", $code, $name);

echo 'Inserting carriers...' . PHP_EOL;
foreach ($carriers as $carrier_code => $carrier_name) {
	$code = $carrier_code;
	$name = $carrier_name;
	$stmt->execute();
}
echo 'Inserted all carriers.' . PHP_EOL;

$sql = "INSERT INTO `airport_carrier` VALUES ();"

$stmt->close();
$conn->close();