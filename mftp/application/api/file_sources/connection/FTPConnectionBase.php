<?php

    require_once(dirname(__FILE__) . '/ConnectionBase.php');

    function normalizeFTPSysType($sysTypeName) {
        if (stripos($sysTypeName, 'unix') !== false || stripos($sysTypeName, 'macos'))
            return FTP_SYS_TYPE_UNIX;

        if (stripos($sysTypeName, 'windows') !== false)
            return FTP_SYS_TYPE_WINDOWS;

        throw new UnexpectedValueException(sprintf("Unknown FTP system type \"%s\".", $sysTypeName));
    }

    abstract class FTPConnectionBase extends ConnectionBase {
        /**
         * @var integer
         * This is lazy loaded
         */
        protected $sysType;

        protected $protocolName = 'FTP';

        abstract protected function rawGetSysType();

        abstract protected function handleChangeDirectory($newDirectory);

        abstract protected function handlePassiveModeSet($passive);

        abstract protected function handleRawDirectoryList($listArgs);

        abstract protected function configureUTF8();

        public function getSysType($defaultOnFailure = null) {
            if (!$this->isConnected())
                throw new FileSourceConnectionException("Attempting to get system type before connection.",
                    LocalizableExceptionDefinition::$GET_SYSTEM_TYPE_BEFORE_CONNECTION_ERROR);

            if ($this->sysType !== null)
                return $this->sysType;

            $cachedSysType = $this->getCapabilitiesArrayValue("SYSTYPE");

            if(!is_null($cachedSysType)) {
                $this->sysType = $cachedSysType;
                return $this->sysType;
            }

            if(!$this->isAuthenticated()) {
                $sysTypeName = $this->rawGetSysType();
            } else {
                mftpLog(LOG_INFO, "Attempting to get SYST after authentication");
                $sysTypeName = false;
            }

            if ($sysTypeName === false) {
                if(is_null($defaultOnFailure)) {
                    throw new FileSourceConnectionException("Failed to retrieve system type",
                        LocalizableExceptionDefinition::$GET_SYSTEM_TYPE_FAILED_ERROR);
                }

                $sysTypeName = $defaultOnFailure;
            }

            $this->sysType = normalizeFTPSysType($sysTypeName);
            return $this->sysType;
        }

        public function changeDirectory($newDirectory) {
            $this->ensureConnectedAndAuthenticated('DIRECTORY_CHANGE_OPERATION');

            if (!PathOperations::directoriesMatch($newDirectory, $this->getCurrentDirectory())) {
                if (!$this->handleChangeDirectory($newDirectory))
                    $this->handleOperationError('DIRECTORY_CHANGE_OPERATION', $newDirectory, $this->getLastError());

                if (substr($newDirectory, 0, 1) == "/") {
                    $this->currentDirectory = $newDirectory;
                } else {
                    if ($this->currentDirectory == null)
                        $this->currentDirectory = "/";

                    $this->currentDirectory = PathOperations::join($this->currentDirectory, $newDirectory);

                    if(substr($this->currentDirectory, -1) == "/" && $this->currentDirectory != "/")
                        $this->currentDirectory = substr($this->currentDirectory,0,
                            strlen($this->currentDirectory) - 1);
                }
            }
        }

        protected function postConnection() {
            $this->configureUTF8();
        }

        protected function postAuthentication() {
            $this->configurePassiveMode();
            $this->syncCurrentDirectory();
        }

        public function configurePassiveMode() {
            if (!$this->isAuthenticated())
                throw new FileSourceConnectionException("Can't configure passive mode before authentication.",
                    LocalizableExceptionDefinition::$PASSIVE_MODE_BEFORE_AUTHENTICATION_ERROR);

            if (!$this->handlePassiveModeSet($this->configuration->isPassiveMode())) {
                $passiveModeBoolName = $this->configuration->isPassiveMode() ? "true" : "false";

                throw new FileSourceConnectionException(sprintf("Failed to set passive mode to %s.",
                    $passiveModeBoolName), LocalizableExceptionDefinition::$FAILED_TO_SET_PASSIVE_MODE_ERROR,
                    array('is_passive_mode' => $passiveModeBoolName));
            }

        }

        protected function handleListDirectory($path, $showHidden) {
            if (!PathOperations::directoriesMatch($path, $this->getCurrentDirectory())) {
                $this->changeDirectory($path);
            }

            $listArgs = $showHidden ? '-a' : null;

            $dirList = $this->handleRawDirectoryList($listArgs);

            if ($dirList === false)
                throw new FileSourceOperationException(sprintf("Failed to list directory \"%s\"", $path),
                    LocalizableExceptionDefinition::$LIST_DIRECTORY_FAILED_ERROR,
                    array(
                        'path' => $path,
                    ));

            return new FTPListParser($dirList, $showHidden, $this->getSysType("unix"));
        }

        protected function handleCopy($source, $destination) {
            /* FTP does not provide built in copy functionality, so we copy file down to local and re-upload */
            $tempPath = monstaTempnam(getMonstaSharedTransferDirectory(), 'ftp-temp');
            try {
                $this->downloadFile(new FTPTransferOperation($tempPath, $source, FTP_BINARY));
                $this->uploadFile(new FTPTransferOperation($tempPath, $destination, FTP_BINARY));
            } catch (Exception $e) {
                @unlink($tempPath);
                throw $e;
                // this should be done in a finally to avoid repeated code but we need to support PHP < 5.5
            }

            @unlink($tempPath);
        }

        public function supportsPermissionChange() {
            return $this->getSysType("unix") == FTP_SYS_TYPE_UNIX;
        }

        protected function handleGetFileInfo($remotePath) {
            $remoteDirectory = dirname($remotePath);
            $fileName = monstaBasename($remoteDirectory);

            $dirList = $this->listDirectory($remoteDirectory, true);

            foreach ($dirList as $item) {
                if ($item->getName() == $fileName) {
                    return $item;
                }
            }

            return null;
        }

        protected function getServerFeatures() {
            // only overriden if supported
            return array();
        }

        protected function handleFetchServerCapabilities() {
            return array(
                "SYSTYPE" => $this->getSysType("unix"),
                "FEATURES" => $this->getServerFeatures()
            );
        }
    }