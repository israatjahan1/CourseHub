<?php
declare(strict_types=1);
class AdminModel {
    private PDO $db;
    public function __construct(){$this->db=getDatabase();}
    public function findByUsername(string $u): array|false {
        $s=$this->db->prepare('SELECT * FROM admins WHERE username=?');
        $s->execute([$u]);return $s->fetch();
    }
}
