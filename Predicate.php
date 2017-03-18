<?php
    namespace Beeltec\ORM;

    class Predicate
    {
        public const EQUALS   = "=";
        public const IS       = "IS";
        public const ISNOT    = "IS NOT";
        public const NEQUALS  = "<>";
        public const LIKE     = "LIKE";
        public const NLIKE    = "NOT LIKE";

        public const AND      = "AND";
        public const NAND     = "NOT AND";
        public const OR       = "OR";
        public const NOR      = "NOR";
        public const XOR      = "XOR";

        private $key;
        private $value;
        private $comparison;
        private $logic; 

        public function __construct(string $key, string $value, string $comparison = self::EQUALS, string $logic = self::AND)
        {
            $this->key = $key;
            $this->value = $value;
            $this->comparison = $comparison;
            $this->logic = $logic;
        }

        public function getKey(): string
        {
            return $this->key;
        }

        public function setKey(string $key)
        {
            $this->key = $key;
        }

        public function getValue(): string
        {
            return $this->value;
        }

        public function setValue(string $value)
        {
            $this->value = $value;
        }

        public function getComparison(): string
        {
            return $this->comparison;
        }

        public function setComparison(string $comparison)
        {
            $this->comparison = $comparison;
        }

        public function getLogic(): string
        {
            return $this->logic;
        }

        public function setLogic(string $logic)
        {
            $this->logic = $logic;
        }

        public static function create(string $key, string $value, string $comparison = self::EQUALS, string $logic = self::AND)
        {
            $predicate = new self($key, $value, $comparison, $logic);
            return $predicate;
        }
    }
?>