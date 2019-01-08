<?php
    require_once(dirname(__FILE__) . "/../lib/HttpFetchRequest.php");

    /**
     * Class HttpRemoteUploadFetchRequest
     */
    class HttpRemoteUploadFetchRequest extends HttpFetchRequest {
        /**
         * @var string
         */
        private $destinationDirectory;

        /**
         * HttpRemoteUploadFetchRequest constructor.
         * @param $url string
         * @param $destinationDirectory string
         */
        public function __construct($url, $destinationDirectory) {
            parent::__construct($url);
            $this->destinationDirectory = $destinationDirectory;
        }

        public function getUploadPath($effectiveUrl) {
            return PathOperations::join($this->destinationDirectory, $this->getFileName($effectiveUrl));
        }
    }