<?php
namespace Controllers;
use Config\Security;

class DashboardController {
    public function index() {
        Security::requireAuth(); // Assegura o estado logado
        
        $role = $_SESSION['user_role'];
        $viewFile = __DIR__ . '/../views/dashboard.php';
        
        // Passar os dados baseados na role
        $dashboardData = [
            'role' => $role,
            'title' => 'Painel Principal'
        ];

        require __DIR__ . '/../views/layout.php';
    }
}
