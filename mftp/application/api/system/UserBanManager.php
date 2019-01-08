<?php

    class UserBanManager {
        private $store;
        private $maxFailures;
        private $failureResetSeconds;

        public function __construct($maxFailures, $failureResetSeconds, $store) {
            $this->store = is_null($store) ? array() : $store;
            $this->maxFailures = $maxFailures;
            $this->failureResetSeconds = $failureResetSeconds;
        }

        public function recordHostAndUserLoginFailure($host, $user) {
            if ($this->maxFailures == 0 || $this->failureResetSeconds == 0)
                return;

            $userHostArray = &$this->getUserHostArray($host, $user);

            $this->recordLoginFailureInUserHostArray($userHostArray);
        }

        public function resetHostUserLoginFailure($host, $user) {
            if ($this->maxFailures == 0 || $this->failureResetSeconds == 0)
                return;

            if (!$this->hostAndUserRecordExists($host, $user))
                return;

            $hostArray = &$this->getHostArray($host);
            unset($hostArray, $user);
        }

        public function hostAndUserBanned($host, $user) {
            if ($this->maxFailures == 0 || $this->failureResetSeconds == 0) {
                return false;
            }

            if (!$this->hostAndUserRecordExists($host, $user)) {
                return false;
            }

            $userHostArray = $this->getUserHostArray($host, $user);

            if ($this->userHostArrayExceedsFailureSettings($userHostArray))
                return true;

            return false;
        }

        public function getStore() {
            return $this->store;
        }

        private function normaliseHost($host) {
            return trim(strtolower($host));
        }

        private function hostRecordExists($host) {
            return isset($this->store[$this->normaliseHost($host)]);
        }

        private function hostAndUserRecordExists($host, $user) {
            if (!$this->hostRecordExists($host)) {
                return false;
            }

            $hostArray = $this->getHostArray($host);

            return isset($hostArray[$user]);
        }

        private function &getHostArray($host) {
            $normalisedHost = $this->normaliseHost($host);
            if (!$this->hostRecordExists($host)) {
                $this->store[$normalisedHost] = array();
            }

            return $this->store[$normalisedHost];
        }

        private function &getUserHostArray($host, $username) {
            $hostArray = &$this->getHostArray($host);

            if (!$this->hostAndUserRecordExists($host, $username)) {
                $hostArray[$username] = array();
            }

            return $hostArray[$username];
        }

        private function recordLoginFailureInUserHostArray(&$userHostArray) {
            if (!isset($userHostArray["failureCount"]))
                $userHostArray["failureCount"] = 0;

            ++$userHostArray["failureCount"];

            $userHostArray["lastFailureTime"] = time();
        }

        private function userHostArrayExceedsFailureSettings($userHostArray) {
            if ($userHostArray["failureCount"] < $this->maxFailures)
                return false;

            if (time() - $userHostArray["lastFailureTime"] > $this->failureResetSeconds)
                return false;

            return true;
        }
    }