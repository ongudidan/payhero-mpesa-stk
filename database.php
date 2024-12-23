<?php
class Database
{
    private $conn;

    public function __construct()
    {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function query($sql)
    {
        $result = $this->conn->query($sql);
        if (!$result) {
            error_log("Database Query Error: " . $this->conn->error);
        }
        return $result;
    }

    public function execute($sql, $params = [], $types = '')
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Database Prepare Error: " . $this->conn->error);
            return false;
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log("Database Execute Error: " . $stmt->error);
            return false;
        }

        return $stmt->get_result() ?: true;
    }

    public function escapeString($str)
    {
        return $this->conn->real_escape_string($str);
    }

    public function getLastInsertId()
    {
        return $this->conn->insert_id;
    }

    public function getError()
    {
        return $this->conn->error;
    }

    public function close()
    {
        $this->conn->close();
    }

    public function beginTransaction()
    {
        $this->conn->begin_transaction();
    }

    public function commit()
    {
        $this->conn->commit();
    }

    public function rollback()
    {
        $this->conn->rollback();
    }
}
