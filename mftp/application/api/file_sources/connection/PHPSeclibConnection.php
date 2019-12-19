<?php

    require_once(dirname(__FILE__) . "/../../lib/helpers.php");
    require_once(dirname(__FILE__) . "/SFTPConnectionBase.php");
    require_once(dirname(__FILE__) . "/PHPSeclibListParser.php");
    require_once(dirname(__FILE__) . "/../../lib/logging.php");
    require_once('Net/SFTP.php');
    require_once('Crypt/RSA.php');
    require_once('System/SSH_Agent.php');

    class PHPSeclibConnection extends SFTPConnectionBase {
        protected function handleConnect() {
            $conn = new Net_SFTP(escapeIpAddress($this->configuration->getHost()), $this->configuration->getPort());
            mftpLog(LOG_DEBUG, "New SFTPSecLib connection created to '{$this->configuration->getHost()}:{$this->configuration->getPort()}'");
            return $conn;
        }

        protected function handleDisconnect() {
            $this->connection->disconnect();
        }

        protected function postAuthentication() {
            // TODO: Implement postAuthentication() method.
        }

        protected function handleListDirectory($path, $showHidden) {
            $stat = $this->statRemoteFile($path);

            if ($stat === false || $stat['type'] != NET_SFTP_TYPE_DIRECTORY) {
                if ($stat === false)
                    $errorMessage = "$path does not exist";
                else
                    $errorMessage = "$path is not a directory";

                mftpLog(LOG_DEBUG, "SFTPSecLib Unable to list directory: $errorMessage");
                $error = array('message' => $errorMessage);
                $this->handleOperationError('LIST_DIRECTORY_OPERATION', $path, $error);
                return false;
            }

            $rawList = $this->connection->rawlist($path);
            $dirList = new PHPSeclibListParser($rawList, $showHidden);

            mftpLog(LOG_DEBUG, "SFTPSecLib listed directory: $path. Returned " . count($rawList) . " results.");

            return $dirList;
        }

        protected function handleDownloadFile($transferOperation) {
            $transferSuccess = $this->connection->get($transferOperation->getRemotePath(),
                $transferOperation->getLocalPath());

            if ($transferSuccess)
                mftpLog(LOG_DEBUG, "SFTPSecLib got '{$transferOperation->getRemotePath()}' to '{$transferOperation->getLocalPath()}'");
            else {
                $this->setLastError($this->connection->getLastSFTPError(), $transferOperation->getRemotePath());
                mftpLog(LOG_WARNING, "SFTPSecLib failed to get '{$transferOperation->getRemotePath()}' to '{$transferOperation->getLocalPath()}': {$this->lastError['message']}");
            }

            return $transferSuccess;
        }

        protected function handleUploadFile($transferOperation) {
            $transferSuccess = $this->connection->put($transferOperation->getRemotePath(),
                $transferOperation->getLocalPath(), NET_SFTP_LOCAL_FILE);

            if ($transferSuccess)
                mftpLog(LOG_DEBUG, "SFTPSecLib put '{$transferOperation->getRemotePath()}' to '{$transferOperation->getLocalPath()}'");
            else {
                $this->setLastError($this->connection->getLastSFTPError(), $transferOperation->getRemotePath());
                mftpLog(LOG_WARNING, "SFTPSecLib failed to put '{$transferOperation->getRemotePath()}' to '{$transferOperation->getLocalPath()}': {$this->lastError['message']}");
            }

            if ($transferSuccess && !is_null($transferOperation->getCreateMode()))
                $this->changePermissions($transferOperation->getCreateMode(), $transferOperation->getRemotePath());

            return $transferSuccess;
        }

        protected function handleDeleteFile($remotePath) {
            $deleteSuccess = $this->connection->delete($remotePath);

            if ($deleteSuccess)
                mftpLog(LOG_DEBUG, "SFTPSecLib deleted file '$remotePath'");
            else {
                $this->setLastError($this->connection->getLastSFTPError(), $remotePath);
                mftpLog(LOG_WARNING, "SFTPSecLib failed to delete file '$remotePath': {$this->lastError['message']}");
            }

            return $deleteSuccess;
        }

        protected function handleMakeDirectory($remotePath) {
            $createSuccess = $this->connection->mkdir($remotePath);

            if ($createSuccess)
                mftpLog(LOG_DEBUG, "SFTPSecLib created directory '$remotePath'");
            else {
                $this->setLastError($this->connection->getLastSFTPError(), $remotePath);
                mftpLog(LOG_WARNING, "SFTPSecLib failed to create directory '$remotePath': {$this->lastError['message']}");
            }

            return $createSuccess;
        }

        protected function handleDeleteDirectory($remotePath) {
            $deleteSuccess = $this->connection->rmdir($remotePath);

            if ($deleteSuccess)
                mftpLog(LOG_DEBUG, "SFTPSecLib deleted directory '$remotePath'");
            else {
                $this->setLastError($this->connection->getLastSFTPError(), $remotePath);
                mftpLog(LOG_WARNING, "SFTPSecLib failed to directory directory '$remotePath': {$this->lastError['message']}");
            }

            return $deleteSuccess;
        }

        protected function handleRename($source, $destination) {
            $renameSuccess = $this->connection->rename($source, $destination);

            if ($renameSuccess)
                mftpLog(LOG_DEBUG, "SFTPSecLib renamed '$source' to '$destination'");
            else {
                $this->setLastError($this->connection->getLastSFTPError(), $source);
                mftpLog(LOG_WARNING, "SFTPSecLib failed to rename ''$source' to '$destination': {$this->lastError['message']}");
            }

            return $renameSuccess;
        }

        protected function handleChangePermissions($mode, $remotePath) {
            $operationSuccess = $this->connection->chmod($mode, $remotePath);

            if ($operationSuccess)
                mftpLog(LOG_DEBUG, sprintf("SFTPSecLib changed perms of '%s' to '%o'", $remotePath, $mode));
            else {
                $this->setLastError($this->connection->getLastSFTPError(), $remotePath);
                mftpLog(LOG_DEBUG, sprintf("SFTPSecLib failed to change perms of '%s' to '%o': ", $remotePath, $mode, $this->lastError['message']));
            }

            return $operationSuccess;
        }

        protected function statRemoteFile($remotePath) {
            return $this->connection->stat($remotePath);
        }

        protected function authenticateByPassword() {
            $authSuccess = $this->connection->login($this->configuration->getRemoteUsername(),
                $this->configuration->getPassword());

            if ($authSuccess)
                mftpLog(LOG_DEBUG, "SFTPSecLib password login success '{$this->configuration->getRemoteUsername()}@{$this->configuration->getHost()}'");
            else
                mftpLog(LOG_WARNING, "SFTPSecLib password authentication failed for '{$this->configuration->getRemoteUsername()}@{$this->configuration->getHost()}'");

            return $authSuccess;
        }

        protected function authenticateByPublicKey() {
            $key = new Crypt_RSA();

            if (!is_null($this->configuration->getPassword()))
                $key->setPassword($this->configuration->getPassword());

            $key->loadKey(file_get_contents($this->configuration->getPublicKeyFilePath()));
            $key->loadKey(file_get_contents($this->configuration->getPrivateKeyFilePath()));

            $authSuccess = $this->connection->login($this->configuration->getRemoteUsername(), $key);

            if ($authSuccess)
                mftpLog(LOG_DEBUG, "SFTPSecLib key login success '{$this->configuration->getRemoteUsername()}@{$this->configuration->getHost()}'");
            else
                mftpLog(LOG_WARNING, "SFTPSecLib key authentication failed for '{$this->configuration->getRemoteUsername()}@{$this->configuration->getHost()}'");

            return $authSuccess;
        }

        protected function authenticateByAgent() {
            $agent = @new System_SSH_Agent();
            $authSuccess = $this->connection->login($this->configuration->getRemoteUsername(), $agent);

            if ($authSuccess)
                mftpLog(LOG_DEBUG, "SFTPSecLib agent login success '{$this->configuration->getRemoteUsername()}@{$this->configuration->getHost()}'");
            else
                mftpLog(LOG_WARNING, "SFTPSecLib agent authentication failed for '{$this->configuration->getRemoteUsername()}@{$this->configuration->getHost()}'");

            return $authSuccess;
        }

        protected function handleGetCurrentDirectory() {
            return $this->connection->realpath('.');
        }
    }