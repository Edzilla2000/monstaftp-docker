<?php
    require_once(dirname(__FILE__) . '/ItemListBase.php');
    require_once(dirname(__FILE__) . '/PHPSeclibListItem.php');

    class PHPSeclibListParser extends ItemListBase {
        public function __construct($rawList, $showHidden) {
            $this->itemList = array();

            foreach ($rawList as $fileName => $fileStat) {
                if($fileName == "." || $fileName == "..")
                    continue;

                if(!$showHidden && substr($fileName, 0, 1) == ".")
                    continue;

                $this->itemList[] = new PHPSeclibListItem($fileName, $fileStat);
            }
        }
    }