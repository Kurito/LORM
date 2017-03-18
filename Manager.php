<?php
    namespace Beeltec\ORM;

    /**
     * This class handles the database access
     * 
     * @author Christian Beelte <christian.beelte@itmo1701.de>
     * @since 1.0.0 (11.03.2017)
     * @version 1.0.2 (16.03.2017)
     */
    class Manager extends \mysqli
    {
        /**
         * @var \Beeltec\ORM\Entity[] $entities
         */
        private $entities;

        /**
         * The constructor extends the \mysqli constructor
         * 
         * @param string $host The database hostname
         * @param string $user The database username
         * @param string $pass The password of the user
         * @param string $db The name of the database
         */
        public function __construct(string $host, string $user, string $pass, string $db)
        {
            // Pass arguments to \mysqli constructor
            parent::__construct($host, $user, $pass, $db);

            // Check for errors during connection
            if (mysqli_connect_error())
                die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());

            // Initialize managed object list
            $this->entities = array();
        }

        /**
         * This function adds a 'delete'-flag to the given object and persist it
         * 
         * @since 1.0.2 (16.03.2017)
         * @param \Beeltec\ORM\Entity $entity
         * @return void
         */
        public function remove(\Beeltec\ORM\Entity $entity): void
        {
            $entity->setDelete(true);
            $this->persist($entity);
        }

        /**
         * This function adds the entitiy to the entity array, which is needed to save it to the database later on
         * 
         * @since 1.0.0
         * @version 1.0.2 (16.03.2017)
         * @param \Beeltec\ORM\Entity $entity
         * @return void
         */
        public function persist(\Beeltec\ORM\Entity $entity): void
        {
            // Check if the entity is already part of the managed object list
            $do_add = true;
            if (isset($this->entities)) 
            {
                foreach ($this->entities as $managed_entity)
                {
                    if ($managed_entity === $entity)
                        $do_add = false;
                }
            }

            // Add it to the managed object list if it was not part of it yet
            if ($do_add)
                $this->entities[] = $entity;
        }

        /**
         * Saves all entities in the entitiy-array to the database
         * 
         * @return void
         */
        public function flush(): void
        {
            foreach ($this->entities as $entity) {
                // Get the entity's real name
                $entity_name = self::getRealEntityName($entity);

                // Get all of the entity's fields
                $entity_fields = $entity->getFields();

                // Get primary key of the entity
                $entity_id = $entity->getId();

                // Check if entity already exists in database
                $num_rows = 0;
                $query = "SELECT `id` FROM `$entity_name` WHERE `id` = ?";
                if ($stmt = $this->prepare($query)) {
                    $stmt->bind_param("i", $entity_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $num_rows = $result->num_rows;
                    $stmt->close();
                }

                if ($num_rows > 0) 
                {
                    // Check if we want to delete or update the entity
                    if ($entity->getDelete() === true)
                    {
                        // Create the DELETE FROM query
                        $query = "DELETE FROM `$entity_name` WHERE `id` = ?";
                        if ($stmt = $this->prepare($query))
                        {
                            $stmt->bind_param("i", $entity_id);
                            $stmt->execute();
                            $stmt->close();
                            return;
                        }
                    }
                    else
                    {
                        // We create the UPDATE statement with wildcards for the preperation
                        $query = "UPDATE `$entity_name` SET ";
                        for ($i = 0; $i < sizeof($entity_fields); $i++) {
                            $query .= "`" . $entity_fields[$i] . "` = ?";
                            if ($i != sizeof($entity_fields)-1)
                                $query .= ", ";
                        }
                        $query .= " WHERE `id` = ?";
                    }
                } 
                else
                {
                    // Create a new row

                    // We create the INSERT statement with wildcards for the preperation
                    $query = "INSERT INTO `$entity_name` (";
                    for ($i = 0; $i < sizeof($entity_fields); $i++) {
                        $query .= "`" . $entity_fields[$i] . "`";
                        if ($i != sizeof($entity_fields)-1) {
                            $query .= ", ";
                        }
                    }
                    $query .= ") VALUES (";
                    for ($i = 0; $i < sizeof($entity_fields); $i++) {
                        $query .= "?";
                        if ($i != sizeof($entity_fields)-1)
                            $query .= ", ";
                    }
                    $query .= ")";
                }

                // holds the type string
                $param_type_string = "";

                // holds all the entity's field values
                $param_values = null;

                // holds references to the bind params
                $param_references = null;

                // iterate through all of the entity's fields
                for ($i = 0; $i < sizeof($entity_fields); $i++) {

                    // create a variable for all getter methods
                    $methodName = "get" . ucwords($entity_fields[$i]);

                    // get the return value of all getter methods
                    $methodValue = $entity->$methodName();

                    // determine the return value's type
                    $fieldType = gettype($methodValue);

                    // create a bind type string based on the return value's type
                    switch ($fieldType) {
                        case "integer":
                            $param_type_string .= "i";
                        break;

                        case "string":
                            $param_type_string .= "s";
                        break;

                        default:
                        break;
                    }

                    // aggregate all field values in a new array
                    $param_values[] = $methodValue;
                }

                // We need to append "i" to the bind_param_string and the primary key to the values if we update the row
                if ($num_rows > 0) {
                    $param_type_string .= "i";
                    $param_values[] = $entity->getId();
                }

                // first argument for mysqli_stmt->bind_param() is the type string
                $param_references[] = $param_type_string;

                /**
                 * mysqli_stmt->bind_param() only accepts references, so we need
                 * to iterate through the value array and reference all values
                 */
                for ($i = 0, $j = 1; $i < sizeof($param_values); $i++, $j++)
                    $param_references[$j] = &$param_values[$i];

                // now we prepare the statement, bind all values and finally execute it
                if ($stmt = $this->prepare($query)) {
                    call_user_func_array([$stmt, "bind_param"], $param_references);
                    $stmt->execute();
                    $stmt->close();
                }
                else
                    die("Error: " . $this->error . "( " . $this->errno . ")");
            }
        }

        /**
         * Fetches an entity with a specified entity name and an id as a primary key from the database
         * 
         * @since 1.0.1 (12.03.2017)
         * @version 1.0.1 (12.03.2017)
         * @param string $entity_name
         * @param int $id
         * @return \Beeltec\ORM\Entity
         */
        public function getEntity(string $entity_name, int $id): ?\Beeltec\ORM\Entity
        {
            // The entity name is always in lowercase
            $entity_name = strtolower($entity_name);

            // The class name needs the namespace prefix to work and has to be in CamelCase
            $class_name = "\\Beeltec\\ORM\\Entity\\" . ucwords($entity_name);

            // Create a new object of the corresponsing class
            $entity = new $class_name();

            // Get all fields of the entity
            $entity_fields = $entity->getFields();

            // Build the query based on the fields of the entity
            $query = "SELECT `id`, ";
            for ($i = 0; $i < sizeof($entity_fields); $i++) {
                $query .= "`" . $entity_fields[$i] . "`";
                if ($i < sizeof($entity_fields)-1)
                    $query .= ", ";
            }
            $query .= " FROM `$entity_name` WHERE `id` = ?";

            // Prepare the statement
            if ($stmt = $this->prepare($query)) {

                // We just need the primary key as a parameter
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // We need one array that holds the values of mysqli_stmt->bind_result
                $result_values = array();

                // And one that references those values since mysqli_stmt->bind_result cannot write directly into the value array
                $result_references = array();

                // The value fields don't need to be initialized and can be referenced implicitly
                // Also we need to create the id-field seperately, since it is not part of the fields array
                $result_references[] = &$result_values["id"];
                for ($i = 0; $i < sizeof($entity_fields); $i++)
                    $result_references[] = &$result_values[$entity_fields[$i]];

                // Now we call mysqli_stmt->bind_result with our reference array
                call_user_func_array([$stmt, "bind_result"], $result_references);

                // The value array now gets populated
                $stmt->fetch();

                // Populate the entity with values from the value array
                $entity->setId($result_values["id"]);
                for ($i = 0; $i < sizeof($entity_fields); $i++) {
                    // Build the setter method names
                    $method_name = "set" . ucwords($entity_fields[$i]);

                    // Set the value
                    $entity->$method_name($result_values[$entity_fields[$i]]);
                }
                
                // Close the statement
                $stmt->close();
            }

            // Return the now instanciated and populated entity
            return $entity;
        }

        /**
         * Returns zero or more entities
         * 
         * @param string $entity_name The class name of the entity
         * @param \Beeltec\ORM\Predicate[] $predicates The predicates for the query
         * @param int $offset
         * @param int $limit
         * @return \Beeltec\ORM\Entity[]
         */
        public function getEntities(string $entity_name, array $predicates, ?int $offset = 0, ?int $limit = 10)
        {
            // Create the array we want to return
            $entity_array = array();

            // Initialize necessary variables
            $offset = $offset ?? 0;
            $limit = $limit ?? 10;

            // The entity name is always in lowercase
            $entity_name = strtolower($entity_name);

            // The class name needs the namespace prefix to work and has to be in CamelCase
            $class_name = "\\Beeltec\\ORM\\Entity\\" . ucwords($entity_name);

            // Create a new object of the corresponsing class
            $entity = new $class_name();

            // Get all fields of the entity
            $entity_fields = $entity->getFields();

            // Build the query with wildcards for predicates
            $query = "SELECT `id`, ";
            for ($i = 0; $i < sizeof($entity_fields); $i++) {
                $query .= "`" . $entity_fields[$i] . "`";
                if ($i < sizeof($entity_fields)-1)
                    $query .= ", ";
            }
            $query .= " FROM `$entity_name` WHERE ";

            // Use sizeof() instead of count() since we do not pre-calculate the amount of elements in the array
            for ($i = 0; $i < sizeof($predicates); $i++) {
                $query .= "`" . $predicates[$i]->getKey() . "` " . $predicates[$i]->getComparison() . " ?";
                if ($i < sizeof($predicates)-1)
                    $query .= " " . $predicates[$i+1]->getLogic() ." ";
            }

            // Set offset and limit wildcards
            $query .= " LIMIT ?, ?";

            // Create the prepared statement
            if ($stmt = $this->prepare($query)) {

                // Create empty string for the bind_param type string
                $param_type_string = "";

                // Create array for the bind_param values
                $param_values = array();

                // Create array that references the bind_param values
                $param_references = array();

                // Create array for the bind_result values;
                $result_values = array();

                // Create array for the bind_result references;
                $result_references = array();

                // Iterate through all predicate values to assess their data types and assign them to the value array
                for ($i = 0; $i < sizeof($predicates); $i++) {

                    // Get the value of the predicate
                    $value = $predicates[$i]->getValue();

                    // Get the type of the value
                    $value_type = gettype($value);

                    // Append the corresponding letter to the type string
                    switch ($value_type) {
                        case "integer":
                            $param_type_string .= "i";
                        break;

                        case "string":
                            $param_type_string .= "s";
                        break;

                        default:
                        break;
                    }

                    // Add value to the value array
                    $param_values[$i] = $value;
                }

                // Add string types for offset and limit
                $param_type_string .= "ii";

                // Add values for offset and value
                $param_values[] = $offset;
                $param_values[] = $limit;

                // First entry in param reference array needs to be the type string
                $param_references[] = $param_type_string;

                // Iterate through the value array and add corresponding references to the reference array
                for ($i = 0; $i < sizeof($param_values); $i++)
                    $param_references[] = &$param_values[$i];


                // Now we call mysqli_stmt->bind_param with our reference array
                call_user_func_array([$stmt, "bind_param"], $param_references);

                // Execute the statement
                $stmt->execute();

                // Set the primary key reference for mysqli_stmt->bind_result
                $result_references[] = &$result_values["id"];

                // Iterate through all fields to create the remaining result references
                for ($i = 0; $i < sizeof($entity_fields); $i++)
                    $result_references[] = &$result_values[$entity_fields[$i]];

                // Now we call mysqli_stmt->bind_result with our reference array
                call_user_func_array([$stmt, "bind_result"], $result_references);

                // We might get multiple rows, so we need to call fetch in a while loop
                while ($stmt->fetch()) {

                    // Create a new instance of our entity
                    $entity = new $class_name();

                    // Populate the entity with values from the value array
                    $entity->setId($result_values["id"]);
                    for ($i = 0; $i < sizeof($entity_fields); $i++) {
                        // Build the setter method names
                        $method_name = "set" . ucwords($entity_fields[$i]);

                        // Set the value
                        $entity->$method_name($result_values[$entity_fields[$i]]);
                    }
                    
                    // Add the entity to our return array
                    $entity_array[] = $entity;
                }

                // Close the statement
                $stmt->close();

            }

            // Return all entities
            return $entity_array;
        }

        /**
         * Returns entity name and cuts off any namespace prefixes of the corresponding class
         * 
         * @param \Beeltec\ORM\Entity $entity
         * @return string
         */
        private static function getRealEntityName(\Beeltec\ORM\Entity $entity): string
        {
            $entity_name = "";
            $entity_name_parts = explode('\\', get_class($entity));
            if (sizeof($entity_name_parts) > 0)
                $entity_name = end($entity_name_parts);
            else
                $entity_name = get_class($entity);

            return strtolower($entity_name);
        }
    }
?>