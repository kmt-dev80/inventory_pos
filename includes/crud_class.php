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
        call_user_func_array([$stmt, 'bind_param'], $this->ref_values($values));
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

        $sql = "SELECT $fields FROM $table";

        if ($where) {
            $sql .= " WHERE ";
            $i = 0;
            foreach ($where as $k => $v) {
                $sql .= "$k = ?";
                $params[] = $v;
                if ($i < count($where) - 1) {
                    $sql .= " AND ";
                }
                $i++;
            }
        }

        if ($sort) {
            $sql .= " ORDER BY $sort $sort_type";
        }

        if ($limit !== false) {
            $sql .= " LIMIT ?";
            $params[] = (int)$limit;
            
            if ($offset !== false) {
                $sql .= " OFFSET ?";
                $params[] = (int)$offset;
            }
        }

        $stmt = $this->connect->prepare($sql);
        if ($stmt) {
            if (!empty($params)) {
                $this->bind_params($stmt, $params);
            }
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    while ($r = $result->fetch_object()) {
                        $data[] = $r;
                    }
                } else {
                    $error = 1;
                    $error_msg = "No data available";
                }
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
        $params = [];
        
        $columns = implode(", ", array_keys($fields));
        $placeholders = implode(", ", array_fill(0, count($fields), "?"));
        $params = array_values($fields);

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $stmt = $this->connect->prepare($sql);
        if ($stmt) {
            $this->bind_params($stmt, $params);
            
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
        $params = [];
        
        $sql = "UPDATE $table SET ";
        $setParts = [];
        foreach ($fields as $k => $v) {
            $setParts[] = "$k = ?";
            $params[] = $v;
        }
        $sql .= implode(", ", $setParts);

        if ($where) {
            $sql .= " WHERE ";
            $whereParts = [];
            foreach ($where as $k => $v) {
                $whereParts[] = "$k = ?";
                $params[] = $v;
            }
            $sql .= implode(" AND ", $whereParts);
        }

        $stmt = $this->connect->prepare($sql);
        if ($stmt) {
            $this->bind_params($stmt, $params);
            
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
        $params = [];
        
        $sql = "DELETE FROM $table";
        
        if ($where) {
            $sql .= " WHERE ";
            $whereParts = [];
            foreach ($where as $k => $v) {
                $whereParts[] = "$k = ?";
                $params[] = $v;
            }
            $sql .= implode(" AND ", $whereParts);
        }

        $stmt = $this->connect->prepare($sql);
        if ($stmt) {
            if (!empty($params)) {
                $this->bind_params($stmt, $params);
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
?>