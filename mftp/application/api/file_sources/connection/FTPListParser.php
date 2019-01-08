<?php

    require_once(dirname(__FILE__) . '/ItemListBase.php');
    require_once(dirname(__FILE__) . '/FTPListItem.php');
    require_once(dirname(__FILE__) . '/WindowsFTPListItem.php');

    class FTPListParser extends ItemListBase {
        public function __construct($rawList, $showHidden, $systemType = FTP_SYS_TYPE_UNIX) {
            $this->itemList = array();

            $parsedItemFormat = null;

            foreach ($rawList as $rawItem) {
                if(strlen($rawItem) >= 5 && substr($rawItem, 0, 5) == "total")
                    continue;

                if (is_null($parsedItemFormat))
                    $parsedItemFormat = $this->determineFTPListItemFormat($rawItem);

                // this is not an "else" for the if above, it needs to be done regardless
                $listFormat = is_null($parsedItemFormat) ? $systemType : $parsedItemFormat;

                if ($listFormat == FTP_SYS_TYPE_UNIX)
                    $listItem = new FTPListItem($rawItem);
                else if ($listFormat == FTP_SYS_TYPE_WINDOWS)
                    $listItem = new WindowsFTPListItem($rawItem);
                else
                    throw new InvalidArgumentException("Unknown List Format");

                if ($listItem->getName() == '..' || $listItem->getName() == '.')
                    continue;

                if(!$showHidden && substr($listItem->getName(), 0, 1) == '.')
                    continue;

                $this->itemList[] = $listItem;
            }
        }

        private function determineFTPListItemFormat($rawItem) {
            if ( preg_match(MFTP_UNIX_LIST_FORMAT, $rawItem) )
                return FTP_SYS_TYPE_UNIX;

            if ( preg_match(MFTP_WIN_LIST_FORMAT, $rawItem) )
                return FTP_SYS_TYPE_WINDOWS;

            return null;
        }
    }