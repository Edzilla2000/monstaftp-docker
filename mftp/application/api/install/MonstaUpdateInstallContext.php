<?php
    require_once(dirname(__FILE__) . "/../lib/LocalizableException.php");
    require_once(dirname(__FILE__) . "/../file_sources/PathOperations.php");
    require_once(dirname(__FILE__) . "/MonstaInstallContext.php");

    class MonstaUpdateInstallContext extends MonstaInstallContext {
        private static $monstaTestItems = array(
            "application",
            "license",
            "settings",
            "application/api",
            "application/frontend",
            "index.php"
        );

        /**
         * @param $extractDir
         * @param $extractGroupRootItem
         * @return mixed
         */
        private static function getBackupPath($extractDir, $extractGroupRootItem) {
            $destinationBackupPath = PathOperations::join($extractDir, PathOperations::stripTrailingSlash($extractGroupRootItem) . ".bak");
            return $destinationBackupPath;
        }

        /**
         * @param $rootItem
         * @return bool
         */
        private static function getItemIsDirectory($rootItem) {
            return substr($rootItem, -1) === "/";
        }

        /**
         * @param $extractDir
         * @param $version
         * @param $rootItem
         * @return mixed
         */
        private static function buildItemExtractDirPath($extractDir, $version, $rootItem) {
            $itemIsDirectory = self::getItemIsDirectory($rootItem);

            $rootItemName = $itemIsDirectory ? substr($rootItem, 0, strlen($rootItem) - 1) : $rootItem;

            return PathOperations::join($extractDir, $rootItemName . "-" . $version);
        }

        /**
         * @param $updateManifest
         * @param $archiveFileName
         * @return mixed
         */
        private static function getManifestIndexForFileRoot($updateManifest, $archiveFileName) {
            $relativeArchiveFileName = self::getRelativeArchivePath($archiveFileName);

            for ($manifestIndex = 0; $manifestIndex < count($updateManifest); ++$manifestIndex) {
                $manifestItem = $updateManifest[$manifestIndex];
                if (substr($relativeArchiveFileName, 0, strlen($manifestItem)) == $manifestItem)
                    return $manifestIndex;
            }

            return false;
        }

        private function validateMonstaItemExists($installDirectory, $itemRelativePath) {
            $itemPath = PathOperations::join($installDirectory, $itemRelativePath);
            if (@!file_exists($itemPath)) {
                throw new LocalizableException("Could not update in $installDirectory as it does not appear to be a Monsta FTP install; missing $itemPath",
                    LocalizableExceptionDefinition::$INSTALL_DIRECTORY_INVALID_ERROR, array("installPath" => $installDirectory, "itemPath" => $itemPath));
            }
        }

        public function validateInstallDirectory($installDirectory) {
            if (@!file_exists($installDirectory))
                throw new LocalizableException("Could not update in $installDirectory as the directory does not exist",
                    LocalizableExceptionDefinition::$INSTALL_DIRECTORY_DOES_NOT_EXIST_ERROR, array("path" => $installDirectory));

            foreach (self::$monstaTestItems as $item) {
                $this->validateMonstaItemExists($installDirectory, $item);
            }

            if (@!is_writable($installDirectory)) {
                throw new LocalizableException("Could not update $installDirectory as the directory is not writable",
                    LocalizableExceptionDefinition::$INSTALL_PATH_NOT_WRITABLE_ERROR, array("path" => $installDirectory));
            }
        }

        private function extractVersionFromArchive($archivePath, $archiveHandle) {
            $version = $archiveHandle->getFromName(self::$archiveParentPath . "application/api/VERSION");

            if ($version === FALSE)
                $this->throwInvalidArchiveError($archivePath, $archiveHandle);

            return trim($version);
        }

        private function processExtractGroup($archiveHandle, $extractDir, $version, $rootItem, $files) {
            $fullExtractDir = self::buildItemExtractDirPath($extractDir, $version, $rootItem);

            return $archiveHandle->extractTo($fullExtractDir, $files);
        }

        private function extractAllGroups($archiveHandle, $installDirectory, $version, $extractGroups) {
            foreach ($extractGroups as $extractGroupRootItem => $extractFiles) {
                if (!$this->processExtractGroup($archiveHandle, $installDirectory, $version, $extractGroupRootItem,
                    $extractFiles)
                ) {
                    return false;
                }
            }

            return true;
        }

        private function restoreBackups($extractDir, $extractGroups) {
            // go back and move backup dirs back into place if something fails
            $errorOccurred = false;

            foreach ($extractGroups as $extractGroupRootItem => $extractFiles) {
                $originalPath = PathOperations::join($extractDir, $extractGroupRootItem);
                $destinationBackupPath = self::getBackupPath($extractDir, $extractGroupRootItem);

                if (!@file_exists($destinationBackupPath)) {
                    continue; // don't restore as there was not one originally
                }

                if (@file_exists($originalPath)) {
                    if (!PathOperations::recursiveDelete($originalPath)) {
                        $errorOccurred = true;
                        continue;
                    }
                }

                if (!@rename($destinationBackupPath, $originalPath)) {
                    $errorOccurred = true;
                }
            }

            if ($errorOccurred) {
                // things have gone pretty wrong here so here's a hail mary
                throw new LocalizableException("Restoring backup after failed install failed.",
                    LocalizableExceptionDefinition::$INSTALL_SETUP_BACKUP_RESTORE_ERROR);
            }
        }

        private function cleanUpBackups($extractDir, $extractGroups) {
            $cleanupSuccess = true;

            foreach ($extractGroups as $extractGroupRootItem => $extractFiles) {
                $destinationBackupPath = self::getBackupPath($extractDir, $extractGroupRootItem);

                if (!file_exists($destinationBackupPath)) {
                    continue; // don't delete as it wasn't backed up
                }

                if (!PathOperations::recursiveDelete($destinationBackupPath)) {
                    $cleanupSuccess = false;
                }
            }
            return $cleanupSuccess;
        }

        private function moveItemsIntoPlace($extractDir, $fullExtractDir, $extractGroupRootItem) {
            $source = PathOperations::join($fullExtractDir, self::$archiveParentPath, $extractGroupRootItem);

            $destination = PathOperations::join($extractDir, $extractGroupRootItem);

            $destinationBackupPath = self::getBackupPath($extractDir, $extractGroupRootItem);

            if (@file_exists($destination) && !@rename($destination, $destinationBackupPath)) {
                throw new LocalizableException("Install setup failed moving '$destination' to '$destinationBackupPath'.",
                    LocalizableExceptionDefinition::$INSTALL_SETUP_RENAME_ERROR, array(
                        "source" => $destination, // lmao looks weird but is correct
                        "destination" => $destinationBackupPath
                    ));
            }

            if (!@rename($source, $destination)) {
                throw new LocalizableException("Install setup failed moving '$source to '$destination'.",
                    LocalizableExceptionDefinition::$INSTALL_SETUP_RENAME_ERROR, array(
                        "source" => $source,
                        "destination" => $destination
                    ));
            }

            PathOperations::recursiveDelete($fullExtractDir);
        }

        private function moveExtractGroupsIntoPlace($extractDir, $version, $extractGroups) {
            foreach ($extractGroups as $extractGroupRootItem => $extractFiles) {
                $fullExtractDir = self::buildItemExtractDirPath($extractDir, $version, $extractGroupRootItem);
                try {
                    $this->moveItemsIntoPlace($extractDir, $fullExtractDir, $extractGroupRootItem);
                } catch (Exception $e) {
                    $this->restoreBackups($extractDir, $extractGroups);
                    throw $e;
                }
            }

            if (!$this->cleanUpBackups($extractDir, $extractGroups))
                $this->setWarning("BACKUP_CLEANUP_ERROR", "Cleaning up of backups created during update failed.");
        }

        public function install($archivePath, $installDirectory) {
            list($archiveHandle, $updateManifest) = $this->getArchiveHandleAndUpdateManifest($archivePath);

            $newMonstaVersion = $this->extractVersionFromArchive($archivePath, $archiveHandle);

            $extractGroups = $this->buildExtractGroupsFromArchive($archiveHandle, $updateManifest);

            // for each item in the manifest, extract to directory with new version appended
            if (!$this->extractAllGroups($archiveHandle, $installDirectory, $newMonstaVersion, $extractGroups)) {
                /* cleanup as some of the files might have been extracted OK.
                * It shouldn't fail if the directories to remove aren't there
                */
                $this->cleanUpAfterExtract($installDirectory, $newMonstaVersion, $extractGroups);

                throw new LocalizableException("Extract of install archive failed.",
                    LocalizableExceptionDefinition::$INSTALL_ARCHIVE_EXTRACT_ERROR);
            }

            try {
                $this->moveExtractGroupsIntoPlace($installDirectory, $newMonstaVersion, $extractGroups);
            } catch (Exception $e) {
                $this->cleanUpAfterExtract($installDirectory, $newMonstaVersion, $extractGroups);
                throw $e;
            }
        }

        /**
         * @param $archiveHandle ZipArchive
         * @param $updateManifest array
         * @return mixed
         */
        private function buildExtractGroupsFromArchive($archiveHandle, $updateManifest) {
            $extractGroups = array();

            foreach (self::listArchive($archiveHandle) as $archiveFileName) {
                $manifestIndex = self::getManifestIndexForFileRoot($updateManifest, $archiveFileName);

                if ($manifestIndex === false) {
                    continue;
                }

                $originalManifestEntry = $updateManifest[$manifestIndex];

                if (!isset($extractGroups[$originalManifestEntry])) {
                    $extractGroups[$originalManifestEntry] = array();
                }

                $extractGroups[$originalManifestEntry][] = $archiveFileName;
            }
            return $extractGroups;
        }

        private function cleanUpAfterExtract($extractDir, $version, $extractGroups) {
            foreach (array_keys($extractGroups) as $extractGroupRootItem) {
                $fullExtractDir = self::buildItemExtractDirPath($extractDir, $version, $extractGroupRootItem);

                if (file_exists($fullExtractDir) && is_dir($fullExtractDir))
                    @rmdir($fullExtractDir);
            }
        }
    }