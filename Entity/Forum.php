<?php
    namespace Beeltec\ORM\Entity;

    /**
     * This file contains the ORM-class for the 'forum' database table
     */

    /**
     * This class maps the 'forum' database table
     * 
     * @author Christian Beelte <christian.beelte@itmo1701.de>
     * @since 1.0.0
     */
    class Forum extends \Beeltec\ORM\Entity
    {
        /**
         * @var integer The unique primary key
         */
        private $id;

        /**
         * @var string The name of the forum
         */
        private $name;

        /**
         * @var string|null The description of the forum
         */
        private $description;

        /**
         * Sets the $id property
         * 
         * @param integer $id The unique primary key
         * @return void
         */
        public function setId(int $id)
        {
            $this->id = $id;
        }

        /**
         * Gets the $id property
         * 
         * @return integer
         */
        public function getId(): ?int
        {
            return $this->id;
        }

        /**
         * Sets the $name property
         * 
         * @param string $name The name of the forum
         * @return void
         */
        public function setName(string $name)
        {
            $this->name = $name;
        }

        /**
         * Gets the $name property
         * 
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Sets the $description property
         * 
         * @param string $description The description of the forum
         * @return void
         */
        public function setDescription(string $description)
        {
            $this->description = $description;
        }

        /**
         * Gets the $description property
         * 
         * @return string
         */
        public function getDescription(): string
        {
            return $this->description;
        }

        /**
         * Returns all fields
         * 
         * @return mixed[]
         */
        public function getFields()
        {
            //$fields = get_class_vars(get_called_class());
            $real_fields = ["name", "description"];

            return $real_fields;
        }
    }
?>