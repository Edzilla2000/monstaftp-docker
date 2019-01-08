<?php

    require_once(dirname(__FILE__) . "/ConnectionBase.php");

    abstract class SFTPConnectionBase extends ConnectionBase {
        protected $protocolName = 'SFTP';

        protected function handleAuthentication() {
            if ($this->configuration->isAuthenticationModePassword())
                return $this->authenticateByPassword();
            else if ($this->configuration->isAuthenticationModePublicKeyFile())
                return $this->authenticateByPublicKey();
            else if ($this->configuration->isAuthenticationModeAgent())
                return $this->authenticateByAgent();
            else
                throw new Exception(sprintf("Unknown %s authentication type.", $this->protocolName));
        }

        abstract protected function statRemoteFile($remotePath);

        abstract protected function authenticateByPassword();

        abstract protected function authenticateByPublicKey();

        abstract protected function authenticateByAgent();

        protected function handleCopy($source, $destination) {
            /* SFTP does not provide built in copy functionality, so we copy file down to local and re-upload */
            $tempPath = monstaTempnam(getMonstaSharedTransferDirectory(), 'sftp-temp');
            try {
                $this->downloadFile(new SFTPTransferOperation($tempPath, $source));
                $sourceStat = $this->statRemoteFile($source);
                $this->uploadFile(new SFTPTransferOperation($tempPath, $destination,
                    $sourceStat['mode'] & PERMISSION_BIT_MASK));
            } catch (Exception $e) {
                @unlink($tempPath);
                throw $e;
            }

            // this should be done in a finally to avoid repeated code but we need to support PHP < 5.5
            @unlink($tempPath);
        }

        protected function handleGetFileInfo($remotePath) {
            $fileName = monstaBasename($remotePath);

            $stat = $this->statRemoteFile($remotePath);
            return new PHPSeclibListItem($fileName, $stat);
        }
    }