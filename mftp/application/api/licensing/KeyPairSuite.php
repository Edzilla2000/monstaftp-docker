<?php
    require_once(dirname(__FILE__) . '/../lib/file_operations.php');
    require_once(dirname(__FILE__) . '/Exceptions.php');

    class KeyPairSuite {
        private $publicKeyPath;
        private $privateKeyPath;
        private $privateKeyPassword;

        public function __construct($publicKeyPath = null, $privateKeyPath = null, $privateKeyPassword = null) {
            $this->publicKeyPath = $publicKeyPath;
            $this->privateKeyPath = $privateKeyPath;
            $this->privateKeyPassword = $privateKeyPassword;
        }

        public function rawEncrypt($message) {
            $keyData = "";

            try {
                $keyData = mftpFileGetContents($this->privateKeyPath);
            } catch (Exception $e) {
                $this->throwKeyLoadException($this->privateKeyPath, "private",
                    LocalizableExceptionDefinition::$PRIVATE_KEY_LOAD_ERROR, $e->getMessage());
            }

            $privateKey = openssl_get_privatekey($keyData, $this->privateKeyPassword);

            if ($privateKey === FALSE)
                $this->throwKeyLoadException($this->privateKeyPath, "private",
                    LocalizableExceptionDefinition::$PRIVATE_KEY_LOAD_ERROR);

            $encrypted = '';
            openssl_private_encrypt($message, $encrypted, $privateKey);
            return $encrypted;
        }

        public function rawDecrypt($encrypted) {
            $keyData = "";

            try {
                $keyData = mftpFileGetContents($this->publicKeyPath);
            } catch (Exception $e) {
                $this->throwKeyLoadException($this->publicKeyPath, "public",
                    LocalizableExceptionDefinition::$PUBLIC_KEY_LOAD_ERROR, $e->getMessage());
            }

            $publicKey = openssl_get_publickey($keyData);

            if($publicKey === FALSE)
                $this->throwKeyLoadException($this->publicKeyPath, "public",
                    LocalizableExceptionDefinition::$PUBLIC_KEY_LOAD_ERROR, null);

            $decrypted = '';
            if(!openssl_public_decrypt($encrypted, $decrypted, $publicKey))
                throw new KeyPairException("Unable to decrypt message", LocalizableExceptionDefinition::$DECRYPT_ERROR);

            return $decrypted;
        }

        public function encryptAndBase64Encode($message) {
            return base64_encode($this->rawEncrypt($message));
        }

        public function base64DecodeAndDecrypt($encoded) {
            return $this->rawDecrypt(base64_decode($encoded));
        }

        private function throwKeyLoadException($path, $publicOrPrivate, $errorCode, $originalExceptionMessage = null){
            $exceptionMessage = "Unable to load $publicOrPrivate key at path '$path'.";

            if(!is_null($originalExceptionMessage)) {
                $exceptionMessage .= "  Original exception was: '$originalExceptionMessage'";
            }

            throw new KeyPairException($exceptionMessage, $errorCode, array("path" => $this->$path));
        }
    }