<?php

require 'vendor/autoload.php';

use EburyLabs\Utils\InputValidation;
use EburyLabs\Google\Service\Drive;

/**
 * Class EburyDrive
 */
class EburyDrive
{

    /**
     * @var
     */
    public static $accountId;

    /**
     * @var
     */
    public static $formId;

    /**
     * @var
     */
    public static $attachment;

    /**
     * @var
     */
    public static $filename;

    public static function init()
    {

        if (! (new InputValidation())->validated()) {
            return false;
        }

        EburyDrive::captureDate();

        Drive::init();

        Drive::connect();

        $document = EburyDrive::upload();

        print_r($document->name . " viewable at " . $document->webViewLink);
    }

    public static function captureDate()
    {

        // certain initialisation
        EburyDrive::$accountId = $accId = filter_var($_GET['accId'], FILTER_SANITIZE_STRING);
        EburyDrive::$formId = $formId = filter_var($_GET['formId'], FILTER_SANITIZE_STRING);
        EburyDrive::$filename = base64_decode($_GET['filename']);


        if (php_sapi_name() === 'cli') {

            EburyDrive::$attachment = file_get_contents(__DIR__ . '/sandbox/documents/CS-Ebury-GB-EN-SF-178.pdf');

            return;

        }


        //file to capture output from Avoka call
        ob_start();

        //capture php://input stream
        $client_data = file_get_contents("php://input");
        echo $client_data;
        EburyDrive::$attachment = ob_get_contents();

        //end capture
        ob_end_clean();

        return;

    }

    public static function upload()
    {

        // compose the relative path after brand folder
        $hierarchy = EburyDrive::$accountId . '\\' . EburyDrive::$accountId . Drive::$folderPostfix;

        $parent = Drive::$corporateClientsFolderId;

        // setup folders structure before uploading document, this will create new folders if do not exist already
        foreach (explode('\\', $hierarchy) as $folder) {

            // get the folder id of the bottom most folder so document can be copied in
            $parent = Drive::getFolderIdByName($folder, $parent);
        }

        return Drive::createDriveFile(EburyDrive::$filename, EburyDrive::$attachment, $parent);

    }


}