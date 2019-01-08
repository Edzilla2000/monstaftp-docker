<?php

    require_once(dirname(__FILE__) . '/../lib/JsonSerializable.php');

    abstract class MonstaLicense implements JsonSerializable {
        protected $expiryDate;

        public function isLicensed() {
            if(is_null($this->expiryDate))
                return false;

            return time() < $this->expiryDate;
        }
    }