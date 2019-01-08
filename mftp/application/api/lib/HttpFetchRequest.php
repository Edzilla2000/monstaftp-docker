<?php
    require_once(dirname(__FILE__) . "/../file_sources/PathOperations.php");

    class HttpFetchRequest {
        /**
         * @var string
         */
        private $url;

        /**
         * @var null string
         */
        private $fileNameFromHeader = null;


        /**
         * HttpRemoteUploadFetchRequest constructor.
         * @param $url string
         * @param $destinationDirectory string
         */
        public function __construct($url) {
            $this->url = $url;
        }

        public function getFileName($effectiveUrl) {
            return $this->fileNameFromHeader != null ? $this->fileNameFromHeader :
                $this->getFileNameFromURL($effectiveUrl);
        }

        private function getFileNameFromURL($effectiveUrl) {
            return monstaBasename($effectiveUrl);
        }

        public function getURL() {
            return $this->url;
        }

        private function parseContentDispositionHeader($headerContents) {
            $fileNameIdentifier = "filename=";
            $fileNamePosition = strpos($headerContents, "filename=");

            if ($fileNamePosition !== false) {
                $headerFilename = substr($headerContents, $fileNamePosition + strlen($fileNameIdentifier));

                if (substr($headerFilename, 0, 1) == '"' && substr($headerFilename, -1) == '"')
                    $headerFilename = substr($headerFilename, 1, strlen($headerFilename) - 2);

                $this->fileNameFromHeader = $headerFilename;
            }
        }

        public function handleCurlHeader($curlHandle, $headerLine) {
            $splitHeaderLine = explode(":", $headerLine, 2);
            if (strtolower($splitHeaderLine[0]) == "content-disposition")
                $this->parseContentDispositionHeader(trim($splitHeaderLine[1]));

            return strlen($headerLine);
        }
    }