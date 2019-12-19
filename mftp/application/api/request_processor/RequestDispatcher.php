<?php
    require_once(dirname(__FILE__) . '/../constants.php');
    includeMonstaConfig();
    require_once(dirname(__FILE__) . '/../lib/helpers.php');
    require_once(dirname(__FILE__) . '/../lib/logging.php');
    require_once(dirname(__FILE__) . '/../file_sources/configuration/ConfigurationFactory.php');
    require_once(dirname(__FILE__) . '/../file_sources/connection/ConnectionFactory.php');
    require_once(dirname(__FILE__) . '/../file_sources/connection/RecursiveFileFinder.php');
    require_once(dirname(__FILE__) . '/../file_sources/connection/ZipBuilder.php');
    require_once(dirname(__FILE__) . '/../file_sources/transfers/TransferOperationFactory.php');
    require_once(dirname(__FILE__) . '/../stored_authentication/AuthenticationStorage.php');
    require_once(dirname(__FILE__) . '/../licensing/KeyPairSuite.php');
    require_once(dirname(__FILE__) . '/../licensing/LicenseReader.php');
    require_once(dirname(__FILE__) . '/../licensing/LicenseWriter.php');
    require_once(dirname(__FILE__) . '/../licensing/AffiliateChecker.php');
    require_once(dirname(__FILE__) . '/../system/SystemVars.php');
    require_once(dirname(__FILE__) . '/../system/ApplicationSettings.php');
    require_once(dirname(__FILE__) . '/../system/UserBanManager.php');
    require_once(dirname(__FILE__) . '/../file_fetch/HttpRemoteUploadFetchRequest.php');
    require_once(dirname(__FILE__) . '/../file_fetch/HTTPFetcher.php');
    require_once(dirname(__FILE__) . '/../file_sources/MultiStageUploadHelper.php');
    require_once(dirname(__FILE__) . '/../file_sources/connection/ArchiveExtractor.php');
    require_once(dirname(__FILE__) . '/../install/MonstaUpdateInstallContext.php');
    require_once(dirname(__FILE__) . '/../install/MonstaInstaller.php');

    class RequestDispatcher {
        /**
         * @var ConnectionBase
         */
        private $connection;

        /**
         * @var string
         */
        private $connectionType;

        /**
         * @var array
         */
        private $rawConfiguration;

        public function __construct($connectionType, $rawConfiguration, $configurationFactory = null,
                                    $connectionFactory = null, $skipConfiguration = false) {
            $this->connectionType = $connectionType;
            /* allow factory objects to be passed in for testing with mocks */
            if ($skipConfiguration) {
                $this->connection = null;
            } else {
                $this->rawConfiguration = $rawConfiguration;
                $configurationFactory = is_null($configurationFactory) ? new ConfigurationFactory() : $configurationFactory;
                $connectionFactory = is_null($connectionFactory) ? new ConnectionFactory() : $connectionFactory;
                $configuration = $configurationFactory->getConfiguration($connectionType, $rawConfiguration);
                $this->connection = $connectionFactory->getConnection($connectionType, $configuration);
            }
        }

        public function dispatchRequest($actionName, $context = null) {
            if (in_array($actionName, array(
                'listDirectory',
                'downloadFile',
                'uploadFile',
                'deleteFile',
                'makeDirectory',
                'deleteDirectory',
                'rename',
                'changePermissions',
                'copy',
                'testConnectAndAuthenticate',
                'checkSavedAuthExists',
                'writeSavedAuth',
                'readSavedAuth',
                'readLicense',
                'getSystemVars',
                'fetchRemoteFile',
                'uploadFileToNewDirectory',
                'downloadMultipleFiles',
                'createZip',
                'setApplicationSettings',
                'deleteMultiple',
                'extractArchive',
                'updateLicense',
                'reserveUploadContext',
                'transferUploadToRemote',
                'getRemoteFileSize',
                'getDefaultPath',
                'downloadForExtract',
                'cleanUpExtract',
                'resetPassword',
                'forgotPassword',
                'validateSavedAuthPassword',
                'downloadLatestVersionArchive',
                'installLatestVersion'
            ))) {
                if (!is_null($context))
                    return $this->$actionName($context);
                else
                    return $this->$actionName();
            }

            throw new InvalidArgumentException("Unknown action $actionName");
        }

        public function getConnection() {
            return $this->connection;
        }

        private function connectAndAuthenticate($isTest = false) {
            $sessionNeedsStarting = false;

            if (function_exists("session_status")) {
                if (session_status() == PHP_SESSION_NONE) {
                    $sessionNeedsStarting = true;
                }
            } else {
                $sessionNeedsStarting = session_id() == "";
            }

            if ($sessionNeedsStarting && !defined("MONSTA_UNIT_TEST_MODE")) {  // TODO: pass in this as parameter to avoid global state
                session_start();
            }

            $configuration = $this->connection->getConfiguration();

            $maxFailures = defined("MFTP_MAX_LOGIN_FAILURES") ? MFTP_MAX_LOGIN_FAILURES : 0;
            $loginFailureResetTimeSeconds = defined("MFTP_LOGIN_FAILURES_RESET_TIME_MINUTES")
                ? MFTP_LOGIN_FAILURES_RESET_TIME_MINUTES * 60 : 0;

            if (!isset($_SESSION["MFTP_LOGIN_FAILURES"]))
                $_SESSION["MFTP_LOGIN_FAILURES"] = array();

            $banManager = new UserBanManager($maxFailures, $loginFailureResetTimeSeconds,
                $_SESSION["MFTP_LOGIN_FAILURES"]);

            if ($banManager->hostAndUserBanned($configuration->getHost(), $configuration->getRemoteUsername())) {
                mftpActionLog("Log in", $this->connection, "", "", "Login and user has exceed maximum failures.");
                throw new FileSourceAuthenticationException("Login and user has exceed maximum failures.",
                    LocalizableExceptionDefinition::$LOGIN_FAILURE_EXCEEDED_ERROR, array(
                        "banTimeMinutes" => MFTP_LOGIN_FAILURES_RESET_TIME_MINUTES
                    ));
            }

            try {
                $this->connection->connect();
            } catch (Exception $e) {
                mftpActionLog("Log in", $this->connection, "", "", $e->getMessage());
                throw $e;
            }

            try {
                $this->connection->authenticate();
            } catch (Exception $e) {
                mftpActionLog("Log in", $this->connection, "", "", $e->getMessage());

                $banManager->recordHostAndUserLoginFailure($configuration->getHost(),
                    $configuration->getRemoteUsername());

                $_SESSION["MFTP_LOGIN_FAILURES"] = $banManager->getStore();

                throw $e;
            }

            $banManager->resetHostUserLoginFailure($configuration->getHost(), $configuration->getRemoteUsername());

            $_SESSION["MFTP_LOGIN_FAILURES"] = $banManager->getStore();

            if ($isTest) {
                // only log success if it is the first connect from the user
                mftpActionLog("Log in", $this->connection, "", "", "");
            }

            if ($configuration->getInitialDirectory() === "" || is_null($configuration->getInitialDirectory())) {
                return $this->connection->getCurrentDirectory();
            }

            return null;
        }

        public function disconnect() {
            if ($this->connection != null && $this->connection->isConnected())
                $this->connection->disconnect();
        }

        public function listDirectory($context) {
            $this->connectAndAuthenticate();
            $directoryList = $this->connection->listDirectory($context['path'], $context['showHidden']);
            $this->disconnect();
            return $directoryList;
        }

        public function downloadFile($context, $skipLog = false) {
            $this->connectAndAuthenticate();
            $transferOp = TransferOperationFactory::getTransferOperation($this->connectionType, $context);
            $this->connection->downloadFile($transferOp);
            if (!$skipLog) {
                // e.g. if editing a file don't log that it was also downloaded
                mftpActionLog("Download file", $this->connection, dirname($transferOp->getRemotePath()), monstaBasename($transferOp->getRemotePath()), "");
            }
            $this->disconnect();
        }

        public function downloadMultipleFiles($context) {
            $this->connectAndAuthenticate();
            $fileFinder = new RecursiveFileFinder($this->connection, $context['baseDirectory']);
            $foundFiles = $fileFinder->findFilesInPaths($context['items']);

            foreach ($foundFiles as $foundFile) {
                $fullPath = PathOperations::join($context['baseDirectory'], $foundFile);
                mftpActionLog("Download file", $this->connection, dirname($fullPath), monstaBasename($fullPath), "");
            }

            $zipBuilder = new ZipBuilder($this->connection, $context['baseDirectory']);
            $zipPath = $zipBuilder->buildZip($foundFiles);

            $this->disconnect();
            return $zipPath;
        }

        public function createZip($context) {
            $this->connectAndAuthenticate();
            $fileFinder = new RecursiveFileFinder($this->connection, $context['baseDirectory']);
            $foundFiles = $fileFinder->findFilesInPaths($context['items']);

            foreach ($foundFiles as $foundFile) {
                $fullPath = PathOperations::join($context['baseDirectory'], $foundFile);
                mftpActionLog("Download file", $this->connection, dirname($fullPath), monstaBasename($fullPath), "");
            }

            $zipBuilder = new ZipBuilder($this->connection, $context['baseDirectory']);
            $destPath = PathOperations::join($context['baseDirectory'], $context['dest']);
            $zipPath = $zipBuilder->buildLocalZip($foundFiles, $destPath);

            $this->connection->uploadFile(new FTPTransferOperation($zipPath, $destPath, FTP_BINARY));

            $this->disconnect();
            return $zipPath;
        }

        public function uploadFile($context, $preserveRemotePermissions = false, $skipLog = false) {
            $this->connectAndAuthenticate();
            $transferOp = TransferOperationFactory::getTransferOperation($this->connectionType, $context);
            $this->connection->uploadFile($transferOp, $preserveRemotePermissions);
            if (!$skipLog) {
                // e.g. if editing a file don't log that it was also uploaded
                mftpActionLog("Upload file", $this->connection, dirname($transferOp->getRemotePath()), monstaBasename($transferOp->getRemotePath()), "");
            }
            $this->disconnect();
        }

        public function uploadFileToNewDirectory($context) {
            // This will first create the target directory if it doesn't exist and then upload to that directory
            $this->connectAndAuthenticate();
            $transferOp = TransferOperationFactory::getTransferOperation($this->connectionType, $context);
            $this->connection->uploadFileToNewDirectory($transferOp);
            mftpActionLog("Upload file", $this->connection, dirname($transferOp->getRemotePath()), monstaBasename($transferOp->getRemotePath()), "");
            $this->disconnect();
        }

        public function deleteFile($context) {
            $this->connectAndAuthenticate();
            $this->connection->deleteFile($context['remotePath']);
            mftpActionLog("Delete file", $this->connection, dirname($context['remotePath']), monstaBasename($context['remotePath']), "");
            $this->disconnect();
        }

        public function makeDirectory($context) {
            $this->connectAndAuthenticate();
            $this->connection->makeDirectory($context['remotePath']);
            $this->disconnect();
        }

        public function deleteDirectory($context) {
            $this->connectAndAuthenticate();
            $this->connection->deleteDirectory($context['remotePath']);
            $this->disconnect();
        }

        public function rename($context) {
            $this->connectAndAuthenticate();

            if(array_key_exists('action', $context) && $context['action'] == 'move') {
                $action = 'Move';
            } else {
                $action = 'Rename';
            }

            $itemType = $this->connection->isDirectory($context['source']) ? 'folder' : 'file';

            $this->connection->rename($context['source'], $context['destination']);

            if ($action == 'Move') {
                mftpActionLog($action . " " . $itemType, $this->connection, dirname($context['source']),
                monstaBasename($context['source']) . " to " . $context['destination'],
                "");
            }
            if ($action == 'Rename') {
                mftpActionLog($action . " " . $itemType, $this->connection, dirname($context['source']),
                monstaBasename($context['source']) . " to " . monstaBasename($context['destination']),
                "");
            }

            $this->disconnect();
        }

        public function changePermissions($context) {
            $this->connectAndAuthenticate();

            $itemType = $this->connection->isDirectory($context['remotePath']) ? 'folder' : 'file';

            $this->connection->changePermissions($context['mode'], $context['remotePath']);

            mftpActionLog("CHMOD " . $itemType, $this->connection, dirname($context['remotePath']),
                monstaBasename($context['remotePath']) . " to " . decoct($context['mode']), "");

            $this->disconnect();
        }

        public function copy($context) {
            $this->connectAndAuthenticate();
            $this->connection->copy($context['source'], $context['destination']);
            $this->disconnect();
        }

        public function testConnectAndAuthenticate($context, $isInitalLogin = true) {
            $initialDirectory = $this->connectAndAuthenticate($isInitalLogin);
            $serverCapabilities = array("initialDirectory" => $initialDirectory);

            if (isset($context['getServerCapabilities']) && $context['getServerCapabilities']) {
                $serverCapabilities["changePermissions"] = $this->connection->supportsPermissionChange();
            }

            clearOldTransfers();

            return array("serverCapabilities" => $serverCapabilities);
        }

        public function checkSavedAuthExists() {
            if ($this->readLicense() == null)
                return false;

            return AuthenticationStorage::configurationExists(AUTHENTICATION_FILE_PATH);
        }

        public function writeSavedAuth($context) {
            if ($this->readLicense() == null)
                return;

            AuthenticationStorage::saveConfiguration(AUTHENTICATION_FILE_PATH, $context['password'],
                $context['authData']);
        }

        public function readSavedAuth($context) {
            if ($this->readLicense() == null)
                return array();

            return AuthenticationStorage::loadConfiguration(AUTHENTICATION_FILE_PATH, $context['password']);
        }

        public function readLicense() {
            $keyPairSuite = new KeyPairSuite(PUBKEY_PATH);
            $licenseReader = new LicenseReader($keyPairSuite);
            $license = $licenseReader->readLicense(MONSTA_LICENSE_PATH);

            if (is_null($license))
                return $license;

            $publicLicenseKeys = array("expiryDate", "version", "isTrial", "licenseVersion", "productEdition");
            $publicLicense = array();
            foreach ($publicLicenseKeys as $publicLicenseKey) {
                if (isset($license[$publicLicenseKey]))
                    $publicLicense[$publicLicenseKey] = $license[$publicLicenseKey];
            }

            return $publicLicense;
        }

        private function recordAffiliateSource($licenseEmail) {
            $affiliateChecker = new AffiliateChecker();
            $installUrl = getMonstaInstallUrl();
            $affiliateId = defined("MFTP_AFFILIATE_ID") ? MFTP_AFFILIATE_ID : "";
            return $affiliateChecker->recordAffiliateSource($affiliateId, $licenseEmail, $installUrl);
        }

        public function updateLicense($context) {
            $licenseContent = $context['license'];
            $licenseWriter = new LicenseWriter($licenseContent, PUBKEY_PATH, MONSTA_CONFIG_DIR_PATH . "../license/");
            $licenseData = $licenseWriter->getLicenseData();

            if (!$this->recordAffiliateSource($licenseData['email'])) {
                $licenseWriter->throwInvalidLicenseException();
            }

            $licenseWriter->writeProFiles(dirname(__FILE__) . "/../resources/config_pro_template.php");
        }

        public function getSystemVars() {
            $systemVars = SystemVars::getSystemVarsArray();

            $applicationSettings = new ApplicationSettings(APPLICATION_SETTINGS_PATH);

            $systemVars['applicationSettings'] = $applicationSettings->getSettingsArray();
            return $systemVars;
        }

        public function setApplicationSettings($context) {
            $applicationSettings = new ApplicationSettings(APPLICATION_SETTINGS_PATH);
            $applicationSettings->setFromArray($context['applicationSettings']);
            $applicationSettings->save();
        }

        public function fetchRemoteFile($context) {
            $fetchRequest = new HttpRemoteUploadFetchRequest($context['source'], $context['destination']);
            $fetcher = new HTTPFetcher();
            try {
                $effectiveUrl = $fetcher->fetch($fetchRequest);
                $this->connectAndAuthenticate();

                $transferContext = array(
                    'localPath' => $fetcher->getTempSavePath(),
                    'remotePath' => $fetchRequest->getUploadPath($effectiveUrl)
                );
                mftpActionLog("Fetch file", $this->connection, dirname($transferContext['remotePath']), $context['source'], "");
                // mftpActionLog("Fetch file", $this->connection, dirname($context['destination']), $context['source'], "");

                $transferOp = TransferOperationFactory::getTransferOperation($this->connectionType, $transferContext);
                $this->connection->uploadFile($transferOp);
            } catch (Exception $e) {
                $fetcher->cleanUp();
                mftpActionLog("Fetch file", $this->connection, dirname($transferContext['remotePath']), $context['source'], $e->getMessage());
                //mftpActionLog("Fetch file", $this->connection, dirname($context['destination']), $context['source'], $e->getMessage());
                throw $e;
            }

            // this should be done in a finally to avoid repeated code but we need to support PHP < 5.5
            $fetcher->cleanUp();
        }

        public function deleteMultiple($context) {
            $this->connectAndAuthenticate();
            $this->connection->deleteMultiple($context['pathsAndTypes']);
            $this->disconnect();
        }

        public function downloadForExtract($context) {
            $this->connectAndAuthenticate();

            $remotePath = $context["remotePath"];
            $localPath = getTempTransferPath($context["remotePath"]);

            $rawTransferContext = array(
                "remotePath" => $remotePath,
                "localPath" => $localPath
            );

            $transferOp = TransferOperationFactory::getTransferOperation($this->connectionType, $rawTransferContext);
            $this->connection->downloadFile($transferOp);

            $extractor = new ArchiveExtractor($localPath, null);

            $archiveFileCount = $extractor->getFileCount(); // will throw exception if it's not valid

            $fileKey = generateRandomString(16);

            $_SESSION[MFTP_SESSION_KEY_PREFIX . $fileKey] = array(
                "archivePath" => $localPath,
                "extractDirectory" => PathOperations::remoteDirname($remotePath)
            );

            return array("fileKey" => $fileKey, "fileCount" => $archiveFileCount);
        }

        public function cleanUpExtract($context) {
            $fileKey = $context['fileKey'];

            if (!isset($_SESSION[MFTP_SESSION_KEY_PREFIX . $fileKey]))
                exitWith404("File key $fileKey not found in session.");

            $fileData = $_SESSION[MFTP_SESSION_KEY_PREFIX . $fileKey];

            if (!isset($fileData['archivePath']))
                exitWith404("archivePath not set in fileData.");

            $archivePath = $fileData['archivePath'];

            @unlink($archivePath); // if this fails not much we can do

            return true;
        }

        public function extractArchive($context) {
            if (!isset($context['fileKey']))
                exitWith404("fileKey not found in context.");

            $fileKey = $context['fileKey'];

            $this->connectAndAuthenticate();

            if (!isset($_SESSION[MFTP_SESSION_KEY_PREFIX . $fileKey]))
                exitWith404("$fileKey not found in session.");

            $fileInfo = $_SESSION[MFTP_SESSION_KEY_PREFIX . $fileKey];

            $archivePath = $fileInfo['archivePath'];
            $extractDirectory = $fileInfo['extractDirectory'];

            $applicationSettings = new ApplicationSettings(APPLICATION_SETTINGS_PATH);

            $extractor = new ArchiveExtractor($archivePath, $extractDirectory, $applicationSettings->getSkipMacOsSpecialFiles());

            try {
                $transferResult = $extractor->extractAndUpload($this->connection,
                    $context['fileIndexOffset'], $context['extractCount']);

                // $transferResult is array [isFinalTransfer(bool), itemsTransferred (in this iteration, not total)]
            } catch (Exception $e) {
                // this should be done in a finally to avoid repeated code but we need to support PHP < 5.5
                @unlink($archivePath);
                throw $e;
            }

            if ($transferResult[0]) { // is final transfer
                unset($_SESSION[MFTP_SESSION_KEY_PREFIX . $fileKey]);
                @unlink($archivePath);
            }

            return $transferResult;
        }

        public function reserveUploadContext($context) {
            $remotePath = $context['remotePath'];

            $localPath = getTempTransferPath($remotePath);

            $sessionKey = MultiStageUploadHelper::storeUploadContext($this->connectionType, $context['actionName'],
                $this->rawConfiguration, $localPath, $remotePath);

            return $sessionKey;
        }

        public function transferUploadToRemote($context) {
            $sessionKey = $context['sessionKey'];
            $uploadContext = MultiStageUploadHelper::getUploadContext($sessionKey);

            $localPath = $uploadContext['localPath'];
            $remotePath = $uploadContext['remotePath'];

            $transferContext = array(
                "localPath" => $localPath,
                "remotePath" => $remotePath
            );

            try {
                $resp = $this->dispatchRequest($uploadContext['actionName'], $transferContext);
                @unlink($localPath);
                unset($_SESSION[MFTP_SESSION_KEY_PREFIX . $sessionKey]);
                return $resp;
            } catch (Exception $e) {
                @unlink($localPath);
                unset($_SESSION[MFTP_SESSION_KEY_PREFIX . $sessionKey]);
                throw $e;
            }
        }

        public function getRemoteFileSize($context) {
            $this->connectAndAuthenticate();
            return $this->connection->getFileSize($context['remotePath']);
        }

        public function getDefaultPath() {
            $this->connectAndAuthenticate();
            return $this->connection->getCurrentDirectory();
        }

        public function resetPassword($context) {
            if (!function_exists('mftpResetPasswordHandler')) {
                throw new Exception("mftpResetPasswordHandler function is not defined.");
            }

            return mftpResetPasswordHandler($context['username'], $context['currentPassword'], $context['newPassword']);
        }

        public function forgotPassword($context) {
            if (!function_exists('mftpForgotPasswordHandler')) {
                throw new Exception("mftpForgotPasswordHandler function is not defined.");
            }

            return mftpForgotPasswordHandler($context['username']);
        }

        public function validateSavedAuthPassword($context) {
            return AuthenticationStorage::validateAuthenticationPassword(AUTHENTICATION_FILE_PATH, $context["password"]);
        }

        public function downloadLatestVersionArchive($context) {
            if (!AuthenticationStorage::validateAuthenticationPassword(AUTHENTICATION_FILE_PATH, $context["password"]))
                throw new LocalizableException("Could not read configuration, the password is probably incorrect.",
                    LocalizableExceptionDefinition::$PROBABLE_INCORRECT_PASSWORD_ERROR);

            $archiveFetchRequest = new HttpFetchRequest(MFTP_LATEST_VERSION_ARCHIVE_PATH);

            $fetcher = new HTTPFetcher();
            $fetcher->fetch($archiveFetchRequest);

            $mftpRoot = realpath(dirname(__FILE__) . "/../../../");

            $archivePath = PathOperations::join($mftpRoot, MFTP_LATEST_VERSION_ARCHIVE_TEMP_NAME);

            rename($fetcher->getTempSavePath(), $archivePath);

            return true;
        }

        public function installLatestVersion($context) {
            if (!AuthenticationStorage::validateAuthenticationPassword(AUTHENTICATION_FILE_PATH, $context["password"]))
                throw new LocalizableException("Could not read configuration, the password is probably incorrect.",
                    LocalizableExceptionDefinition::$PROBABLE_INCORRECT_PASSWORD_ERROR);

            $mftpRoot = realpath(dirname(__FILE__) . "/../../../");

            $archivePath = PathOperations::join($mftpRoot, MFTP_LATEST_VERSION_ARCHIVE_TEMP_NAME);

            if (MONSTA_DEBUG)
                return true;  // don't accidentally update the developer's machine

            $updateContext = new MonstaUpdateInstallContext();

            $installer = new MonstaInstaller($archivePath, $mftpRoot, $updateContext);
            $installer->install();
            return true;
        }
    }
