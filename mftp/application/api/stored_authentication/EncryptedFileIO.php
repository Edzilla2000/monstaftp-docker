<?php
    require_once(dirname(__FILE__) . '/CipherSuite.php');
    require_once(dirname(__FILE__) . '/EncryptionSuite.php');
    require_once(dirname(__FILE__) . '/../lib/LocalizableException.php');

    class EncryptedFileIO {
        private $encryptionSuite;

        public function __construct() {
            $cipherSuite = new CipherSuite();
            $this->encryptionSuite = new EncryptionSuite($cipherSuite);
        }

        public function writeEncryptedData($path, $message, $key) {
            $encryptedPayload = $this->encryptionSuite->encryptWithBestCipherMethod($message, $key);
            if(@file_put_contents($path, $encryptedPayload) === false){
                $pathDirectory = dirname($path);

                if(!file_exists($path) && !is_writable($pathDirectory)) {
                    throw new LocalizableException("Could write to data file in $pathDirectory as it does not appear to 
                    be writable.", LocalizableExceptionDefinition::$FILE_NOT_WRITABLE_ERROR,
                        array("path" => $pathDirectory));
                }

                throw new LocalizableException("Could write to data file $path as it does not appear to be 
                        writable.", LocalizableExceptionDefinition::$FILE_NOT_WRITABLE_ERROR, array("path" => $path));
            }
        }

        public function readEncryptedData($path, $key) {
            $encryptedPayload = file_get_contents($path, $key);
            return $this->encryptionSuite->decryptWithInlineCipherMethod($encryptedPayload, $key);
        }
    }