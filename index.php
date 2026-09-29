<?php
/**
 * E-Cardeneta - Front Controller para Alojamento Partilhado
 * 
 * Este ficheiro permite que a aplicação seja executada a partir do diretório raiz,
 * facilitando a instalação em ambientes de alojamento partilhado onde não é 
 * possível apontar o "Document Root" para a pasta /public.
 */

// Define que estamos a carregar a partir da raiz
define('ROOT_ACCESS', true);

// Carrega o front controller original localizado na pasta public
require_once __DIR__ . '/public/index.php';
