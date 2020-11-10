<?php

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2020, Watcha SAS
 *
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Francois Granade <francois@watcha.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Watcha_Integrator;

use GuzzleHttp\Client;
use OC\Files\Filesystem;
use OC\Files\View;
use OCA\Watcha_Integrator\Extension\Synapse;
use OCA\Watcha_Integrator\Extension\Files;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IURLGenerator;

const NEXTCLOUD_ROOT_DIRECTORY = '/';

/**
 * The class to handle the filesystem hooks
 *
 * Heavily inspired by the similar class in the Activity app.
 */
class FilesHooks
{
    const USER_BATCH_SIZE = 50;

    /** @var IURLGenerator */
    protected $urlGenerator;

    /** @var ILogger */
    protected $logger;

    /** @var CurrentUser */
    protected $currentUser;

    /** @var Client */
    protected $client;

    /** @var IConfig */
    private $config;

    /** @var string|bool */
    protected $moveCase = false;
    /** @var array */
    protected $oldAccessList;
    /** @var string */
    protected $oldParentPath;
    /** @var string */
    protected $oldParentOwner;
    /** @var string */
    protected $oldParentId;

    public function __construct(
        IURLGenerator $urlGenerator,
        ILogger $logger,
        CurrentUser $currentUser,
        IConfig $config
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->currentUser = $currentUser;
        $this->config = $config;
        $this->synapseHomeserverUrl = $this->config->getSystemValue('synapse_homeserver_url');
        $this->client = new Client(["base_uri" => $this->synapseHomeserverUrl, 'timeout' => 10]);
        $this->synapseAccessToken = $this->obtainSynapseAccessToken();
    }

    /**
     * Store the create hook events
     * @param string $path Path of the file that has been created
     */
    public function fileCreate($path)
    {
        if ($path === '/' || $path === '' || $path === null) {
            return;
        }

        list($fileName, $fileUrl) = $this->getFileInformations($path);

        if (!isset($fileName) || !isset($fileUrl)) {
            return;
        }

        $notifications = array(['activity_type' => Files::TYPE_SHARE_CREATED, 'directory' => dirname($path), 'limit_of_notification_propagation' => NEXTCLOUD_ROOT_DIRECTORY]);
        $this->sendNotificationsToSynapse($fileName, $fileUrl, $notifications);
    }

    /**
     * Store the update hook events
     * @param string $path Path of the file that has been modified
     */
    public function fileUpdate($path)
    {
        $this->sendNotificationsToSynapse($path, Files::TYPE_SHARE_UPDATED);
    }

    /**
     * Store the delete hook events
     * @param string $path Path of the file that has been deleted
     */
    public function fileDelete($path)
    {
        list($fileName, $fileUrl) = $this->getFileInformations($path);

        if (!isset($fileName) || !isset($fileUrl)) {
            return;
        }

        $notifications = array(['activity_type' => Files::TYPE_SHARE_DELETED, 'directory' => dirname($path), 'limit_of_notification_propagation' => NEXTCLOUD_ROOT_DIRECTORY]);
        $this->sendNotificationsToSynapse($fileName, $fileUrl, $notifications);
    }

    /**
     * Store the restore hook events
     * @param string $path Path of the file that has been restored
     */
    public function fileRestore($path)
    {
        list($fileName, $fileUrl) = $this->getFileInformations($path);

        if (!isset($fileName) || !isset($fileUrl)) {
            return;
        }

        $notifications = array(['activity_type' => Files::TYPE_SHARE_RESTORED, 'directory' => dirname($path), 'limit_of_notification_propagation' => NEXTCLOUD_ROOT_DIRECTORY]);
        $this->sendNotificationsToSynapse($fileName, $fileUrl, $notifications);
    }

    /**
     * @param string $oldPath Path of the file that has been moved
     * @param string $newPath Path of the file that has been moved
     */
    public function fileMove($oldPath, $newPath)
    {
        if (substr($oldPath, -5) === '.part' || substr($newPath, -5) === '.part') {
            // Do not add activities for .part-files
            $this->moveCase = false;
            return;
        }

        $oldDir = dirname($oldPath);
        $newDir = dirname($newPath);

        if ($oldDir === $newDir) {
            /**
             * a/b moved to a/c
             *
             * Cases:
             * - a/b shared: no visible change
             * - a/ shared: rename
             */
            $this->moveCase = 'rename';
            return;
        }

        if (strpos($oldDir, $newDir) === 0) {
            $this->moveCase = 'moveUp';
        } else if (strpos($newDir, $oldDir) === 0) {
            $this->moveCase = 'moveDown';
        } else {
            $this->moveCase = 'moveCross';
        }

        list($this->oldParentPath, $this->oldParentOwner, $this->oldParentId) = $this->getSourcePathAndOwner($oldDir);
        if ($this->oldParentId === 0) {
            // Could not find the file for the owner ...
            $this->moveCase = false;
            return;
        }
    }

