<?php
    require_once(dirname(__FILE__) . "/../constants.php");
    require_once(dirname(__FILE__) . "/../lib/LocalizableException.php");
    require_once(dirname(__FILE__) . "/../lib/helpers.php");

    class HTTPFetcher {
        private $fetchRequest = null;
        private $tempSavePath = null;

        public function fetch($fetchRequest) {
            if ($this->fetchRequest != null)
                throw new LocalizableException("Can not fetch a request as one is already in progress.",
                    LocalizableExceptionDefinition::$FETCH_IN_PROGRESS_ERROR);

            $this->fetchRequest = $fetchRequest;
            $this->generateTempSavePath();
            return $this->performFetchRequest();
        }

        public function getTempSavePath() {
            return $this->tempSavePath;
        }

        private function performFetchRequest() {
            $fp = fopen($this->tempSavePath, 'w+');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->fetchRequest->getURL());
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(&$this->fetchRequest, 'handleCurlHeader'));
            $success = @curl_exec($ch);
            $curlError = @curl_error($ch);
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);
            fclose($fp);

            if ($httpCode == 404) {
                throw new LocalizableException('Remote file not found.',
                    LocalizableExceptionDefinition::$REMOTE_FILE_NOT_FOUND,
                    array('url' => $this->fetchRequest->getURL()));
            }

            if ($success === false) {
                throw new LocalizableException("File fetch failed: " . $curlError,
                    LocalizableExceptionDefinition::$FETCH_FAILED_ERROR,
                    array('cause' => $curlError, 'url' => $this->fetchRequest->getURL()));
            }

            return $effectiveUrl;
        }

        private function generateTempSavePath() {
            $this->tempSavePath = monstaTempnam(getMonstaSharedTransferDirectory(), 'http_fetch');
        }

        public function cleanUp() {
            @unlink($this->tempSavePath);
            $this->fetchRequest = null;
        }
    }