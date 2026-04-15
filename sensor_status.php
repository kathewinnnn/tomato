<?php

$sensor_status = [
  'ENV-01'   => ['status'=>'active'],
  'ENV-02'   => ['status'=>'error',  'error_code'=>'E_TIMEOUT',  'error_note'=>'Sensor not responding for >60 s. Possible loose I²C connection or power drop at Node ENV-02.'],
  'ENV-03'   => ['status'=>'active'],
  'SOIL-01'  => ['status'=>'active'],
  'SOIL-02'  => ['status'=>'active'],
  'SOIL-03'  => ['status'=>'active'],
  'SOIL-04'  => ['status'=>'error',  'error_code'=>'E_RANGE',    'error_note'=>'Reading out of calibrated range (2%). Sensor probe may be damaged or probe tip exposed to air. Requires field inspection.'],
  'SOIL-05'  => ['status'=>'active'],
  'PWR-01'   => ['status'=>'active'],
  'PWR-02'   => ['status'=>'error',  'error_code'=>'E_VOLT_LOW', 'error_note'=>'Battery module reporting 3.1 V — below safe operating threshold of 3.6 V. Check battery cell health and charge controller output.'],
  'WATER-01' => ['status'=>'active'],
  'CHEM-01'  => ['status'=>'active'],
];

$errCount      = 0;
$activeCount   = 0;
$totalSensors  = count($sensor_status);

foreach ($sensor_status as $s) {
    if ($s['status'] === 'error') {
        $errCount++;
    } else {
        $activeCount++;
    }
}

$erroredSensors = [];
foreach ($sensor_status as $nodeId => $s) {
    if ($s['status'] === 'error') {
        $erroredSensors[$nodeId] = $s;
    }
}