    /**
     * Store the move hook events
     *
     * @param string $oldPath Path of the file that has been moved
     * @param string $newPath Path of the file that has been moved
     */
    public function fileMovePost($oldPath, $newPath)
    {
        // Do not add activities for .part-files
        if ($this->moveCase === false) {
            return;
        }

        list($fileName, $fileUrl) = $this->getFileInformations($newPath);

        if (!isset($fileName) && !isset($fileUrl)) {
            return;
        }

        $oldDir = dirname($oldPath);
        $newDir = dirname($newPath);

        switch ($this->moveCase) {
            case 'rename':
                $this->fileRenaming($oldPath, $newPath);
                break;
            case 'moveUp':
                /**
                 * a/b/c moved to a/c
                 *
                 * Cases:
                 *  In Watcha rooms, it's like :
                 *  - Deletion notification in source directory and all parents directories between source directory and target directory.
                 *  - Movement notification in target directory and in all parents directories of target directory.
                 */
                $notifications = array(
                    ['activity_type' => Files::TYPE_SHARE_DELETED, 'directory' => $oldDir, 'limit_of_notification_propagation' => $newDir],
                    ['activity_type' => Files::TYPE_SHARE_MOVED, 'directory' => $newDir, 'limit_of_notification_propagation' => NEXTCLOUD_ROOT_DIRECTORY]
                );
                break;
            case 'moveDown':
                /**
                 * a/b moved to a/c/b
                 *
                 * Cases:
                 *  In Watcha rooms, it's like :
                 *  - Movement notification in source directory and in all parents directories of source directory.
                 *  - Creation notification in target directory and all parents directories between target directory and source directory.
                 */
                $notifications = array(
                    ['activity_type' => Files::TYPE_SHARE_MOVED, 'directory' => $oldDir, 'limit_of_notification_propagation' => NEXTCLOUD_ROOT_DIRECTORY],
                    ['activity_type' => Files::TYPE_SHARE_CREATED, 'directory' => $newDir, 'limit_of_notification_propagation' => $oldDir]
                );
                break;
            case 'moveCross':
                /**
                 * a/b/c moved to a/d/c
                 *
                 *  In Watcha rooms, it's like :
                 *  - Deletion notification in source directory and all parents directories between source directory and root directory (/a in our example).
                 *  - Creation notification in target directory and all parents directories between target directory and root directory (/a in our example).
                 *  - Movement notification in root directory and all parents directories of root directory (/a in our example).
                 */
                $rootDir = $this->getRootDirectory($oldDir, $newDir);

                if (!isset($rootDir)) {
                    return;
                }

                $notifications = array(
                    ['activity_type' => Files::TYPE_SHARE_DELETED, 'directory' => $oldDir, 'limit_of_notification_propagation' => $rootDir],
                    ['activity_type' => Files::TYPE_SHARE_CREATED, 'directory' => $newDir, 'limit_of_notification_propagation' => $rootDir],
                    ['activity_type' => Files::TYPE_SHARE_MOVED, 'directory' => $rootDir, 'limit_of_notification_propagation' => NEXTCLOUD_ROOT_DIRECTORY]
                );
                break;
            default:
                return;
        }
        $this->sendNotificationsToSynapse($fileName, $fileUrl, $notifications);

        $this->moveCase = false;
    }

    /**
     * Send request to Synapse for Watcha rooms notifications
     *
     * @param string $fileName         The name of the file
     * @param int    $fileUrl          The Nextcloud url link to the file
     * @param string $notifications    Notifications to send in Watcha rooms.
     */
    protected function sendNotificationsToSynapse($fileName, $fileUrl, $notifications = array())
    {

        $body = json_encode(
            array(
                'file_name' => $fileName,
                'file_url' => $fileUrl,
                'notifications' => $notifications,
            )
        );

        $this->client->request('POST', Synapse::NOTIFICATION_ENDPOINT, ['headers' => ['Authorization' => 'Bearer ' . $this->synapseAccessToken], 'http_errors' => True, 'body' => $body]);
    }

