<?php
declare(strict_types=1);
class StaffModule
{
    private PDO $db;
    public function __construct() { $this->db = getDatabase(); }

    public function getAllStaff(): array {
        return $this->db->query(
            'SELECT s.*,
             (SELECT COUNT(*) FROM modules m WHERE m.module_leader_id=s.id) AS module_count,
             (SELECT COUNT(*) FROM programmes p WHERE p.programme_leader_id=s.id) AS prog_count
             FROM staff s ORDER BY s.name'
        )->fetchAll();
    }
    public function getStaffById(int $id): array|false {
        $s=$this->db->prepare('SELECT * FROM staff WHERE id=?');$s->execute([$id]);return $s->fetch();
    }
    public function findByEmail(string $email): array|false {
        $s=$this->db->prepare('SELECT * FROM staff WHERE email=?');$s->execute([$email]);return $s->fetch();
    }
    public function getModulesLedBy(int $id): array {
        $s=$this->db->prepare(
            'SELECT m.*,
             (SELECT COUNT(*) FROM programme_modules pm WHERE pm.module_id=m.id) AS prog_count
             FROM modules m WHERE m.module_leader_id=? ORDER BY m.year_of_study,m.title'
        );
        $s->execute([$id]);return $s->fetchAll();
    }
    public function getProgrammesLedBy(int $id): array {
        $s=$this->db->prepare('SELECT p.title,p.slug,p.level FROM programmes p WHERE p.programme_leader_id=? AND p.published=1');
        $s->execute([$id]);return $s->fetchAll();
    }
    public function createStaff(array $d): int {
        $this->db->prepare('INSERT INTO staff (name,role,department,email,password_hash,password_plain,bio,phone,office,photo_url) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([$d['name'],$d['role'],$d['department'],$d['email'],password_hash($d['password'],PASSWORD_BCRYPT),$d['password'],$d['bio']??'',$d['phone']??'',$d['office']??'',$d['photo_url']??'']);
        return (int)$this->db->lastInsertId();
    }
    public function updateStaff(int $id, array $d): void {
        $this->db->prepare('UPDATE staff SET name=?,role=?,department=?,email=?,bio=?,phone=?,office=?,photo_url=? WHERE id=?')
            ->execute([$d['name'],$d['role'],$d['department'],$d['email'],$d['bio']??'',$d['phone']??'',$d['office']??'',$d['photo_url']??'',$id]);
    }
    public function updatePassword(int $id, string $hash, string $plain = ''): void {
        $this->db->prepare('UPDATE staff SET password_hash=?,password_plain=? WHERE id=?')->execute([$hash,$plain,$id]);
    }
    public function deleteStaff(int $id): void {
        $this->db->prepare('DELETE FROM staff WHERE id=?')->execute([$id]);
    }
    public function count(): int {
        return (int)$this->db->query('SELECT COUNT(*) FROM staff')->fetchColumn();
    }
}