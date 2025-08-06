<?php
require('phpMQTT.php'); // Ensure this file is present in the same directory

$server   = 'mqtt.hsl.fi';
$port     = 1883; // Use 1883 if SSL is not supported by your phpMQTT.php or 8883 if SSL works!
$username = '';
$password = '';
$client_id = 'phpMQTT-hsl-' . uniqid();

$mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);

if(!$mqtt->connect(true, NULL, $username, $password)) {
    exit("Failed to connect to MQTT broker\n");
}

$topic = '/hfp/v2/journey/#';
$mqtt->subscribe([$topic => ['qos' => 0, 'function' => 'collectBusData']]);
$busPositions = [];
$lastWrite = time();

function collectBusData($topic, $msg) {
    // Only handle messages for /vp/bus/
    if (strpos($topic, '/vp/bus/') === false && strpos($topic, '/tlr/bus/') === false) {
        return; // Not a bus message, skip it
    }
    global $busPositions;
    $data = json_decode($msg, true);
    if (!isset($data['VP'])) return;
    $vp = $data['VP'];
    $id = $vp['veh'];
    $busPositions[$id] = [
        'line' => $vp['desi'],
        'veh' => $id,
        'lat' => $vp['lat'],
        'lon' => $vp['long'],
        'spd' => $vp['spd'],
        'dl' => $vp['dl'],
        'hdg' => $vp['hdg'],
        'tst' => $vp['tst'],
    ];
}

echo "Listening for bus data (writing every second)...\n";
while (true) {
    $mqtt->proc();
    if (time() - $lastWrite >= 1) {
        file_put_contents('buses.json', json_encode(array_values($busPositions), JSON_PRETTY_PRINT));
        $lastWrite = time();
    }
}
$mqtt->close();