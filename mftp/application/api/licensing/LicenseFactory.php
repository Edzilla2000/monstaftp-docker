<?php

    require_once(dirname(__FILE__) . '/MonstaLicenseV1.php');
    require_once(dirname(__FILE__) . '/MonstaLicenseV2.php');
    require_once(dirname(__FILE__) . '/MonstaLicenseV3.php');

    class LicenseFactory {
        public static function getMonstaLicenseV1($email, $purchaseDate, $expiryDate, $version) {
            return new MonstaLicenseV1($email, $purchaseDate, $expiryDate, $version);
        }

        public static function getMonstaLicenseV2($email, $purchaseDate, $expiryDate, $version, $isTrial) {
            return new MonstaLicenseV2($email, $purchaseDate, $expiryDate, $version, $isTrial);
        }

        public static function getMonstaLicenseV3($email, $purchaseDate, $expiryDate, $version, $isTrial,
                                                  $productEdition) {
            return new MonstaLicenseV3($email, $purchaseDate, $expiryDate, $version, $isTrial, $productEdition);
        }

        public static function getMonstaLicenseFromArray($licenseArr) {
            if(is_null($licenseArr))
                return null;

            $email = $licenseArr['email'];
            $purchaseDate = $licenseArr['purchaseDate'];
            $expiryDate = $licenseArr['expiryDate'];
            $version = $licenseArr['version'];

            if (!array_key_exists("isTrial", $licenseArr))
                return self::getMonstaLicenseV1($email, $purchaseDate, $expiryDate, $version);

            $isTrial = $licenseArr['isTrial'];

            if (array_key_exists("licenseVersion", $licenseArr)) {
                $licenseVersion = $licenseArr['licenseVersion'];
                if ($licenseVersion == 3)
                    return self::getMonstaLicenseV3($email, $purchaseDate, $expiryDate, $version, $isTrial,
                        $licenseArr['productEdition']);

                throw new Exception("Unknown license version " . $licenseVersion);
            }

            return self::getMonstaLicenseV2($email, $purchaseDate, $expiryDate, $version, $isTrial);
        }
    }