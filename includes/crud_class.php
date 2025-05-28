<?php
class CRUD {
    protected $host = 'localhost';
    protected $username = 'root';
    protected $password = '';
    protected $database = 'inventory_system';
    protected $connect;

    function __construct() {
        $this->connect = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($this->connect->connect_error) {
            die("Connection failed: " . $this->connect->connect_error);
        }
        $this->connect->set_charset("utf8mb4");
    }

    // Secure parameter binding to prevent SQL injection
    private function bind_params($stmt, $params) {
        if (empty($params)) {
            return true;
        }
        
        $types = '';
        $values = [];
        
        foreach ($params as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        
        array_unshift($values, $types);
        return call_user_func_array([$stmt, 'bind_param'], $this->ref_values($values));
    }
    
    private function ref_values($array) {
        $refs = [];
        foreach ($array as $key => $value) {
            $refs[$key] = &$array[$key];
        }
        return $refs;
    }

    public function common_select($table, $fields = '*', $where = false, $sort = 'id', $sort_type = 'asc', $offset = false, $limit = false) {
        $data = [];
        $error = 0;
        $error_msg = "";
        $params = [];

        // Validate table name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return ['data' => [], 'error' => 1, 'error_msg' => "Invalid table name"];
        }

        $sql = "SELECT $fields FROM `$table`";

        if ($where && is_array($where)) {
            $sql .= " WHERE ";
            $i = 0;
            foreach ($where as $k => $v) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $k)) {
                    return ['data' => [], 'error' => 1, 'error_msg' => "Invalid column name in WHERE clause"];
                }
                $sql .= "`$k` = ?";
                $params[] = $v;
                if ($i < count($where) - 1) {
                    $sql .= " AND ";
                }
                $i++;
            }
        }

        if ($sort) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $sort)) {
                return ['data' => [], 'error' => 1, 'error_msg' => "Invalid sort column"];
            }
            $sql .= " ORDER BY `$sort` " . (strtoupper($sort_type) === 'DESC' ? 'DESC' : 'ASC');
        }

        if ($limit !== false) {
            $limit = (int)$limit;
            if ($limit <= 0) {
                return ['data' => [], 'error' => 1, 'error_msg' => "Invalid limit value"];
            }
            $sql .= " LIMIT ?";
            $params[] = $limit;
            
            if ($offset !== false) {
                $offset = (int)$offset;
                if ($offset < 0) {
                    return ['data' => [], 'error' => 1, 'error_msg' => "Invalid offset value"];
                }
                $sql .= " OFFSET ?";
                $params[] = $offset;
            }
        }

        $stmt = $this->connect->prepare($sql);
        if ($stmt) {
            if (!empty($params)) {
                if (!$this->bind_params($stmt, $params)) {
                    return ['data' => [], 'error' => 1, 'error_msg' => "Parameter binding failed"];
                }
            }
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    while ($r = $result->fetch_object()) {
                        $data[] = $r;
                    }
                }
                // No data is not considered an error - returns empty array
            } else {
                $error = 1;
                $error_msg = $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 1;
            $error_msg = $this->connect->error;
        }
        
        return ['data' => $data, 'error' => $error, 'error_msg' => $error_msg];
    }

    public function common_insert($table, $fields) {
        $data = '';
        $error = 0;
        $error_msg = "";
        
        if (!is_array($fields) || empty($fields)) {
            return ['data' => '', 'error' => 1, 'error_msg' => "No fields provided for insert"];
        }

        // Validate table name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return ['data' => '', 'error' => 1, 'error_msg' => "Invalid table name"];
        }

        $columns = [];
        $placeholders = [];
        $params = [];
        
        foreach ($fields as $k => $v) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $k)) {
                return ['data' => '', 'error' => 1, 'error_msg' => "Invalid column name"];
            }
            $columns[] = "`$k`";
            $placeholders[] = "?";
            $params[] = $v;
        }

        $columns = implode(", ", $columns);
        $placeholders = implode(", ", $placeholders);

        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
        
        $stmt = $this->connect->prepare($sql);
        if ($stmt) {
            if (!$this->bind_params($stmt, $params)) {
                return ['data' => '', 'error' => 1, 'error_msg' => "Parameter binding failed"];
            }
            
            if ($stmt->execute()) {
                $data = $stmt->insert_id;
            } else {
                $error = 1;
                $error_msg = $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 1;
            $error_msg = $this->connect->error;
        }
        
        return ['data' => $data, 'error' => $error, 'error_msg' => $error_msg];
    }

    public function common_update($table, $fields, $where) {
        $data = '';
        $error = 0;
        $error_msg = "";
        
        if (!is_array($fields) || empty($fields)) {
            return ['data' => '', 'error' => 1, 'error_msg' => "No fields provided for update"];
        }

        if (!is_array($where) || empty($where)) {
            return ['data' => '', 'error' => 1, 'error_msg' => "No conditions provided for update"];
        }

        // Validate table name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return ['data' => '', 'error' => 1, 'error_msg' => "Invalid table name"];
        }

        $sql = "UPDATE `$table` SET ";
        $setParts = [];
        $params = [];
        
        foreach ($fields as $k => $v) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $k)) {
                return ['data' => '', 'error' => 1, 'error_msg' => "Invalid column name in SET clause"];
            }
            $setParts[] = "`$k` = ?";
            $params[] = $v;
        }
        $sql .= implode(", ", $setParts);

        $sql .= " WHERE ";
        $whereParts = [];
        foreach ($where as $k => $v) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $k)) {
                return ['data' => '', 'error' => 1, 'error_msg' => "Invalid column name in WHERE clause"];
            }
            $whereParts[] = "`$k` = ?";
            $params[] = $v;
        }
        $sql .= implode(" AND ", $whereParts);

        $stmt = $this->connect->prepare($sql);
        if ($stmt) {
            if (!$this->bind_params($stmt, $params)) {
                return ['data' => '', 'error' => 1, 'error_msg' => "Parameter binding failed"];
            }
            
            if ($stmt->execute()) {
                $data = $stmt->affected_rows;
            } else {
                $error = 1;
                $error_msg = $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 1;
            $error_msg = $this->connect->error;
        }
        
        return ['data' => $data, 'error' => $error, 'error_msg' => $error_msg];
    }

    public function common_delete($table, $where) {
        $data = '';
        $error = 0;
        $error_msg = "";
        
        if (!is_array($where) || empty($where)) {
            return ['data' => '', 'error' => 1, 'error_msg' => "No conditions provided for delete"];
        }

        // Validate table name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return ['data' => '', 'error' => 1, 'error_msg' => "Invalid table name"];
        }

        $sql = "DELETE FROM `$table` WHERE ";
        $whereParts = [];
        $params = [];
        
        foreach ($where as $k => $v) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $k)) {
                return ['data' => '', 'error' => 1, 'error_msg' => "Invalid column name in WHERE clause"];
            }
            $whereParts[] = "`$k` = ?";
            $params[] = $v;
        }
        $sql .= implode(" AND ", $whereParts);

        $stmt = $this->connect->prepare($sql);
        if ($stmt) {
            if (!$this->bind_params($stmt, $params)) {
                return ['data' => '', 'error' => 1, 'error_msg' => "Parameter binding failed"];
            }
            
            if ($stmt->execute()) {
                $data = $stmt->affected_rows;
            } else {
                $error = 1;
                $error_msg = $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 1;
            $error_msg = $this->connect->error;
        }
        
        return ['data' => $data, 'error' => $error, 'error_msg' => $error_msg];
    }

    // Close connection
    public function __destruct() {
        if ($this->connect) {
            $this->connect->close();
        }
    }
}