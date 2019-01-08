<?php
    require_once(dirname(__FILE__) . "/../lib/LocalizableException.php");
    require_once(dirname(__FILE__) . "/../file_sources/PathOperations.php");
    require_once(dirname(__FILE__) . "/MonstaInstallContext.php");

    class MonstaNewInstallContext extends MonstaInstallContext {
        private static function getInstallRootItems($archiveHandle) {
            $rootItems = array();

            $previousRootItem = "";

            foreach (self::listArchive($archiveHandle) as $archiveFileName) {
                $relativeArchivePath = self::getRelativeArchivePath($archiveFileName);

                $rootItem = PathOperations::getFirstPathComponent($relativeArchivePath);

                if ($rootItem != $previousRootItem) {
                    $rootItems[] = $rootItem;
                    $previousRootItem = $rootItem;
                }
            }

            return $rootItems;
        }

        private static function validateMonstaItemsNotInInstallDirectory($rootItems, $installDirectory) {
            foreach ($rootItems as $rootItem) {
                $itemPath = PathOperations::join($installDirectory, $rootItem);

                if (@file_exists($itemPath)) {
                    throw new LocalizableException("Could not install to $itemPath as the item exists",
                        LocalizableExceptionDefinition::$INSTALL_DIRECTORY_EXISTS_ERROR, array("path" => $itemPath));
                }
            }
        }

        private static function getTemporaryExtractDirectory($installDirectory) {
            $extractNum = 1;

            do {
                $extractDirectory = PathOperations::join($installDirectory, 'monsta-extract-temp-' . $extractNum++);
            } while (file_exists($extractDirectory));

            return $extractDirectory;
        }

        public function validateInstallDirectory($installDirectory) {
            if (@!is_writable($installDirectory)) {
                throw new LocalizableException("Could not install into $installDirectory as the directory is not writable",
                    LocalizableExceptionDefinition::$INSTALL_PATH_NOT_WRITABLE_ERROR, array("path" => $installDirectory));
            }
        }

        private static function cleanupFailedInstalledItems($installDirectory, $rootItems) {
            foreach ($rootItems as $rootItem) {
                $itemPath = PathOperations::join($rootItem, $rootItem);
                PathOperations::recursiveDelete($itemPath);
                // this might fail if the item wasn't moved/other reasons
                // since it's last resort failure cleanup just continue
            }
        }

        private static function moveRootItemsFromExtractToInstallDirectory($extractDirectory, $installDirectory,
                                                                           $rootItems) {
            foreach ($rootItems as $rootItem) {
                $source = PathOperations::join($extractDirectory, self::$archiveParentPath, $rootItem);
                $dest = PathOperations::join($installDirectory, $rootItem);
                if (!@rename($source, $dest)) {
                    throw new LocalizableException("Install setup failed moving '$source' to '$dest'.",
                        LocalizableExceptionDefinition::$INSTALL_SETUP_RENAME_ERROR, array(
                            "source" => $source,
                            "destination" => $dest
                        ));
                }
            }
        }

        public function install($archivePath, $installDirectory) {
            list($archiveHandle, $updateManifest) = $this->getArchiveHandleAndUpdateManifest($archivePath);

            $rootItems = self::getInstallRootItems($archiveHandle);

            self::validateMonstaItemsNotInInstallDirectory($rootItems, $installDirectory);

            $extractDirectory = self::getTemporaryExtractDirectory($installDirectory);

            if (!@$archiveHandle->extractTo($extractDirectory)) {
                throw new LocalizableException("Extract of install archive failed.",
                    LocalizableExceptionDefinition::$INSTALL_ARCHIVE_EXTRACT_ERROR);
            }

            try {
                self::moveRootItemsFromExtractToInstallDirectory($extractDirectory, $installDirectory, $rootItems);
            } catch (Exception $e) {
                self::cleanupFailedInstalledItems($installDirectory, $rootItems);
                PathOperations::recursiveDelete($extractDirectory); // this might fail too. we exception soon though
                throw $e;
            }

            self::makeUserDirectoriesWritable($installDirectory, array("license", "settings"));

            if (!PathOperations::recursiveDelete($extractDirectory)) {
                $this->setWarning("EXTRACT_CLEANUP_ERROR", "Cleaning up of extract directory created during install failed.");
            }
        }

        private static function addOwnerWritableToFileMode($mode) {
            return $mode | 0x0080;
        }

        private static function makeUserDirectoriesWritable($installDirectory, $userDirectories) {
            foreach ($userDirectories as $userDirectory) {
                $fullUserDirectoryPath = PathOperations::join($installDirectory, $userDirectory);
                $currentMode = @fileperms($fullUserDirectoryPath);

                if($currentMode === false) {
                    continue; // failure, no biggie, let just try the next one
                }

                $newMode = self::addOwnerWritableToFileMode($currentMode);
                if($newMode != $currentMode) {
                    @chmod($fullUserDirectoryPath, $newMode); // might fail, nothing we can do, move on
                }
            }
        }
    }