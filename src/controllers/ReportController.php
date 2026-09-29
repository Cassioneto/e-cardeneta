<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class ReportController {
    private $db;
    public function __construct() { $this->db = Database::getConnection(); }

    public function index() {
        Security::requireAuth(['super_admin', 'admin']);
        
        // Relatório 1: Ocupação por Turma
        $stmtClasses = $this->db->query("
            SELECT c.name, COUNT(ch.id) as total 
            FROM classes c 
            LEFT JOIN children ch ON ch.class_id = c.id 
            GROUP BY c.id
        ");
        $classStats = $stmtClasses->fetchAll();

        // Relatório 2: Administrações de Medicamentos (Últimos 30 dias)
        $stmtMed = $this->db->query("
            SELECT administered, COUNT(*) as total 
            FROM medicine_administrations 
            WHERE scheduled_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY administered
        ");
        $medStats = $stmtMed->fetchAll();

        // Relatório 3: Registos de Saúde por Tipo
        $stmtHealth = $this->db->query("
            SELECT record_type, COUNT(*) as total 
            FROM health_records 
            GROUP BY record_type
        ");
        $healthStats = $stmtHealth->fetchAll();

        $viewFile = __DIR__ . '/../views/reports.php';
        require __DIR__ . '/../views/layout.php';
    }
}
