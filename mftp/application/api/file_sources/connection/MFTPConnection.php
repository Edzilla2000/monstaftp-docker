<?php

    require_once(dirname(__FILE__) . "/../../lib/helpers.php");
    require_once(dirname(__FILE__) . "/../../lib/logging.php");
    require_once(dirname(__FILE__) . '/mftp_functions.php');
    require_once(dirname(__FILE__) . '/FTPConnectionBase.php');

    class MFTPConnection extends FTPConnectionBase {
        protected function handleConnect() {
            $connectionOrFalse = mftp_connect($this->configuration->getHost(), $this->configuration->getPort());

            if($connectionOrFalse === false)
                mftpLog(LOG_WARNING, "MFTP failed to connect to '{$this->configuration->getHost()}:{$this->configuration->getPort()}'");
            else
                mftpLog(LOG_DEBUG, "MFTP connected to '{$this->configuration->getHost()}:{$this->configuration->getPort()}'");

            return $connectionOrFalse;
        }

        protected function configureUTF8() {
            $features = $this->getServerFeatures();
            if (array_search("UTF8", $features) !== false) {
                mftp_utf8_on($this->connection);
                // this may or may not work, but if it doesn't there's nothing we can do so just carry on
                mftpLog(LOG_DEBUG, "MFTP enabled UTF8");
            }
        }

        protected function handleChangeDirectory($newDirectory) {
            try {
                mftp_chdir($this->connection, $newDirectory);

                mftpLog(LOG_DEBUG, "MFTP changed directory to $newDirectory");

                return true;
            } catch (MFTPRemoteFileException $remoteFileException) {
                $this->setLastError($remoteFileException->getMessage(), $newDirectory);
                mftpLog(LOG_WARNING, "MFTP failed to change directory to '$newDirectory': {$remoteFileException->getMessage()}");
                return false;
            }
        }

        protected function handleGetCurrentDirectory() {
            $path = mftp_pwd($this->connection);

            mftpLog(LOG_DEBUG, "MFTP pwd is: '$path'");

            return $path;
        }

        function handleRawDirectoryList($listArgs) {
            try {
                $rawList = mftp_rawlist($this->connection, $listArgs);

                mftpLog(LOG_DEBUG, "MFTP listed directory: $listArgs. Returned " . count($rawList) . " results.");

                return $rawList;
            } catch (MFTPNoSuchRemoteFileException $remoteFileMissingException) {
                $this->setLastError($remoteFileMissingException->getMessage(), $listArgs);

                return false;
            }
        }

        protected function rawGetSysType() {
            try {
                $sysType = mftp_get_systype($this->connection);
            } catch (MFTPException $sysTypeException) {
                mftpLog(LOG_WARNING, "MFTP failed to get sysType: " . $sysTypeException->getMessage());

                return false;
            }

            mftpLog(LOG_DEBUG, "MFTP got sysType '$sysType'");

            return $sysType;
        }

        protected function handleDisconnect() {
            return mftp_disconnect($this->connection);
        }

        protected function handleAuthentication() {
            try {
                if($this->configuration->isSSLMode())
                    mftp_enable_ssl($this->connection);

                mftp_login($this->connection, $this->configuration->getUsername(),
                    $this->configuration->getPassword());

                mftpLog(LOG_INFO, "MFTP login success '{$this->configuration->getUsername()}@{$this->configuration->getHost()}'");

                return true;
            } catch(MFTPAuthenticationRequiresTlsException $tlsException) {
              throw new LocalizableException("The server you are connecting to requires TLS/SSL to be enabled.",
                  LocalizableExceptionDefinition::$TLS_REQUIRED_ERROR);
            } catch (MFTPAuthenticationException $e) {
                mftpLog(LOG_WARNING, "MFTP authentication failed for '{$this->configuration->getUsername()}': {$e->getMessage()}");
                return false;
            }
        }

        protected function handlePassiveModeSet($passiveMode) {
            mftp_pasv($this->connection, $passiveMode);

            mftpLog(LOG_DEBUG, "MFTP passive mode set to '$passiveMode'");

            return true;
        }

        protected function handleDownloadFile($transferOperation) {
            try {
                mftp_get($this->connection, $transferOperation->getLocalPath(),
                    $transferOperation->getRemotePath(), $transferOperation->getTransferMode());

                mftpLog(LOG_DEBUG, "MFTP got '{$transferOperation->getRemotePath()}' to '{$transferOperation->getLocalPath()}'");

                return true;
            } catch (MFTPException $exception) {
                $this->setLastError($exception->getMessage(), $transferOperation->getRemotePath());
                mftpLog(LOG_WARNING, "MFTP failed to get '{$transferOperation->getRemotePath()}' to '{$transferOperation->getLocalPath()}': {$exception->getMessage()}");
                return false;
            }
        }

        protected function handleUploadFile($transferOperation) {
            try {
                mftp_put($this->connection, $transferOperation->getRemotePath(),
                    $transferOperation->getLocalPath(), $transferOperation->getTransferMode(),
                    MFTP_UPLOAD_PROGRESS_CALLBACK_TIME_SECONDS,
                    function ($totalBytes){
                        outputStreamKeepAlive();
                    });

                mftpLog(LOG_DEBUG, "MFTP put '{$transferOperation->getLocalPath()}' to '{$transferOperation->getRemotePath()}'");

                return true;
            } catch (MFTPFileException $fileException) {
                $this->setLastError($fileException->getMessage(), $transferOperation->getRemotePath());

                mftpLog(LOG_WARNING, "MFTP failed to put '{$transferOperation->getLocalPath()}' to '{$transferOperation->getRemotePath()}': {$fileException->getMessage()}");

                return false;
            } catch (MFTPQuotaExceededException $quotaExceededException) {
                throw new LocalizableException("Could not upload the file as the account quota has been exceeded.",
                    LocalizableExceptionDefinition::$QUOTA_EXCEEDED_MESSAGE);
            }
        }

        protected function handleDeleteFile($remotePath) {
            try {
                mftp_delete($this->connection, $remotePath);

                mftpLog(LOG_DEBUG, "MFTP deleted $remotePath");

                return true;
            } catch (MFTPRemoteFileException $localFileException) {
                $this->setLastError($localFileException->getMessage(), $remotePath);
                mftpLog(LOG_WARNING, "MFTP failed to delete '$remotePath': {$localFileException->getMessage()}");
                return false;
            }
        }

        protected function handleMakeDirectory($remotePath) {
            try {
                mftp_mkdir($this->connection, $remotePath);

                mftpLog(LOG_DEBUG, "MFTP created directory '$remotePath'");
                return true;
            } catch (MFTPRemoteFileException $remoteException) {
                $this->setLastError($remoteException->getMessage(), $remotePath);
                mftpLog(LOG_WARNING, "MFTP failed to create directory '$remotePath': {$remoteException->getMessage()}");
                return false;
            }
        }

        protected function handleDeleteDirectory($remotePath) {
            try {
                mftp_rmdir($this->connection, $remotePath);

                mftpLog(LOG_DEBUG, "MFTP deleted directory '$remotePath'");

                return true;
            } catch (MFTPRemoteFileException $remoteException) {
                $this->setLastError($remoteException->getMessage(), $remotePath);
                mftpLog(LOG_WARNING, "MFTP failed to delete directory '$remotePath': {$remoteException->getMessage()}");
                return false;
            }
        }

        protected function handleRename($source, $destination) {
            try {
                mftp_rename($this->connection, $source, $destination);

                mftpLog(LOG_DEBUG, "MFTP renamed '$source' to '$destination'");

                return true;
            } catch (MFTPRemoteFileException $remoteException) {
                $this->setLastError($remoteException->getMessage(), $remoteException->getPath());

                mftpLog(LOG_WARNING, "MFTP failed to rename '$source' to '$destination': {$remoteException->getMessage()}");

                return false;
            }
        }

        protected function handleChangePermissions($mode, $remotePath) {
            try {
                mftp_chmod($this->connection, $mode, $remotePath);

                mftpLog(LOG_DEBUG, sprintf("MFTP chmod '%s' to '%o'", $remotePath, $mode));

                return true;
            } catch (MFTPRemoteFileException $remoteException) {
                $this->setLastError($remoteException->getMessage(), $remotePath);

                mftpLog(LOG_WARNING, sprintf("MFTP failed to chmod '%s' to '%o': %s", $remotePath, $mode,
                    $remoteException->getMessage()));

                return false;
            }
        }

        protected function getServerFeatures() {
            $cachedFeatures = $this->getCapabilitiesArrayValue("FEATURES");

            if(!is_null($cachedFeatures)) {
                if(is_null($this->connection->features))
                    $this->connection->features = $cachedFeatures;
                return $cachedFeatures;
            }
            if(!$this->isAuthenticated())
                return mftp_features($this->connection);
            else {
                mftpLog(LOG_INFO, "Attempting to get FEAT after authentication");
                return array(); // some FTP servers (ws_ftp) don't support getting FEAT after auth. default to empty array
            }
        }
    }