    /**
     * Return the source
     *
     * @param string $path
     * @return array
     */
    protected function getSourcePathAndOwner($path)
    {
        $view = Filesystem::getView();
        try {
            $owner = $view->getOwner($path);
            $owner = !is_string($owner) || $owner === '' ? null : $owner;
        } catch (NotFoundException $e) {
            $owner = null;
        }
        $fileId = 0;
        $currentUser = $this->currentUser->getUID();

        if ($owner === null || $owner !== $currentUser) {
            /** @var \OCP\Files\Storage\IStorage $storage */
            list($storage,) = $view->resolvePath($path);

            if ($owner !== null && !$storage->instanceOfStorage('OCA\Files_Sharing\External\Storage')) {
                Filesystem::initMountPoints($owner);
            } else {
                // Probably a remote user, let's try to at least generate activities
                // for the current user
                if ($currentUser === null) {
                    list(, $owner,) = explode('/', $view->getAbsolutePath($path), 3);
                } else {
                    $owner = $currentUser;
                }
            }
        }

        $info = Filesystem::getFileInfo($path);
        if ($info !== false) {
            $ownerView = new View('/' . $owner . '/files');
            $fileId = (int) $info['fileid'];
            $path = $ownerView->getPath($fileId);
        }

        return array($path, $owner, $fileId);
    }

    /**
     * Return file name and file url of a file path pass into paremeter of the function.
     *
     * @param string $filePath
     * @return array
     */
    protected function getFileInformations($filePath)
    {
        // Do not add activities for .part-files
        if (substr($filePath, -5) === '.part') {
            $this->logger->warning("Do not add activities for .part-files");
            return;
        }

        list($filePath,, $fileId) = $this->getSourcePathAndOwner($filePath);
        if ($fileId === 0) {
            // Could not find the file for the owner ...
            $this->logger->warning("Could not find the files " . $filePath);
            return;
        }

        $info = Filesystem::getFileInfo($filePath);
        $isDir = ($info->getType() === \OCP\Files\FileInfo::TYPE_FOLDER);

        if ($isDir) {
            $this->logger->warning("The path " . $filePath . " point to a folder.");
            return;
        }

        $fileName = basename($filePath);
        $fileUrl = $this->urlGenerator->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $fileId]);

        return array($fileName, $fileUrl);
    }

    /**
     * Send a request to Synapse to get the access token of a admin user.
     *
     * @return string
     */
    protected function obtainSynapseAccessToken()
    {
        $homeserverUrl = $this->synapseHomeserverUrl;
        $hostname = parse_url($homeserverUrl, PHP_URL_HOST);
        $synapseUserId = "@" . Synapse::SERVICE_ACCOUNT_NAME . ":" . $hostname;
        $password = hash_hmac("sha512", utf8_encode($synapseUserId), utf8_encode($this->config->getSystemValue('synapse_service_account_password')));

        $body = json_encode(
            array(
                'type' => 'm.login.password',
                'user' => $synapseUserId,
                'password' => $password,
            )
        );

        $access_token = '';

        $response = $this->client->request('POST', Synapse::LOGIN_ENDPOINT, ['body' => $body, 'http_errors' => True]);

        if ($response->getStatusCode() === 200) {
            $this->logger->info("Request success to " . $homeserverUrl . Synapse::LOGIN_ENDPOINT);
            $access_token = json_decode($response->getBody(), JSON_PRETTY_PRINT)['access_token'];
        }

        return $access_token;
    }

    /**
     * Get the root directory of two other directories
     *
     * @param string $firstDirectory
     * @param string $secondDirectory
     * @return string The path of root directory
     */
    protected function getRootDirectory($firstDirectory, $secondDirectory)
    {
        $firstDirectoriesList = explode('/', $firstDirectory);
        $secondDirectoriesList = explode('/', $secondDirectory);
        $rootDirectory = array();

        for ($i = 0; $i < count($firstDirectoriesList); $i++) {
            if (!isset($secondDirectoriesList[$i]) || $secondDirectoriesList[$i] !== $firstDirectoriesList[$i]) {
                break;
            }
            array_push($rootDirectory, $firstDirectoriesList[$i]);
        };

        $rootDirectory = implode('/', $rootDirectory);

        return empty($rootDirectory) ? '/' : $rootDirectory;
    }
}
