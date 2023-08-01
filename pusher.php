<?php
// First, run 'composer require pusher/pusher-php-server'

$APP_CHANNEL="APP_CHANNEL";
$APP_EVENT=$_POST['event'];
$APP_ID = "APP_ID";
$APP_KEY= "APP_KEY";
$APP_SECRET = "APP_SECRET";
$APP_CLUSTER = "APP_CLUSTER";
$message=$_POST['message'];

require __DIR__ . '/vendor/autoload.php';

$pusher = new Pusher\Pusher($APP_KEY, $APP_SECRET, $APP_ID, array('cluster' => $APP_CLUSTER));

$pusher->trigger($APP_CHANNEL, $APP_EVENT, $message);
?>
