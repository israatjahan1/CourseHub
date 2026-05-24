<?php
declare(strict_types=1);

class StudentModel
{
    private PDO $db;
    public function __construct() { $this->db = getDatabase(); }

    public function findByEmail(string $e): array|false { $s=$this->db->prepare('SELECT * FROM students WHERE email=?');$s->execute([$e]);return $s->fetch(); }
    public function findById(int $id): array|false { $s=$this->db->prepare('SELECT * FROM students WHERE id=?');$s->execute([$id]);return $s->fetch(); }
    public function emailExists(string $e): bool { return (int)$this->db->prepare('SELECT COUNT(*) FROM students WHERE email=?')->execute([$e])||true??(bool)0; }
    public function create(array $d): int {
        $this->db->prepare('INSERT INTO students (first_name,last_name,email,password_hash) VALUES (?,?,?,?)')->execute([$d['first_name'],$d['last_name'],$d['email'],password_hash($d['password'],PASSWORD_BCRYPT)]);
        return (int)$this->db->lastInsertId();
    }
    public function updateProfile(int $id, array $d): void {
        $this->db->prepare('UPDATE students SET first_name=?,last_name=?,phone=?,bio=? WHERE id=?')->execute([$d['first_name'],$d['last_name'],$d['phone']??'',$d['bio']??'',$id]);
    }
    public function updatePassword(int $id, string $hash): void { $this->db->prepare('UPDATE students SET password_hash=? WHERE id=?')->execute([$hash,$id]); }
    public function deleteStudent(int $id): void { $this->db->prepare('DELETE FROM students WHERE id=?')->execute([$id]); }
    public function getInterests(int $sid): array {
        $s=$this->db->prepare('SELECT ir.*,p.title AS programme_title,p.slug AS programme_slug,p.level FROM interest_registrations ir JOIN programmes p ON p.id=ir.programme_id WHERE ir.student_id=? ORDER BY ir.created_at DESC');
        $s->execute([$sid]);return $s->fetchAll();
    }
    public function withdrawInterest(int $sid, int $rid): void { $this->db->prepare('DELETE FROM interest_registrations WHERE id=? AND student_id=?')->execute([$rid,$sid]); }
    public function getFavourites(int $sid): array {
        $s=$this->db->prepare('SELECT p.*,s.name AS leader_name FROM favourites f JOIN programmes p ON p.id=f.programme_id LEFT JOIN staff s ON s.id=p.programme_leader_id WHERE f.student_id=? ORDER BY f.created_at DESC');
        $s->execute([$sid]);return $s->fetchAll();
    }
    public function isFavourite(int $sid, int $pid): bool {
        $s=$this->db->prepare('SELECT COUNT(*) FROM favourites WHERE student_id=? AND programme_id=?');$s->execute([$sid,$pid]);return (int)$s->fetchColumn()>0;
    }
    public function addFavourite(int $sid, int $pid): void { $this->db->prepare('INSERT OR IGNORE INTO favourites (student_id,programme_id) VALUES (?,?)')->execute([$sid,$pid]); }
    public function removeFavourite(int $sid, int $pid): void { $this->db->prepare('DELETE FROM favourites WHERE student_id=? AND programme_id=?')->execute([$sid,$pid]); }
    public function findByResetToken(string $tok): array|false { $s=$this->db->prepare('SELECT * FROM students WHERE reset_token=? AND reset_expires>datetime("now")');$s->execute([$tok]);return $s->fetch(); }
    public function setResetToken(int $id, string $tok): void { $this->db->prepare('UPDATE students SET reset_token=?,reset_expires=datetime("now","+1 hour") WHERE id=?')->execute([$tok,$id]); }
    public function clearResetToken(int $id): void { $this->db->prepare('UPDATE students SET reset_token=NULL,reset_expires=NULL WHERE id=?')->execute([$id]); }
    public function checkEmailExists(string $e): bool { $s=$this->db->prepare('SELECT COUNT(*) FROM students WHERE email=?');$s->execute([$e]);return (int)$s->fetchColumn()>0; }
}
