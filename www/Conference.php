<?php
class Conference {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Добавление участника
    public function addParticipant($fullName, $email, $birthYear, $section, $participationType, $needsCertificate, $newsletter) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO conference_participants 
            (full_name, email, birth_year, section, participation_type, needs_certificate, newsletter_subscription) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$fullName, $email, $birthYear, $section, $participationType, $needsCertificate, $newsletter]);
    }

    // Получение всех участников
    public function getAllParticipants($orderBy = 'created_at', $order = 'DESC') {
        $validOrders = ['ASC', 'DESC'];
        $order = in_array(strtoupper($order), $validOrders) ? strtoupper($order) : 'DESC';
        
        $stmt = $this->pdo->prepare("SELECT * FROM conference_participants ORDER BY $orderBy $order");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Получение участников старше определённого возраста
    public function getParticipantsOlderThan($age) {
        $currentYear = date('Y');
        $birthYearLimit = $currentYear - $age;
        
        $stmt = $this->pdo->prepare(
            "SELECT * FROM conference_participants 
            WHERE birth_year <= ? 
            ORDER BY created_at DESC"
        );
        $stmt->execute([$birthYearLimit]);
        return $stmt->fetchAll();
    }

    // Подсчёт общего количества участников
    public function getTotalCount() {
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM conference_participants");
        return $stmt->fetch()['count'];
    }

    // Подсчёт участников по секциям
    public function getCountBySection() {
        $stmt = $this->pdo->query(
            "SELECT section, COUNT(*) as count 
            FROM conference_participants 
            GROUP BY section 
            ORDER BY count DESC"
        );
        return $stmt->fetchAll();
    }

    // Получение статистики по сертификатам
    public function getCertificateStats() {
        $stmt = $this->pdo->query(
            "SELECT 
                COUNT(*) as total,
                SUM(needs_certificate) as with_certificate,
                SUM(NOT needs_certificate) as without_certificate
            FROM conference_participants"
        );
        return $stmt->fetch();
    }

    // Удаление участника по ID
    public function deleteParticipant($id) {
        $stmt = $this->pdo->prepare("DELETE FROM conference_participants WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Получение участника по ID
    public function getParticipantById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM conference_participants WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Обновление участника
    public function updateParticipant($id, $fullName, $email) {
        $stmt = $this->pdo->prepare(
            "UPDATE conference_participants SET full_name = ?, email = ? WHERE id = ?"
        );
        return $stmt->execute([$fullName, $email, $id]);
    }
}
?>