<?php
// models/User.php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table = 'users';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll(string $search = '', int $limit = 0, int $offset = 0): array {
        $where = '';
        $params = [];

        if ($search !== '') {
            $where = "WHERE name LIKE :search OR order_number LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $query = "SELECT * FROM {$this->table} {$where} ORDER BY created_at DESC";

        if ($limit > 0) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        if ($limit > 0) {
            $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(string $search = ''): int {
        $where = '';
        $params = [];

        if ($search !== '') {
            $where = "WHERE name LIKE :search OR order_number LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $query = "SELECT COUNT(*) FROM {$this->table} {$where}";
        $stmt  = $this->conn->prepare($query);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByHash($hash) {
        $query = "SELECT * FROM {$this->table} WHERE hash = :hash";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':hash', $hash);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data): int|false {
        $hash         = generateHash();
        $subscription = !empty($data['subscription_code'])
            ? base64_encode($data['subscription_code'])
            : $this->getDefaultSubscription();

        $query = "INSERT INTO {$this->table} (name, amount, subscription_code, order_number, hash, fetch_url_sub, use_fetch)
                VALUES (:name, :amount, :subscription_code, :order_number, :hash, :fetch_url_sub, :use_fetch)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':name',$data['name']);
        $stmt->bindValue(':amount',            $data['amount']);
        $stmt->bindValue(':subscription_code', $subscription);
        $stmt->bindValue(':order_number',      $data['order_number']);
        $stmt->bindValue(':hash',$hash);
        $stmt->bindValue(':fetch_url_sub',     $data['fetch_url_sub'] ?? '');
        $stmt->bindValue(':use_fetch',         $data['use_fetch'] ?? 0, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return false;
        }

        return (int) $this->conn->lastInsertId();
    }

    public function update($id, $data) {
        $subscription = base64_encode($data['subscription_code']);

        $query = "UPDATE {$this->table} SET name = :name, amount = :amount,
                    subscription_code = :subscription_code, order_number = :order_number,
                    fetch_url_sub = :fetch_url_sub, use_fetch = :use_fetch
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id',                $id, PDO::PARAM_INT);
        $stmt->bindValue(':name',              $data['name']);
        $stmt->bindValue(':amount',            $data['amount']);
        $stmt->bindValue(':subscription_code', $subscription);
        $stmt->bindValue(':order_number',      $data['order_number']);
        $stmt->bindValue(':fetch_url_sub',     $data['fetch_url_sub'] ?? '');
        $stmt->bindValue(':use_fetch',         $data['use_fetch'] ?? 0, PDO::PARAM_INT);

        return $stmt->execute();
    }


    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getLastInserted(): array|false {
        $query = "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT 1";
        $stmt  = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDailyCount(): int {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE DATE(created_at) = CURDATE()";
        $stmt  = $this->conn->prepare($query);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function getMonthlyCount(): int {
        $query = "SELECT COUNT(*) FROM {$this->table}
                  WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
                  AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $stmt  = $this->conn->prepare($query);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function getDefaultSubscription(): string {
        $query  = "SELECT setting_value FROM settings WHERE setting_key = 'default_subscription'";
        $stmt   = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? base64_encode($result['setting_value']) : '';
    }
}
