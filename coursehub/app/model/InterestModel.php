<?php
declare(strict_types=1);
class InterestModel {
    private PDO $db;
    public function __construct(){$this->db=getDatabase();}
    public function getAll(?int $pid=null): array {
        if($pid){$s=$this->db->prepare('SELECT ir.*,p.title AS programme_title FROM interest_registrations ir JOIN programmes p ON p.id=ir.programme_id WHERE ir.programme_id=? ORDER BY ir.created_at DESC');$s->execute([$pid]);}
        else{$s=$this->db->query('SELECT ir.*,p.title AS programme_title FROM interest_registrations ir JOIN programmes p ON p.id=ir.programme_id ORDER BY ir.created_at DESC');}
        return $s->fetchAll();
    }
    public function isDuplicate(string $email, int $pid): bool {
        $s=$this->db->prepare('SELECT COUNT(*) FROM interest_registrations WHERE email=? AND programme_id=?');$s->execute([$email,$pid]);return (int)$s->fetchColumn()>0;
    }
    public function create(array $d): void {
        $this->db->prepare('INSERT INTO interest_registrations (first_name,last_name,email,phone,programme_id,student_id,message) VALUES (?,?,?,?,?,?,?)')
            ->execute([$d['first_name'],$d['last_name'],$d['email'],$d['phone']??'',$d['programme_id'],$d['student_id']??null,$d['message']??'']);
    }
    public function deleteByEmail(string $e): int { $s=$this->db->prepare('DELETE FROM interest_registrations WHERE email=?');$s->execute([$e]);return $s->rowCount(); }
    public function deleteById(int $id): void { $this->db->prepare('DELETE FROM interest_registrations WHERE id=?')->execute([$id]); }
    public function count(): int { return (int)$this->db->query('SELECT COUNT(*) FROM interest_registrations')->fetchColumn(); }
}
