<?php

    require_once(dirname(__FILE__) . '/FTPConnectionBase.php');
    require_once(dirname(__FILE__) . '/../PathOperations.php');
    require_once(dirname(__FILE__) . '/FTPListParser.php');
    require_once(dirname(__FILE__) . '/Exceptions.php');
    require_once(dirname(__FILE__) . "/../../lib/helpers.php");

    abstract class FTPTransferMode {
        public static function fromString($transferModeName) {
            switch ($transferModeName) {
                case "ASCII":
                    return FTP_ASCII;
                case "BINARY":
                    return FTP_BINARY;
                default:
                    throw new InvalidArgumentException("FTP Transfer mode must be ASCII or BINARY.");
            }
        }
    }

    class FTPConnection extends FTPConnectionBase {
        public function __construct($configuration) {
            parent::__construct($configuration);
            $this->sysType = null;
        }

        protected function handleConnect() {
            if ($this->configuration->isSSLMode())
                return @ftp_ssl_connect($this->configuration->getHost(), $this->configuration->getPort());

            return @ftp_connect($this->configuration->getHost(), $this->configuration->getPort());
        }

        protected function handleDisconnect() {
            if (@ftp_close($this->connection) === false) {
                $errorMessage =  $this->getLastError();
                throw new FileSourceConnectionException(
                    sprintf("Failed to close %s connection: %s", $this->getProtocolName(), $errorMessage),
                    LocalizableExceptionDefinition::$FAILED_TO_CLOSE_CONNECTION_ERROR, array(
                    'protocol' => $this->getProtocolName(),
                    'message' => $errorMessage
                ));
            }
        }

        protected function handleAuthentication() {
            return @ftp_login($this->connection, $this->configuration->getUsername(),
                $this->configuration->getPassword());
        }

        protected function configureUTF8() {
            @ftp_raw($this->connection, "OPTS UTF8 ON");
            // this may or may not work, but if it doesn't there's nothing we can do so just carry on
        }

        protected function handlePassiveModeSet($passiveMode) {
            return @ftp_pasv($this->connection, $passiveMode);
        }

        function handleRawDirectoryList($listArgs) {
            return @ftp_rawlist($this->connection, $listArgs);
        }

        protected function handleListDirectory($path, $showHidden) {
            if (!PathOperations::directoriesMatch($path, $this->getCurrentDirectory())) {
                $this->changeDirectory($path);
            }

            $listArgs = $showHidden ? '-a' : null;

            $dirList = @ftp_rawlist($this->connection, $listArgs);

            if ($dirList === false)
                throw new FileSourceOperationException(sprintf("Failed to list directory \"%s\"", $path),
                    LocalizableExceptionDefinition::$LIST_DIRECTORY_FAILED_ERROR,
                    array(
                        'path' => $path,
                    ));

            return new FTPListParser($dirList, $showHidden, $this->getSysType("unix"));
        }

        protected function rawGetSysType() {
            return @ftp_systype($this->connection);
        }

        protected function handleGetCurrentDirectory() {
            return @ftp_pwd($this->connection);
        }

        protected function handleChangeDirectory($newDirectory) {
            return @ftp_chdir($this->connection, $newDirectory);
        }

        /**
         * @param $transferOperation FTPTransferOperation
         * @return bool
         */
        protected function handleDownloadFile($transferOperation) {
            return @ftp_get($this->connection, $transferOperation->getLocalPath(), $transferOperation->getRemotePath(),
                $transferOperation->getTransferMode());
        }

        /**
         * @param $transferOperation FTPTransferOperation
         * @return bool
         */
        protected function handleUploadFile($transferOperation) {
            return @ftp_put($this->connection, $transferOperation->getRemotePath(), $transferOperation->getLocalPath(),
                $transferOperation->getTransferMode());
        }

        protected function handleDeleteFile($remotePath) {
            return @ftp_delete($this->connection, $remotePath);
        }

        protected function handleMakeDirectory($remotePath) {
            return @ftp_mkdir($this->connection, $remotePath);
        }

        protected function handleDeleteDirectory($remotePath) {
            return @ftp_rmdir($this->connection, $remotePath);
        }

        protected function handleRename($source, $destination) {
            return @ftp_rename($this->connection, $source, $destination);
        }

        protected function handleChangePermissions($mode, $remotePath) {
            return @ftp_chmod($this->connection, $mode, $remotePath);
        }
    }