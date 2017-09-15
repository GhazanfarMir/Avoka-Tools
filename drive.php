<?php

require 'vendor/autoload.php';

use EburyLabs\Google\Service\Drive;

if (
    empty($_GET)
    or empty($_GET['accId'])
    or $_GET['accId'] === 'null'
    or empty($_GET['formId'])
    or $_GET['formId'] === 'null'
    or empty($_GET['filename'])
    or $_GET['filename'] === 'null'
) {

    Logger::log('Required data is not supplied (e.g. accId, formId, filename)');

    die(json_encode(['Required data is not supplied (e.g. accId, formId, filename)']));

}

try {

    //file to capture output from Avoka call
    ob_start();

    //capture php://input stream
    $client_data = file_get_contents("php://input");
    echo $client_data;
    $attachment = ob_get_contents();

    //end capture
    ob_end_clean();

    $accId = filter_var($_GET['accId'], FILTER_SANITIZE_STRING);;
    $formId = filter_var($_GET['formId'], FILTER_SANITIZE_STRING);

    // decode the encoded filename
    $original_filename = base64_decode($_GET['filename']);


    // sets formId as the google drive folder name by default
    $folder_name = $accId;

    Drive::init();

    Drive::connect();

    // compose the relative path after brand folder
    $hierarchy = $folder_name . '\\' . $folder_name . Drive::$folderPostfix;

    $parent = Drive::$corporateClientsFolderId;

    // setup folders structure before uploading document, this will create new folders if do not exist already
    foreach (explode('\\', $hierarchy) as $folder) {

        // get the folder id of the bottom most folder so document can be copied in
        $parent = Drive::getFolderIdByName($folder, $parent);
    }

    $document = Drive::createDriveFile($original_filename, $attachment, $parent);

    print_r(json_encode([$document->name . " viewable at " . $document->webViewLink]));

} catch (Exception $e) {

    printf($e->getMessage());

}