<?php
declare(strict_types=1);

class ModuleModule
{
    private PDO $db;
    public function __construct() { $this->db = getDatabase(); }

    public function getModulesByProgrammeId(int $pid): array {
        $st=$this->db->prepare('SELECT m.*,s.name AS leader_name,s.email AS leader_email FROM modules m JOIN programme_modules pm ON pm.module_id=m.id LEFT JOIN staff s ON m.module_leader_id=s.id WHERE pm.programme_id=? ORDER BY m.year_of_study,m.title');
        $st->execute([$pid]);return $st->fetchAll();
    }
    public function getAllModules(): array {
        return $this->db->query(
            'SELECT m.*,s.name AS leader_name,
             (SELECT COUNT(*) FROM programme_modules pm WHERE pm.module_id=m.id) AS prog_count,
             (SELECT GROUP_CONCAT(p.title,", ") FROM programme_modules pm JOIN programmes p ON p.id=pm.programme_id WHERE pm.module_id=m.id AND p.published=1) AS shared_programmes,
             (SELECT GROUP_CONCAT(DISTINCT p.level) FROM programme_modules pm JOIN programmes p ON p.id=pm.programme_id WHERE pm.module_id=m.id AND p.published=1) AS programme_levels
             FROM modules m LEFT JOIN staff s ON m.module_leader_id=s.id ORDER BY m.year_of_study,m.title'
        )->fetchAll();
    }
    public function getModuleById(int $id): array|false {
        $st=$this->db->prepare('SELECT m.*,s.name AS leader_name,s.email AS leader_email,s.phone AS leader_phone,s.office AS leader_office,s.photo_url AS leader_photo FROM modules m LEFT JOIN staff s ON m.module_leader_id=s.id WHERE m.id=?');
        $st->execute([$id]);return $st->fetch();
    }
    public function getProgrammesForModule(int $mid): array {
        $st=$this->db->prepare('SELECT p.title,p.slug,p.level FROM programmes p JOIN programme_modules pm ON pm.programme_id=p.id WHERE pm.module_id=? AND p.published=1 ORDER BY p.title');
        $st->execute([$mid]);return $st->fetchAll();
    }
    public function createModule(array $d): int {
        $this->db->prepare('INSERT INTO modules (title,code,description,credits,year_of_study,image_url,module_leader_id) VALUES (?,?,?,?,?,?,?)')
            ->execute([$d['title'],strtoupper($d['code']),$d['description']??'',$d['credits']??20,$d['year_of_study']??1,$d['image_url']??'',$d['module_leader_id']?:null]);
        return (int)$this->db->lastInsertId();
    }
    public function updateModule(int $id, array $d): void {
        $this->db->prepare('UPDATE modules SET title=?,code=?,description=?,credits=?,year_of_study=?,image_url=?,module_leader_id=? WHERE id=?')
            ->execute([$d['title'],strtoupper($d['code']),$d['description']??'',$d['credits']??20,$d['year_of_study']??1,$d['image_url']??'',$d['module_leader_id']?:null,$id]);
    }
    public function deleteModule(int $id): void { $this->db->prepare('DELETE FROM modules WHERE id=?')->execute([$id]); }
    public function count(): int { return (int)$this->db->query('SELECT COUNT(*) FROM modules')->fetchColumn(); }
}