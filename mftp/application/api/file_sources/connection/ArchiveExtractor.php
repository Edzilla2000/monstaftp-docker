<?php

    require_once(dirname(__FILE__) . "/../../lib/helpers.php");
    require_once(dirname(__FILE__) . "/../PathOperations.php");
    require_once(dirname(__FILE__) . "/../../lib/LocalizableException.php");
    require_once(dirname(__FILE__) . "/../../lib/helpers.php");
    require_once(dirname(__FILE__) . "/ConnectionFactory.php");
    require_once(dirname(__FILE__) . "/../../vendor/autoload.php");

    use \wapmorgan\UnifiedArchive\UnifiedArchive;

    function recursiveDirectoryDelete($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!recursiveDirectoryDelete($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }

    class ArchiveExtractor {
        private $archivePath;
        private $uploadDirectory;
        private $existingDirectories;
        private $extractDirectory;
        private $flatFileList;
        private $archiveHandle;
        private $archiveExtension;
        private $isSkipMacOsFiles;

        public function __construct($archivePath, $uploadDirectory, $skipMacOsFiles = false) {
            $this->archivePath = $archivePath;
            $this->uploadDirectory = $uploadDirectory;
            $this->existingDirectories = array();
            $this->flatFileList = null;
            $this->archiveHandle = null;
            $this->archiveExtension = pathinfo($archivePath, PATHINFO_EXTENSION);
            $this->isSkipMacOsFiles = $skipMacOsFiles;
        }

        private function getSessionKey() {
            return "archive_contents_" . md5($this->getArchivePath());
        }

        private function getArchivePath() {
            return $this->archivePath;
        }

        private function extractArchiveFilePath($fullFilePath) {
            return $fullFilePath;
        }

        private function setupArchiveHandle() {
            if ($this->archiveHandle !== null)
                return;

            $archivePath = $this->getArchivePath();

            $this->archiveHandle = UnifiedArchive::open($archivePath);

            if (isset($_SESSION[MFTP_SESSION_KEY_PREFIX . $this->getSessionKey()]))
                $this->flatFileList = $_SESSION[MFTP_SESSION_KEY_PREFIX . $this->getSessionKey()];
            else {
                $this->flatFileList = $this->archiveHandle->getFileNames();
                $_SESSION[MFTP_SESSION_KEY_PREFIX . $this->getSessionKey()] = $this->flatFileList;
            }
        }

        public function getFileCount() {
            $this->setupArchiveHandle();
            return count($this->flatFileList);
        }

        private function getFileInfoAtIndex($fileIndex) {
            $fileInfo = $this->archiveHandle->getFileData($this->flatFileList[$fileIndex]);

            $fileName = $fileInfo->filename;

            $isDirectory = substr($fileName, strlen($fileName) - 1) == "/";

            return array($fileName, $isDirectory);
        }

        public function extractAndUpload($connection, $fileOffset, $stepCount) {
            $connection->changeDirectory($this->uploadDirectory);

            $this->createExtractDirectory();

            $fileMax = min($this->getFileCount(), $fileOffset + $stepCount);

            $itemsTransferred = 0;

            $startTime = time();

            try {
                for (; $fileOffset < $fileMax; ++$fileOffset) {
                    $this->extractAndUploadItem($connection, $this->archiveHandle, $fileOffset);
                    ++$itemsTransferred;

                    outputStreamKeepAlive();

                    if (time() - $startTime >= MFTP_EXTRACT_UPLOAD_TIME_LIMIT_SECONDS)
                        break;
                }
            } catch (Exception $e) {
                recursiveDirectoryDelete($this->extractDirectory);
                throw $e;
                // this should be done in a finally to avoid repeated code but we need to support PHP < 5.5
            }

            recursiveDirectoryDelete($this->extractDirectory);

            $extractFinished = $this->getFileCount() == $fileOffset;

            if ($extractFinished)
                $this->cleanup();

            return array($extractFinished, $itemsTransferred);
        }

        private function cleanup() {
            if (isset($_SESSION[MFTP_SESSION_KEY_PREFIX . $this->getSessionKey()]))
                unset($_SESSION[MFTP_SESSION_KEY_PREFIX . $this->getSessionKey()]);
        }

        private function getTransferOperation($connection, $localPath, $remotePath) {
            return TransferOperationFactory::getTransferOperation(strtolower($connection->getProtocolName()),
                array(
                    "localPath" => $localPath,
                    "remotePath" => $remotePath
                )
            );
        }

        private function createExtractDirectory() {
            $tempPath = monstaTempnam(getMonstaSharedTransferDirectory(), monstaBasename($this->archivePath) . "extract-dir");

            if (file_exists($tempPath))
                unlink($tempPath);

            mkdir($tempPath);
            if (!is_dir($tempPath))
                throw new Exception("Temp archive dir was not a dir");

            $this->extractDirectory = $tempPath;
        }

        private function isPathTraversalPath($itemName) {
            return strpos($itemName, "../") !== FALSE || strpos($itemName, "..\\") !== FALSE;
        }

        private function extractFileToDisk($archive, $extractDir, $itemPath) {
            if ($this->archiveExtension == "gz")
                $node = "/"; // gzip may only be extracted all at once
            else
                $node = "/" . $itemPath;


            if ($archive->extractNode($extractDir, $node) === false)
                throw new Exception("Unable to extract $node from archive");
        }

        private function extractAndUploadItem($connection, $archive, $itemIndex) {
            $itemInfo = $this->getFileInfoAtIndex($itemIndex);

            if ($this->isPathTraversalPath($itemInfo[0]))
                return;

            $itemIsDirectory = $itemInfo[1] === true;

            $archiveInternalPath = $this->extractArchiveFilePath($itemInfo[0]);

            if($this->isSkipMacOsFiles && (preg_match('/(^|\/)__MACOSX\//m', $archiveInternalPath) ||
                    preg_match('/(^|\/)\.DS_Store(\/|$)/m', $archiveInternalPath))) {
                return;
            }

            if (DIRECTORY_SEPARATOR == "\\")
                $archiveInternalPath = str_replace("\\", "/", $archiveInternalPath); // fix in windows

            if (!$itemIsDirectory)
                $this->extractFileToDisk($archive, $this->extractDirectory, $archiveInternalPath);

            $itemPath = PathOperations::join($this->extractDirectory, $archiveInternalPath);

            if (is_null($itemInfo[1]) && is_dir($itemPath))
                return;

            $uploadPath = PathOperations::join($this->uploadDirectory, $archiveInternalPath);

            $remoteDirectoryPath = $itemIsDirectory ? $uploadPath : PathOperations::remoteDirname($uploadPath);

            if (!$this->directoryRecordExists($remoteDirectoryPath)) {
                $connection->makeDirectoryWithIntermediates($remoteDirectoryPath);
                $this->recordExistingDirectories(PathOperations::directoriesInPath($remoteDirectoryPath));
            }

            if ($itemIsDirectory)
                return; // directory was created so just return, don't upload it

            $uploadOperation = $this->getTransferOperation($connection, $itemPath, $uploadPath);

            try {
                $connection->uploadFile($uploadOperation);
            } catch (Exception $e) {
                @unlink($itemPath);
                throw $e;
                // this should be done in a finally to avoid repeated code but we need to support PHP < 5.5
            }

            @unlink($itemPath);
        }

        private function directoryRecordExists($directoryPath) {
            // this is not true directory exists function, just if we have created it or a subdirectory in this object
            return array_search(PathOperations::normalize($directoryPath), $this->existingDirectories) !== false;
        }

        private function recordDirectoryExists($directoryPath) {
            if ($this->directoryRecordExists($directoryPath))
                return;

            $this->existingDirectories[] = PathOperations::normalize($directoryPath);
        }

        private function recordExistingDirectories($existingDirectories) {
            foreach ($existingDirectories as $existingDirectory) {
                $this->recordDirectoryExists($existingDirectory);
            }
        }
    }