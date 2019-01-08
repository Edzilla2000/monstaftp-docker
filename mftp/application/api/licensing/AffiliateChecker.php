<?php

    class AffiliateChecker {
        private $affiliateCheckUrl = "https://www.monstaftp.com/_callbacks/affiliate-tracker.php";
        private $affiliateRecordTimeoutSeconds = 10;

        public function recordAffiliateSource($affiliateId, $licenseEmail, $installUrl) {
            $urlWithQS = $this->affiliateCheckUrl . "?" . $this->buildQueryString($affiliateId, $licenseEmail,
                    $installUrl);

            $contextOptions  = array (
                "http" => array (
                    "method" => "GET",
                    "header"=> "User-agent: Monsta FTP " . MONSTA_VERSION . "\r\n"
                )
            );

            $streamContext = stream_context_create($contextOptions);

            $handle = @fopen($urlWithQS, "r", false, $streamContext);

            if ($handle === false) {
                throw new Exception("Unable to connect to license server for verification");
            }

            $affiliateResult = @fread($handle, 10);
            @fclose($handle);

            return trim($affiliateResult) === "true";
        }

        private function buildQueryString($affiliateId, $licenseEmail, $installUrl) {
            return http_build_query(
                array(
                    "a" => $affiliateId,
                    "e" => $licenseEmail,
                    "u" => $installUrl
                )
            );
        }
    }