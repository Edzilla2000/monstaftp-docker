<?php

    require_once(dirname(__FILE__) . "/EncryptedFileIO.php");
    require_once(dirname(__FILE__) . "/Exceptions.php");

    class AuthenticationStorage {
        public static function readEncryptedData($path, $password) {
            $fileIO = new EncryptedFileIO();
            return $fileIO->readEncryptedData($path, $password);
        }

        public static function decodeEncryptedDataAtPath($path, $password) {
            return json_decode(self::readEncryptedData($path,$password), true);
        }

        public static function loadConfiguration($path, $password) {
            $decodedData = self::decodeEncryptedDataAtPath($path, $password);

            if ($decodedData === null)
                throw new AuthenticationFileReadException("Could not load valid data from storage at $path",
                    LocalizableExceptionDefinition::$COULD_NOT_LOAD_PROFILE_DATA_ERROR, array('path' => $path));

            return $decodedData;
        }

        public static function saveConfiguration($path, $password, $data) {
            if (self::configurationExists($path)) {
                try {
                    self::loadConfiguration($path, $password);
                } catch (AuthenticationFileReadException $e) {
                    throw new AuthenticationFileWriteException("File exists at $path but it is not readable with the 
                    given password.", LocalizableExceptionDefinition::$PROFILE_NOT_READABLE_ERROR,
                        array('path' => $path));
                }
            }

            $encodedData = json_encode($data);

            $fileIO = new EncryptedFileIO();
            $fileIO->writeEncryptedData($path, $encodedData, $password);
        }

        public static function configurationExists($path) {
            if (!file_exists($path))
                return false;

            $fileSize = filesize($path);

            if ($fileSize === FALSE)
                throw new AuthenticationFileReadException("File exists at $path but couldn't get its size.",
                    LocalizableExceptionDefinition::$PROFILE_SIZE_READ_ERROR, array('path' => $path));

            return $fileSize != 0;
        }

        public static function validateAuthenticationPassword($path, $password) {
            return self::decodeEncryptedDataAtPath($path, $password) !== null;
        }
    }