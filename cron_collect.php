<?php
/**
 * Created by PhpStorm.
 * User: Ruben
 * Date: 8-2-14
 * Time: 14:43
 */

require("config.inc.php");
require("func.inc.php");

$dbh = anubis_db_connect();

$result = $dbh->query($show_tables);
db_error();

while ($row = $result->fetch(PDO::FETCH_NUM))
{
    if ($row[0] == "configuration")
        $gotconfigtbl = 1;
    if ($row[0] == "hosts")
        $gothoststbl = 1;
    if ($row[0] == "dev_stats")
        $gotdevstatstbl = 1;
    if ($row[0] == "host_stats")
        $gothoststatstbl = 1;
}

if (!isset($gotconfigtbl))
    include("configtbl.sql.php");

if (!isset($gothoststbl))
    include("hoststbl.sql.php");

if(!isset($gotdevstatstbl))
    include("hoststatstbl.sql.php");

if(!isset($gothoststatstbl))
    include("devstatstbl.sql.php");

$config = get_config_data();

$result = $dbh->query("SELECT * FROM hosts ORDER BY name ASC");
if ($result)
{

    while ($host_data = $result->fetch(PDO::FETCH_ASSOC)){
        if(get_host_status($host_data)){
            //HOST specific data
            $hostid = $host_data['id'];
            $desmhash = $host_data['mhash_desired'];
            $arr = array ('command'=>'summary','parameter'=>'');
            $summary_arr = send_request_to_host($arr, $host_data);
            $i = 0;
            $arr = array ('command'=>'devs','parameter'=>'');
            $devs_arr = send_request_to_host($arr, $host_data);

            $devs = process_host_devs($devs_arr, $activedevs, $fivesmhash, $max_temp);

            $h_accepted =    $summary_arr['SUMMARY'][0]['Accepted'];
            $h_rejected =    $summary_arr['SUMMARY'][0]['Rejected'];
            $h_discarded =   $summary_arr['SUMMARY'][0]['Discarded'];
            $h_stale =       $summary_arr['SUMMARY'][0]['Stale'];
            $h_getfail =     $summary_arr['SUMMARY'][0]['Get Failures'];
            $h_remfail =     $summary_arr['SUMMARY'][0]['Remote Failures'];
            /*$utility =     $summary_arr['SUMMARY'][0]['Utility'];
            $Wutility =    $summary_arr['SUMMARY'][0]['Work Utility'];*/
            $h_getworks =    $summary_arr['SUMMARY'][0]['Getworks'];/*
            $Diff1Accept = $summary_arr['SUMMARY'][0]['Difficulty Accepted'];*/

            $arr = array ('command'=>'coin','parameter'=>'');
            $coin_arr = send_request_to_host($arr, $host_data);

            if ($coin_arr)
                if ($coin_arr['STATUS'][0]['STATUS'] == 'S')
                    $Hash_Method = $coin_arr['COIN'][0]['Hash Method'];

            $h5s = 0;

            if ($devs_arr != null)
            {
                $id = $host_data['id'];
                while (isset($devs_arr['DEVS'][$i]))
                {
                    $d = $devs_arr['DEVS'][$i];
                    $enabled = ($d['Enabled'] == "Y") ? 1 : 0;
                    $status = $d['Status'];
                    $accepted =  $d['Accepted'];
                    $rejected =  $d['Rejected'];
                    $temp = $d['Temperature'];
                    $fan = 0;
                    $clock = 0;
                    $mem = 0;
                    $v = 0;
                    $intensity = 0;
                    if($Hash_Method == 'scrypt'){
                        $mhs5s = $d['MHS 5s'] * 1000;
                    }else{
                        $mhs5s = $d['MHS 5s'];
                    }
                    $h5s += $mhs5s;
                    $mhsav = $d['MHS av'];
                    $hwerr = $d['Hardware Errors'];

                    if (isset($d['GPU']))
                    {
                        $fan = $d['Fan Percent'];
                        $dev_name = $DEV_cell = "GPU" . $d['GPU'];
                        $clock = $d['GPU Clock'];
                        $mem = $d['Memory Clock'];
                        $v = $d['GPU Voltage'];
                        $intensity = $d['Intensity'];
                    }else if (isset($d['PGA'])){
                        $dev_name = $d['Name'] . $d['PGA'];
                    }else if (isset($d['ASC'])){
                        $dev_name = $d['Name'] . $d['ASC'];
                    }
                    $i++;
                    $sqlDev = "INSERT INTO dev_stats(host, dev, enabled, status, temp, fan, gpu, mem, v, h_5s, accepted, rejected, hw_error, intensity)
                    VALUES ($id, '$dev_name',  $enabled, '$status', $temp, $fan, $clock, $mem, $v, $mhs5s, $accepted, $rejected, $hwerr, $intensity);";
                    $dbh->exec($sqlDev);
                    db_error();
                }
            }

            $sqlHost = "INSERT INTO host_stats (host_id, h_5s, gets, accepted, rejected, discarded, stale, getFails, remFails)
                                        VALUES ($hostid, $h5s, $h_getworks, $h_accepted, $h_rejected, $h_discarded, $h_stale, $h_getfail, $h_remfail)";
            $dbh->exec($sqlHost);
            db_error();
        }
    }

}