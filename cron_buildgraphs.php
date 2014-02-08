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

$dbh = anubis_db_connect();

$config = get_config_data();

$host_data_sql = "SELECT * FROM host_stats WHERE stamp > date_sub(now(), interval 1 day)";

$result = $dbh->query($host_data_sql);

$now = time();
$start = $now - 24 * 60 * 60;

function timeToBin($time, $binSize = 5){
    global $start;
    return (int) floor(($time - $start)/(60 * $binSize));
}

$hashRateData = array();
$acceptanceRate = array();
$rejectedRate = array();

for($i = 0; $i < 288; $i++){
    $acceptanceRate[$i] = $rejectedRate[$i] =  $hashRateData[$i] = 0;
}

$acc_prev = 0;
$rej_prev = 0;

while ($host_data = $result->fetch(PDO::FETCH_ASSOC)){
    $time = strtotime($host_data['stamp']);
    $bin = timeToBin($time);
    $hashRateData[$bin] += $host_data['h_5s'] / 5;
    $acc = $host_data['accepted'];
    $rej = $host_data['rejected'];
    if($acc_prev > 0){
        $accD = $acc - $acc_prev;
    }else $accD = 0;
    if($rej_prev > 0){
        $rejD = $rej - $rej_prev;
    } else $rejD = 0;
    $rej_prev = $rej;
    $acc_prev = $acc;
    $acceptanceRate[$bin] += $accD / 5;
    $rejectedRate[$bin] += $rejD / 5;
}

$chart_global_hashrate = new LineChart();
$chart_global_shares = new LineChart();

$dataSet_global_hashrate = new XYDataSet();
$dataSet_global_accepted = new XYDataSet();
$dataSet_global_rejected = new XYDataSet();

for($i = 0; $i < 288; $i++){
    $label = '';
    if($i % 24 == 0){
        $label = date("H:i", $start + $i * 5 * 60);
    }
    $dataSet_global_hashrate->addPoint(new Point($label, $hashRateData[$i]));
    $dataSet_global_accepted->addPoint(new Point($label, $acceptanceRate[$i]));
    $dataSet_global_rejected->addPoint(new Point($label, $rejectedRate[$i]));
}

$chart_global_hashrate->setDataSet($dataSet_global_hashrate);
$chart_global_hashrate->setTitle('KH/s average 5 min');
$chart_global_hashrate->render('charts/global_hash.png');

$chart_global_shares->getPlot()->getPalette()->setLineColor(array(
    new Color(0, 255, 0),
    new Color(255, 0, 0)
));
$dataSeries_global_shares = new XYSeriesDataSet();
$dataSeries_global_shares->addSerie("Accepted", $dataSet_global_accepted);
$dataSeries_global_shares->addSerie("Rejected", $dataSet_global_rejected);
$chart_global_shares->setDataSet($dataSeries_global_shares);
$chart_global_shares->setTitle('Shares/min avarage 5 min');
$chart_global_shares->render('charts/global_shares.png');