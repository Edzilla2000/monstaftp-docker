<?php

    require_once(dirname(__FILE__) . '/Exceptions.php');
    require_once(dirname(__FILE__) . '/../lib/file_operations.php');

    class LicenseReader {
        /**
         * @var KeyPairSuite
         */
        private $keyPairSuite;

        public function __construct($keyPairSuite) {
            $this->keyPairSuite = $keyPairSuite;
        }

        public function readLicense($licensePath) {
            if(!file_exists($licensePath))
                return null;

            $licenseContent = mftpFileGetContents($licensePath);
            return $this->readLicenseString($licensePath, $licenseContent);
        }

        public function extractEncodedDataFromLicense($licenseData) {
            $licenseLines = preg_split("/(=\s+|\r\n|\n|\r)/", $licenseData);
            $encodedData = "";

            foreach ($licenseLines as $line) {
                $line = trim($line);

                if ($line == "")
                    continue;

                if(strlen($line) >= 3 && substr($line, 0, 3) == "===" || substr($line, -3, 3) == "===")
                    continue;

                $encodedData .= $line;
            }

            return $encodedData;
        }

        /**
         * @param $licensePath
         * @param $licenseContent
         * @return mixed
         * @throws InvalidLicenseException
         */
        public function readLicenseString($licensePath, $licenseContent) {
            $encodedData = $this->extractEncodedDataFromLicense($licenseContent);

            try {
                $rawLicenseData = $this->keyPairSuite->base64DecodeAndDecrypt($encodedData);
            } catch (KeyPairException $e) {
                throw new InvalidLicenseException("Unable to read the license file at '$licensePath'.",
                    LocalizableExceptionDefinition::$LICENSE_READ_FAILED_ERROR, array('path' => $licensePath));
            }

            return json_decode($rawLicenseData, true);
        }
    }