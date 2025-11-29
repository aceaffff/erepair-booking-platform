<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/InputValidator.php';

try{
    $db = (new Database())->getConnection();
    $shopId = InputValidator::validateId($_GET['shop_owner_id'] ?? 0);
    if($shopId === null){ http_response_code(400); echo json_encode(['error'=>'Valid shop_owner_id required']); exit; }

    // create table if not exists (defensive)
    $db->exec("CREATE TABLE IF NOT EXISTS shop_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shop_owner_id INT NOT NULL,
        service_name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) DEFAULT 0,
        FOREIGN KEY (shop_owner_id) REFERENCES shop_owners(id) ON DELETE CASCADE
    )");

    $stmt = $db->prepare('SELECT id, service_name, price FROM shop_services WHERE shop_owner_id=? ORDER BY service_name');
    $stmt->execute([$shopId]);
    echo json_encode(['success'=>true, 'services'=>$stmt->fetchAll()]);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


