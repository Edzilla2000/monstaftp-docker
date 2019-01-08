<?php

    require_once(dirname(__FILE__) . '/MonstaLicenseV1.php');

    class MonstaLicenseV2 extends MonstaLicenseV1 {
        // the V2 refers to the license version, not the application version

        /**
         * @var boolean
         */
        protected $trial;

        public function __construct($email, $purchaseDate, $expiryDate, $version, $isTrial) {
           parent::__construct($email, $purchaseDate, $expiryDate, $version);
            $this->trial = $isTrial;
        }

        public function getLicenseVersion() {
            return 2;
        }

        /**
         * @return boolean
         */
        public function isTrial() {
            return $this->trial;
        }

        public function toArray() {
            $arr = parent::toArray();
            $arr['isTrial'] = $this->isTrial();
            return $arr;
        }
    }