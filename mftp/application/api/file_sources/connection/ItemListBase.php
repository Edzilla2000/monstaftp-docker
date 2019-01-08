<?php
    require_once(dirname(__FILE__) . '/../../lib/JsonSerializable.php');

    abstract class ItemListBase implements ArrayAccess, Iterator, JsonSerializable {
        /**
         * @var array
         */
        protected $itemList;

        public function getEntryCount() {
            return count($this->itemList);
        }

        // ArrayAccess Implementation Start

        public function offsetSet($offset, $value) {
            // not sure if BadMethodCallException is appropriate but seems to be most fitting built in exception
            throw new BadMethodCallException("Setting list items is not supported.");
        }

        public function offsetExists($offset) {
            return isset($this->itemList[$offset]);
        }

        public function offsetUnset($offset) {
            throw new BadMethodCallException("Unsetting list items is not supported.");
        }

        public function offsetGet($offset) {
            if ($offset > count($this->itemList) + 1)
                throw new OutOfRangeException(sprintf("Index %d is out of bounds.", $offset));

            return $this->itemList[$offset];
        }

        // ArrayAccess Implementation End

        // Iterator Implementation Start

        public function rewind() {
            reset($this->itemList);
        }

        public function current() {
            return current($this->itemList);
        }

        public function key() {
            return key($this->itemList);
        }

        public function next() {
            return next($this->itemList);
        }

        public function valid() {
            $key = $this->key();
            return ($key !== NULL && $key !== FALSE);
        }

        // Iterator Implementation End

        public function jsonSerialize() {
            return $this->itemList;
        }

        public function legacyJsonSerialize() {
            $itemListArray = array();

            foreach ($this as $item)
                $itemListArray[] = method_exists($item, 'legacyJsonSerialize') ? $item->legacyJsonSerialize() : $item;

            return $itemListArray;
        }
    }