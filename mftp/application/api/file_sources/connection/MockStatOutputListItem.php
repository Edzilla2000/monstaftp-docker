<?php
    require_once(dirname(__FILE__) . '/StatOutputListItem.php');


    class MockStatOutputListItem extends StatOutputListItem {
        public function __construct($name, $fileStat) {
            parent::__construct($name, $fileStat);
            if ($name == 'src')
                $this->link = true;
        }

    }