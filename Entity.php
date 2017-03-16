<?php
    namespace Beeltec\ORM;

    class Entity
    {
        /**
         * Determines if the entity is going to be deleted on next flush
         * 
         * @var bool $delete
         */
        private $delete = false;

        /**
         * Getter for $delete field
         * 
         * @return bool $delete
         */
        public function getDelete(): bool
        {
            return $this->delete;
        }

        /**
         * Setter for $delete field
         * 
         * @param bool $delete
         */
        public function setDelete(bool $delete) 
        {
            $this->delete = $delete;
        }
        
        /**
         * Prototype method for returning all database columns as fields
         * 
         * @return mixed[] $fields
         */
        public function getFields()
        {

        }
    }
?>