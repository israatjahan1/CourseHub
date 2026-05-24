<?php
declare(strict_types=1);

class ProgrammeModule
{
    private PDO $db;
    public function __construct() { $this->db = getDatabase(); }

    public function getAllPublishedProgrammes(): array {
        return $this->db->query(
            'SELECT p.*,s.name AS leader_name,s.email AS leader_email
             FROM programmes p LEFT JOIN staff s ON p.programme_leader_id=s.id
             WHERE p.published=1 ORDER BY p.level,p.title'
        )->fetchAll();
    }
    public function getAllProgrammes(): array {
        return $this->db->query(
            'SELECT p.*,s.name AS leader_name,
             (SELECT COUNT(*) FROM programme_modules pm WHERE pm.programme_id=p.id) AS module_count,
             (SELECT COUNT(*) FROM interest_registrations ir WHERE ir.programme_id=p.id) AS reg_count
             FROM programmes p LEFT JOIN staff s ON p.programme_leader_id=s.id ORDER BY p.level,p.title'
        )->fetchAll();
    }
    public function getProgrammeBySlug(string $slug): array|false {
        $st=$this->db->prepare('SELECT p.*,s.name AS leader_name,s.email AS leader_email,s.bio AS leader_bio,s.phone AS leader_phone,s.office AS leader_office FROM programmes p LEFT JOIN staff s ON p.programme_leader_id=s.id WHERE p.slug=? AND p.published=1');
        $st->execute([$slug]);return $st->fetch();
    }
    public function getProgrammeById(int $id): array|false {
        $st=$this->db->prepare('SELECT * FROM programmes WHERE id=?');$st->execute([$id]);return $st->fetch();
    }
    public function searchProgrammes(string $kw, string $level): array {
        $sql='SELECT p.*,s.name AS leader_name FROM programmes p LEFT JOIN staff s ON p.programme_leader_id=s.id WHERE p.published=1';
        $b=[];
        if($kw){$sql.=' AND (p.title LIKE ? OR p.description LIKE ?)';$b[]="%$kw%";$b[]="%$kw%";}
        if($level&&in_array($level,['Undergraduate','Postgraduate'])){$sql.=' AND p.level=?';$b[]=$level;}
        $sql.=' ORDER BY p.level,p.title';
        $st=$this->db->prepare($sql);$st->execute($b);return $st->fetchAll();
    }
    public function createProgramme(array $d): int {
        $slug=$this->makeSlug($d['title']);
        if((int)$this->db->query("SELECT COUNT(*) FROM programmes WHERE slug='$slug'")->fetchColumn())$slug.='-'.time();
        $this->db->prepare('INSERT INTO programmes (title,slug,level,description,duration_years,image_url,published,programme_leader_id) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$d['title'],$slug,$d['level'],$d['description']??'',$d['duration_years']??3,$d['image_url']??'',$d['published']??0,$d['programme_leader_id']?:null]);
        return (int)$this->db->lastInsertId();
    }
    public function updateProgramme(int $id, array $d): void {
        $this->db->prepare('UPDATE programmes SET title=?,level=?,description=?,duration_years=?,image_url=?,published=?,programme_leader_id=?,updated_at=CURRENT_TIMESTAMP WHERE id=?')
            ->execute([$d['title'],$d['level'],$d['description']??'',$d['duration_years']??3,$d['image_url']??'',$d['published']??0,$d['programme_leader_id']?:null,$id]);
    }
    public function deleteProgramme(int $id): void { $this->db->prepare('DELETE FROM programmes WHERE id=?')->execute([$id]); }
    public function togglePublished(int $id): void { $this->db->prepare('UPDATE programmes SET published=CASE WHEN published=1 THEN 0 ELSE 1 END WHERE id=?')->execute([$id]); }
    public function syncModules(int $pid, array $mids): void {
        $this->db->prepare('DELETE FROM programme_modules WHERE programme_id=?')->execute([$pid]);
        $st=$this->db->prepare('INSERT OR IGNORE INTO programme_modules (programme_id,module_id) VALUES (?,?)');
        foreach($mids as $m)$st->execute([$pid,(int)$m]);
    }
    public function getModulesForCompare(int $id): array {
        $st=$this->db->prepare('SELECT m.*,s.name AS leader_name FROM modules m JOIN programme_modules pm ON pm.module_id=m.id LEFT JOIN staff s ON s.id=m.module_leader_id WHERE pm.programme_id=? ORDER BY m.year_of_study,m.title');
        $st->execute([$id]);return $st->fetchAll();
    }
    private function makeSlug(string $t): string { return trim(preg_replace('/[\s-]+/','-',preg_replace('/[^a-z0-9\s-]/','',strtolower($t))),'-'); }
    public function count(): int { return (int)$this->db->query('SELECT COUNT(*) FROM programmes')->fetchColumn(); }
    public function countPublished(): int { return (int)$this->db->query('SELECT COUNT(*) FROM programmes WHERE published=1')->fetchColumn(); }
}
