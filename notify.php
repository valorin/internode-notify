#!/usr/bin/env php
<?php

require_once __DIR__ .'/config.php';

/**
 * Load Service information
 */
$url  = "https://".INTERNODE_HOST.INTERNODE_URI;
$data = callApi($url);
$service_id = (string)$data->api->services->service;


/**
 * Load Usage
 */
$url = "https://".INTERNODE_HOST.INTERNODE_URI.'/'.$service_id;
$service = callApi($url.'/service');
$usage = callApi($url.'/usage');


/**
 * Calculate Remaining
 */
$remaining     = $service->api->service->quota - $usage->api->traffic;
$remaining     = ($remaining < 0) ? 0 : $remaining;
$daysRemaining = intval(((strtotime($service->api->service->rollover)) - time()) / (60*60*24)) + 1;
$targetRate    = $remaining / $daysRemaining;

/**
 * Send email if needed
 */
$lastNotify = file_get_contents(__DIR__ .'/'.NOTIFY_CACHE);
$notify = false;
$extra = '';
if ($targetRate <= ($lastNotify * ((100 - NOTIFY_PERCENTAGE) / 100))) {
    $notify = true;
    $extra  = "\nDOWN from the last alert...";
} elseif ($targetRate >= ($lastNotify * ((100 + NOTIFY_PERCENTAGE) / 100))) {
    $notify = true;
    $extra  = "\nUP from the last alert...";
}

$message = "Internode Usage: ".format_size($remaining)." left @ ".format_size($targetRate)." per day.";

if ($notify) {

    mail(NOTIFY_EMAIL, $message, $message.$extra);
    file_put_contents(__DIR__ .'/'.NOTIFY_CACHE, $targetRate);

    echo "\nEmail Sent!";
}

echo "\n".$message.$extra."\n";



function callApi($url) {

    for ($i = 0; $i < 5; $i++) {

        echo ".";

        $o = curl_init();
        curl_setopt($o, CURLOPT_URL, $url);
        curl_setopt($o, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($o, CURLOPT_USERPWD, INTERNODE_USERNAME . ':' . INTERNODE_PASSWORD);
        curl_setopt($o, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($o, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($o, CURLOPT_SSL_VERIFYHOST, FALSE);

        $result = curl_exec($o); // run the whole process
        $info = curl_getinfo($o);
        curl_close($o);

        if(!$result || $info['http_code'] != 200) {
            sleep(1);
            continue;
        }

        return new SimpleXMLElement($result);
    }

    echo "API failed 5 times, exiting.\n";
    exit(1);
}


function format_size($size) {
    if ($size < BYTE_A_KB) {
      return str_replace('@size', round($size, 2), '@size bytes');
    }
    else {
      $size = $size / BYTE_A_KB; // Convert bytes to kilobytes.
      $units = array(
        '@size KB',
        '@size MB',
        '@size GB',
        '@size TB',
        '@size PB',
        '@size EB',
        '@size ZB',
        '@size YB',
      );
      foreach ($units as $unit) {
        if (round($size, 2) >= BYTE_A_KB) {
          $size = $size / BYTE_A_KB;
        }
        else {
          break;
        }
      }
      return str_replace('@size', round($size, 2), $unit);
    }
}
