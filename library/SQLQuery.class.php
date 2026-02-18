<?php

class SQLQuery {
    protected $_dbHandle;
    protected $_result;

    /** Connects to database **/

    function connect($address, $account, $pwd, $name) {
        $this->_dbHandle = new mysqli($address, $account, $pwd, $name);
        if ($this->_dbHandle->connect_error) {
            error_log('Connect Error (' . $this->_dbHandle->connect_errno . ') ' . $this->_dbHandle->connect_error);
            return 0;
        } else {
            error_log('Database connected successfully.'); // Added for debugging
            $this->_dbHandle->set_charset("utf8");
            return 1;
        }
    }

    /** Disconnects from database **/

    function disconnect() {
        if ($this->_dbHandle) {
            if ($this->_dbHandle->close()) {
                return 1;
            } else {
                return 0;
            }
        }
        return 0;
    }

    function selectAll() {
        $query = 'select * from `'.$this->_table.'`';
        $result = $this->query($query);
        return is_array($result) ? $result : [];
    }

    function select($id) {
        $query = 'select * from `' . $this->_table . '` where `id` = ?';
        $result = $this->query($query, [$id], true);
        return is_array($result) ? $result : [];
    }


    /** Custom SQL Query **/

    function query(string $query, array $params = [], bool $singleResult = false) {
        if (!$this->_dbHandle) {
            error_log("Database connection not established.");
            return false;
        }

        $stmt = $this->_dbHandle->prepare($query);
        if ($stmt === false) {
            error_log("MySQLi Prepare Error: " . $this->_dbHandle->error . " Query: " . $query);
            return false;
        }

        if (!empty($params)) {
            $types = $this->_getParamTypes($params);
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log("MySQLi Execute Error: " . $stmt->error . " Query: " . $query . " Params: " . implode(', ', $params));
            $stmt->close();
            return false;
        }

        if (preg_match("/^select/i", trim($query))) {
            $this->_result = $stmt->get_result(); // Store result for getNumRows etc.
            if ($this->_result === false) {
                error_log("MySQLi Get Result Error: " . $stmt->error . " Query: " . $query);
                $stmt->close();
                return false;
            }

            $result = array();
            $tempResults = array();

            if ($this->_result instanceof mysqli_result) {
                $numOfFields = $this->_result->field_count;
                $fields = $this->_result->fetch_fields();
                $fieldNames = [];
                $tableNames = [];
                foreach ($fields as $fieldInfo) {
                    $fieldNames[] = $fieldInfo->name;
                    $tableNames[] = trim(ucfirst($fieldInfo->table), "s");
                }

                while ($row = $this->_result->fetch_row()) {
                    $tempResults = array();
                    for ($i = 0; $i < $numOfFields; ++$i) {
                        $tempResults[$tableNames[$i]][$fieldNames[$i]] = $row[$i];
                    }
                    if ($singleResult) {
                        $stmt->close();
                        return $tempResults;
                    }
                    array_push($result, $tempResults);
                }
                //$this->_result->free(); // get_result returns a buffered result set, free is implicitly called when stmt is closed.
            }
            $stmt->close();
            return $result;
        } else {
            $stmt->close();
            return true; // For INSERT, UPDATE, DELETE
        }
    }

    /** Helper to get parameter types for bind_param **/
    private function _getParamTypes(array $params): string {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i'; // Integer
            } elseif (is_float($param)) {
                $types .= 'd'; // Double
            } elseif (is_string($param)) {
                $types .= 's'; // String
            } else {
                $types .= 'b'; // Blob (or any other type, treat as string for simplicity if unsure)
            }
        }
        return $types;
    }

    /** Get number of rows **/
    function getNumRows() {
        if ($this->_result instanceof mysqli_result) {
            return $this->_result->num_rows;
        }
        return 0; // Or throw an error, depending on desired behavior
    }

    /** Free resources allocated by a query **/

    function freeResult() {
        if ($this->_result instanceof mysqli_result) {
            $this->_result->free();
        }
    }

    /** Get error string **/

    function getError() {
        return $this->_dbHandle->error;
    }
}

