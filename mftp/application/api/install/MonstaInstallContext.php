<?php
    require_once(dirname(__FILE__) . "/../lib/LocalizableException.php");

    abstract class  MonstaInstallContext {
        protected static $archiveParentPath = "mftp/";
        protected static $archiveManifestPath = "README/UPDATE.txt";
        protected static $defaultManifest = array(
            "index.php",
            "application/api/",
            "application/frontend/",
            "application/languages/en_us.json"
        );

        private $warningExists = false;
        private $warningCode = null;
        private $warningMessage = null;

        /**
         * Validate if the install directory is valid for this context (e.g. if update should be an existing Monsta
         * directory, for a fresh install the directory should not exist)
         * @param $installDirectory string
         */
        abstract public function validateInstallDirectory($installDirectory);

        abstract public function install($archivePath, $installDirectory);

        protected static function getRelativeArchivePath($archiveFileName) {
            return substr($archiveFileName, strlen(self::$archiveParentPath));
        }

        protected static function listArchive($archiveHandle) {
            $fileList = array();

            $archiveNumFiles = $archiveHandle->numFiles;

            for ($fileIndex = 0; $fileIndex < $archiveNumFiles; ++$fileIndex) {
                $fileList[] = $archiveHandle->getNameIndex($fileIndex);
            }

            return $fileList;
        }

        protected function throwInvalidArchiveError($archivePath, $archiveHandle = null) {
            if (!is_null($archiveHandle))
                $archiveHandle->close();

            throw new LocalizableException("$archivePath is unreadable or not a Monsta FTP install archive.",
                LocalizableExceptionDefinition::$INSTALL_PATH_NOT_WRITABLE_ERROR, array("path" => $archivePath));
        }

        private function testArchiveForDefaultManifest($archivePath, $archiveHandle) {
            foreach (self::$defaultManifest as $manifestItem) {
                if(is_null($archiveHandle->getFromName(self::$archiveParentPath . $manifestItem))){
                    $this->throwInvalidArchiveError($archivePath, $archiveHandle);
                }
            }
        }

        private function extractUpdateText($archiveHandle) {
            return $archiveHandle->getFromName(self::$archiveParentPath . self::$archiveManifestPath);
        }

        private function getUpdateManifest($archivePath, $archiveHandle) {
            $manifest = array();

            $updateText = $this->extractUpdateText($archiveHandle);

            if ($updateText === FALSE) {
                $this->testArchiveForDefaultManifest($archivePath, $archiveHandle);
                return self::$defaultManifest;
            }

            $manifestLines = explode("\n", $updateText);

            foreach ($manifestLines as $manifestLine) {
                if (!preg_match('/^-/', $manifestLine))
                    continue;

                $manifest[] = trim(substr($manifestLine, 1));
            }

            if (count($manifest) === 0)
                $this->throwInvalidArchiveError($archivePath, $archiveHandle);

            return $manifest;
        }

        private function getArchiveHandle($archivePath) {
            $zip = new ZipArchive();
            if ($zip->open($archivePath) === FALSE)
                $this->throwInvalidArchiveError($archivePath);

            return $zip;
        }

        protected function getArchiveHandleAndUpdateManifest($archivePath) {
            $archiveHandle = $this->getArchiveHandle($archivePath);
            $updateManifest = $this->getUpdateManifest($archivePath, $archiveHandle);
            return array($archiveHandle, $updateManifest);
        }

        protected function setWarning($warningCode, $warningMessage) {
            $this->warningExists = true;
            $this->warningCode = $warningCode;
            $this->warningMessage = $warningMessage;
        }

        public function getWarningExists() {
            return $this->warningExists;
        }

        public function getWarning() {
            return array($this->warningCode, $this->warningMessage);
        }
    }