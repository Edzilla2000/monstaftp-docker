<?php

    /**
     * Class MonstaInstaller
     */
    class MonstaInstaller {

        /**
         * MonstaInstaller constructor.
         * @param $archivePath string
         * @param $installDirectory string
         * @param $installContext MonstaInstallContext
         */

        private $archivePath;
        private $installDirectory;
        private $installContext;

        public function __construct($archivePath, $installDirectory, $installContext) {
            $this->archivePath = $archivePath;
            $this->installDirectory = $installDirectory;
            $this->installContext = $installContext;
            $this->validateInstallUsingContext();
        }

        private function validateInstallUsingContext() {
            $this->installContext->validateInstallDirectory($this->installDirectory);
        }

        public function install() {
            $this->installContext->install($this->archivePath, $this->installDirectory);
        }

        public function getWarningExists() {
            return $this->installContext->getWarningExists();
        }

        public function getWarning() {
            return $this->installContext->getWarning();
        }
    }