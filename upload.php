<?php

require 'vendor/autoload.php';

use EburyLabs\Google\Service\Drive;

error_reporting(0);

$api_key = 'AIzaSyC5yvRnsAKlSxPCJCf94OF-MkYpJX3jn88';

try {
    $client = new Google_Client();
    $client->setApplicationName("Client_Library_Examples");
    $client->setDeveloperKey($api_key);

    $service = new Google_Service_Books($client);
    $optParams = array('filter' => 'free-ebooks');
    $results = $service->volumes->listVolumes('Henry David Thoreau', $optParams);

    foreach ($results as $item) {
        echo $item['volumeInfo']['title'], PHP_EOL;
    }

} catch (Google_Service_Exception $e) {
    var_dump($e->getMessage());
}