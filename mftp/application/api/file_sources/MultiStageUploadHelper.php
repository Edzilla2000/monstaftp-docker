<?php
    require_once(dirname(__FILE__) . "/../lib/helpers.php");

    class MultiStageUploadHelper {
        public static function storeUploadContext($connectionType, $actionName, $configuration, $localPath, $remotePath) {
            $context = array(
                "connectionType" => $connectionType,
                "actionName" => $actionName,
                "configuration" => $configuration,
                "remotePath" => $remotePath,
                "localPath" => $localPath
            );

            $sessionKey = generateRandomString(16);

            $_SESSION[MFTP_SESSION_KEY_PREFIX . $sessionKey] = $context;

            return $sessionKey;
        }

        public static function getUploadContext($sessionKey) {
            if (!array_key_exists(MFTP_SESSION_KEY_PREFIX . $sessionKey, $_SESSION))
                throw new Exception("sessionKey '$sessionKey' not found in session");

            return $_SESSION[MFTP_SESSION_KEY_PREFIX . $sessionKey];
        }

        public static function getUploadRequest($sessionKey) {
            $uploadContext = self::getUploadContext($sessionKey);

            if (!is_array($uploadContext))
                throw new Exception("Upload Context is not an array");

            $request = array(
                "connectionType" => $uploadContext["connectionType"],
                "configuration" => $uploadContext["configuration"],
                "actionName" => $uploadContext["actionName"],
                "context" => array(
                    "localPath" => $uploadContext["localPath"],
                    "remotePath" => $uploadContext["remotePath"]
                )
            );

            return $request;
        }
    }