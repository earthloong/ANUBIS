<?php
/**
 * Created by PhpStorm.
 * User: Ruben
 * Date: 8-2-14
 * Time: 16:25
 */

require("config.inc.php");
require("func.inc.php");
require "libchart/classes/libchart.php";

$path = dirname(__FILE__) . '/';

$dbh = anubis_db_connect();

$config = get_config_data();

$host_data_sql = "SELECT * FROM host_stats WHERE stamp > date_sub(now(), interval 1 day)";

$binSize = 5;

$result = $dbh->query($host_data_sql);

$now = time();
$start = $now - 24 * 60 * 60;

function timeToBin($time, $binSize = 5){
    global $start;
    return (int) floor(($time - $start)/(60 * $binSize));
}

function emptyBins(){
    $res = array();
    for($i = 0; $i < 288; $i++){
        $res[$i] = 0;
    }
    return $res;
}

$acceptanceRate = emptyBins();
$rejectedRate = emptyBins();
$hashRateData = emptyBins();

$host_hash = array();
$host_accepted = array();
$host_rejected = array();

while ($host_data = $result->fetch(PDO::FETCH_ASSOC)){
    $hid = $host_data['host_id'];
    if(!isset($host_hash[$hid])){
        $host_hash[$hid] = emptyBins();
        $host_accepted[$hid] = emptyBins();
        $host_rejected[$hid] = emptyBins();
        $acc_prev[$hid] = 0;
        $rej_prev[$hid] = 0;
    }
    $time = strtotime($host_data['stamp']);
    $bin = timeToBin($time, $binSize);
    $hashRateData[$bin] += $host_data['h_5s'] / $binSize;
    $host_hash[$hid][$bin] += $host_data['h_5s'] / $binSize;
    $acc = $host_data['accepted'];
    $rej = $host_data['rejected'];
    if($acc_prev[$hid] < $acc && $acc_prev[$hid] != 0){
        $accD = $acc - $acc_prev[$hid];
    }else $accD = 0;
    if($rej_prev[$hid] < $rej && $rej_prev[$hid] != 0){
        $rejD = $rej - $rej_prev[$hid];
    } else $rejD = 0;
    $rej_prev[$hid] = $rej;
    $acc_prev[$hid] = $acc;
    $acceptanceRate[$bin] += $accD / $binSize;
    $rejectedRate[$bin] += $rejD / $binSize;
    $host_accepted[$hid][$bin] += $accD / $binSize;
    $host_rejected[$hid][$bin] +=  $rejD / $binSize;
}

$chart_global_hashrate = new LineChart(570);
$chart_global_shares = new LineChart(570);

$dataSet_global_hashrate = new XYDataSet();
$dataSet_global_accepted = new XYDataSet();
$dataSet_global_rejected = new XYDataSet();

for($i = 0; $i < 288; $i++){
    $label = '';
    if(($i+1) % 24 == 0){
        $label = date("H:i", $start + ($i+1) * 5 * 60);
    }
    $dataSet_global_hashrate->addPoint(new Point($label, $hashRateData[$i]));
    $dataSet_global_accepted->addPoint(new Point($label, $acceptanceRate[$i]));
    $dataSet_global_rejected->addPoint(new Point($label, $rejectedRate[$i]));
}

$chart_global_hashrate->setDataSet($dataSet_global_hashrate);
$chart_global_hashrate->setTitle('KH/s average 5 min');
$chart_global_hashrate->render($path.'charts/global_hash.png');

$chart_global_shares->getPlot()->getPalette()->setLineColor(array(
    new Color(0, 255, 0),
    new Color(255, 0, 0)
));
$dataSeries_global_shares = new XYSeriesDataSet();
$dataSeries_global_shares->addSerie("Accepted", $dataSet_global_accepted);
$dataSeries_global_shares->addSerie("Rejected", $dataSet_global_rejected);
$chart_global_shares->setDataSet($dataSeries_global_shares);
$chart_global_shares->setTitle('Shares/min avarage 5 min');
$chart_global_shares->render($path.'charts/global_shares.png');