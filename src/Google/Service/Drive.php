<?php

namespace EburyLabs\Google\Service;

use Google_Auth_AssertionCredentials;
use Google_Service_Drive_DriveFile;
use EburyLabs\Utils\Logger;
use Google_Service_Drive;
use Google_Client;
use Dotenv\Dotenv;

/**
 * Class Drive
 * @package EburyLabs\Utils\Google
 */
class Drive
{
    /**
     * @var
     */
    public static $service;

    /**
     * @var
     */
    public static $userEmail;

    /**
     * @var
     */
    public static $destination;

    /**
     * @var null
     */
    public static $userToImpersonate = null;

    /**
     * @var
     */
    public static $clientEmail;

    /**
     * @var
     */
    public static $scopes;

    /**
     * @var
     */
    public static $privateKey;

    /**
     * @var
     */
    public static $privateKeyPass;

    /**
     * @var
     */
    public static $rootFolder;

    /**
     * @var
     */
    public static $corporateClientsFolderName;

    /**
     * @var
     */
    public static $privateClientsFolderName;


    /**
     * @var
     */
    public static $corporateClientsFolderId;

    /**
     * @var
     */
    public static $privateClientsFolderId;

    /**
     * @var
     */
    public static $folderPostfix;

    /**
     * Drive constructor.
     */
    private function __construct()
    {
        printf('Error! You can not initialise the ' . __CLASS__ );
    }

    public static function loadEnv()
    {
        $dotenv = new Dotenv(__DIR__ . '/../../../');
        $dotenv->load();
    }

    /**
     * Initialise properties from environment variables
     */
    public static function init()
    {

        self::loadEnv();

        self::$userToImpersonate = getenv('GOOGLE_DRIVE_IMPERSONATE_EMAIL');

        self::$clientEmail = getenv('GOOGLE_DRIVE_CLIENT_EMAIL');

        self::$privateKey = file_get_contents(getenv('GOOGLE_DRIVE_CERTIFICATE_PATH'));

        self::$privateKeyPass = getenv('GOOGLE_DRIVE_PRIVATE_KEY_PASS');

        self::$corporateClientsFolderId = getenv('GOOGLE_DRIVE_UPLOAD_FOLDER_ID');

        self::$privateClientsFolderId = getenv('GOOGLE_DRIVE_PRIVATE_CLIENTS_FOLDER_ID');

        self::$folderPostfix = ' - Account Opening';

        self::$corporateClientsFolderName = 'Ebury corporate clients';

        self::$privateClientsFolderName = 'Ebury Private Clients';

        self::$rootFolder = 'Client Documentation';

        self::$scopes = [
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/drive.file'
        ];

    }

    /**
     * Initialise Google Drive Client
     */
    public static function connect()
    {

        try {

            if (null === self::$userToImpersonate) {

                // setup credentials for service account
                $credentials = new Google_Auth_AssertionCredentials(
                    self::$clientEmail,
                    self::$scopes,
                    self::$privateKey
                );

            } else {

                // impersonate user
                $credentials = new Google_Auth_AssertionCredentials(
                    self::$clientEmail,
                    self::$scopes,
                    self::$privateKey,
                    self::$privateKeyPass,                        // Default P12 password
                    'http://oauth.net/grant_type/jwt/1.0/bearer', // Default grant type
                    self::$userToImpersonate);
            }

            $client = new Google_Client();
            $client->setAssertionCredentials($credentials);
            if ($client->getAuth()->isAccessTokenExpired()) {
                $client->getAuth()->refreshTokenWithAssertion();
            }

            self::$service = new Google_Service_Drive($client);

        } catch (Google_Auth_Exception $e) {
            echo $e->getMessage();
            exit;
        }

    }

    /**
     * @param $fileId
     */
    public static function processBatchPermissions($fileId)
    {
        self::$service->getClient()->setUseBatch(true);

        try {

            $batch = self::$service->createBatch();

            // give access to the user
            $userPermissions = new Google_Service_Drive_Permission([
                "type" => "user",
                "role" => "writer",
                "emailAddress" => Drive::$userEmail
            ]);

            $request = self::$service->permissions->create($fileId, $userPermissions, array("fields" => "id"));

            $batch->add($request, "user");

            $results = $batch->execute();

            foreach ($results as $result) {
                if ($result instanceof Google_Service_Exception) {
                    // on error
                } else {
                    // on success
                }
            }

        } finally {

            self::$service->getClient()->setUseBatch(false);
        }
    }

    public static function getFolderIdByName($name, $parent_id = null)
    {
        if (false !== ($folderId = self::getDriveFoldersByName($name, $parent_id))) {

            // if any folder matched, return id of the first folder that matched
            return $folderId[0]->id;
        }

        $folder = self::createDriveFolder($name, $parent_id);
        return $folder->getId();
    }

    public static function getDriveFoldersByName($folder_name, $parent_id = null)
    {

        $ands = [
            "name='$folder_name'",
            "trashed=false",    // #WEB-1154 (only look for folders which are not trashed)
            "mimeType='application/vnd.google-apps.folder'",
            "'".self::$userToImpersonate . "' IN owners"
        ];

        if (null !== $parent_id) $ands[] = "'$parent_id' IN parents";

        try {

            Logger::log('Searching for folder: ' . implode(' and ', $ands));

            // check if the folder already exists
            $response = self::$service->files->listFiles(array(
                'q' => implode(' and ', $ands),
                'spaces' => 'drive',
                'pageToken' => null,
                'fields' => 'files(id, name)',
            ));

            if (!empty($response->files)) {

                // Google Drive classifies all folders & files as 'files', hence $response->files
                return $response->files;
            }

        } catch (Exception $e) {

            printf($e->getMessage());
        }

        return false;
    }

    /**
     * @param $folder_name
     * @param null $parents_id
     * @return mixed
     */
    public static function createDriveFolder($folder_name, $parents_id = null)
    {

        // set meta data for the folder
        $folder_metadata = [
            "name" => $folder_name,
            "mimeType" => 'application/vnd.google-apps.folder'
        ];

        if (null !== $parents_id) $folder_metadata['parents'] = is_array($parents_id) ? $parents_id : array($parents_id);

        // set a folder metadata
        $folderMetaData = new Google_Service_Drive_DriveFile($folder_metadata);

        // Disable options to download, print and copy for commenter and viewers
        // $folderMetaData->setViewersCanCopyContent(false);

        // Prevent editors from changing access and adding new people - #WEB-1155
        $folderMetaData->setWritersCanShare(false);

        // create drive folder
        $folder = self::$service->files->create($folderMetaData, ["fields" => "id"]);

        Logger::log('Folder ' . $folder_name . ' created!');

        return $folder;
    }

    public static function createDriveFile($filename, $content, $parents_id)
    {
        // set drive file metadata
        $fileMetadata = new Google_Service_Drive_DriveFile(array(
            'name' => $filename,
            'parents' => is_array($parents_id) ? $parents_id : array($parents_id)
        ));

        // Disable options to download, print and copy for commenter and viewers
        //$fileMetadata->setViewersCanCopyContent(false);

        // Prevent editors from changing access and adding new people - #WEB-1155
        $fileMetadata->setWritersCanShare(false);

        // copy file to the drive folder
        $file = self::$service->files->create($fileMetadata, array(
            'data' => $content,
            'uploadType' => 'multipart',
            'fields' => 'id, name, webViewLink'));

        Logger::log('File ' . $filename . ' created!');

        return $file;
    }

}