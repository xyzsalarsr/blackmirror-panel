<?php
// models/Transaction.php
require_once __DIR__ . '/../config/database.php';

class Transaction {
    private $conn;
    private $table = 'transactions';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll(int $limit = 0, int $offset = 0): array {
        $query = "SELECT t.*, u.name as user_name
                  FROM {$this->table} t
                  LEFT JOIN users u ON t.user_id = u.id
                  ORDER BY t.created_at DESC";

        if ($limit > 0) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($query);

        if ($limit > 0) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(): int {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table}");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): bool {
        $query = "INSERT INTO {$this->table} (user_id, amount, description)
                VALUES (:user_id, :amount, :description)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(':user_id', $data['user_id'], $data['user_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':amount', $data['amount']);
        $stmt->bindValue(':description', $data['description']);
        
        return $stmt->execute();
    }


    public function update(int $id, float $amount): bool {
        $query = "UPDATE {$this->table} SET amount = :amount WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':amount', $amount);
        return $stmt->execute();
    }

    public function delete(int $id): bool {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getMonthlyRevenue(): float {
        $query = "SELECT COALESCE(SUM(amount), 0) FROM {$this->table}
                  WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
                  AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    public function getTotalRevenue(): float {
        $query = "SELECT COALESCE(SUM(amount), 0) FROM {$this->table}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    public function getDailyRevenue(): float {
        $query = "SELECT COALESCE(SUM(amount), 0) FROM {$this->table}
                  WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    public function getDailyChart(int $days = 7): array {
        $query = "SELECT DATE(created_at) as date,
                         COALESCE(SUM(amount), 0) as revenue
                  FROM {$this->table}
                  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
