<?php

    require_once(dirname(__FILE__) . "/../constants.php");

    define("MFTP_SERVER_CAPABILITIES_CREATION_TIME_KEY", "CREATION_TIME");

    class ServerCapabilities {
        private $path;
        private $capabilityCache = null;

        public function __construct($path) {
            $this->path = $path;
        }

        public function getServerCapabilities($protocol, $host, $port) {
            $key = $this->generateServerCacheKey($protocol, $host, $port);

            $cache = $this->getCapabilityCache();

            return array_key_exists($key, $cache) ? $cache[$key] : null;
        }

        public function setServerCapabilities($protocol, $host, $port, $capabilities) {
            $key = $this->generateServerCacheKey($protocol, $host, $port);

            $cache = $this->getCapabilityCache();

            $capabilities[MFTP_SERVER_CAPABILITIES_CREATION_TIME_KEY] = time();

            $cache[$key] = $capabilities;

            $this->setCapabilityCache($cache);

            $this->storeCapabilities();
        }

        private function getCapabilityCache() {
            if (is_null($this->capabilityCache)) {
                $this->capabilityCache = $this->readCapabilityCache();
            }

            return $this->capabilityCache;
        }

        private function setCapabilityCache($capabilityCache) {
            $this->capabilityCache = $capabilityCache;
        }

        private function readCapabilityCache() {
            $path = $this->getPath();
            if (!@file_exists($path) || !@is_readable($path)) {
                return array();
            }

            $cacheContents = @file_get_contents($path);

            if ($cacheContents === FALSE) {
                return array();
            }

            $data = @json_decode($cacheContents, true);

            return ($data === FALSE || is_null($data)) ? array() : $data;
        }

        private function writeCapabilityCache($capabilityCache) {
            $cachePath = $this->getPath();
            $cacheDir = dirname($cachePath);

            if ((@file_exists($cachePath) && @is_writable($cachePath)) || @is_writable($cacheDir)) {
                $encodedData = json_encode($capabilityCache);
                @file_put_contents($cachePath, $encodedData);
            }
        }

        private function storeCapabilities() {
            if (is_null($this->capabilityCache) || (is_array($this->capabilityCache) && count($this->capabilityCache) == 0)) {
                return; // nothing to write
            }

            $lockPath = $this->getLockFilePath();

            $lockHandle = @fopen($lockPath, "w+");

            if ($lockHandle !== FALSE) {
                if (@flock($lockHandle, LOCK_EX)) {
                    $storedCache = $this->readCapabilityCache();

                    foreach ($this->capabilityCache as $serverKey => $serverCapabilities) {
                        $storedCache[$serverKey] = $serverCapabilities;
                    }

                    $expireBefore = time() - MFTP_CAPABILITY_CACHE_TIMEOUT_SECONDS;

                    $newToStore = array();

                    foreach ($storedCache as $serverKey => $serverCapabilities) {
                        if (array_key_exists(MFTP_SERVER_CAPABILITIES_CREATION_TIME_KEY, $serverCapabilities)
                            && $serverCapabilities[MFTP_SERVER_CAPABILITIES_CREATION_TIME_KEY] >= $expireBefore) {
                            $newToStore[$serverKey] = $serverCapabilities;
                        }
                    }

                    if (count($newToStore) != 0) {
                        $this->writeCapabilityCache($newToStore);
                    }

                    $this->capabilityCache = $newToStore;

                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    @unlink($lockPath);
                }
            }
        }

        private function getPath() {
            return $this->path;
        }

        private function getLockFilePath() {
            return $this->getPath() . ".lck";
        }

        private function generateServerCacheKey($protocol, $host, $port) {
            return strtolower("$protocol://$host:$port");
        }
    }