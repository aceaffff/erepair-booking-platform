<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

function auth_shop(PDO $db){
    $token = $_COOKIE['auth_token'] ?? '';
    if(!$token){ http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id, u.role, so.id as shop_id FROM users u INNER JOIN sessions s ON s.user_id=u.id LEFT JOIN shop_owners so ON so.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='shop_owner'){ http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
    return $u;
}

if($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

try{
    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $shop = auth_shop($db);
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $techId = (int)($input['id'] ?? 0);
    $password = (string)($input['password'] ?? '');
    $toggleActive = isset($input['active']) ? (bool)$input['active'] : null; // when provided, activate/deactivate
    if($techId<=0){ http_response_code(400); echo json_encode(['error'=>'Technician id required']); exit; }

    // check technician belongs to this shop
    $check = $db->prepare('SELECT t.id, u.id as user_id FROM technicians t INNER JOIN users u ON u.id=t.user_id WHERE t.id=? AND t.shop_id=?');
    $check->execute([$techId, $shop['shop_id']]);
    $row = $check->fetch();
    if(!$row){ http_response_code(404); echo json_encode(['error'=>'Technician not found']); exit; }

    // If activation toggle requested, update status and clear sessions when deactivating
    if($toggleActive !== null){
        // Use 'approved' for active, 'rejected' as our deactivated sentinel (compatible with schema)
        $newStatus = $toggleActive ? 'approved' : 'rejected';
        // Try to set desired status; if the column uses an ENUM without 'deactivated', fall back to 'rejected'
        $appliedStatus = $newStatus;
        try {
            $upd = $db->prepare('UPDATE users SET status=?, updated_at=NOW() WHERE id=?');
            $upd->execute([$appliedStatus, $row['user_id']]);
        } catch (PDOException $pe) {
            // Fallback to 'rejected' if 'deactivated' is not allowed by schema
            if($appliedStatus !== 'approved'){
                $appliedStatus = 'rejected';
                $upd2 = $db->prepare('UPDATE users SET status=?, updated_at=NOW() WHERE id=?');
                $upd2->execute([$appliedStatus, $row['user_id']]);
            } else {
                throw $pe;
            }
        }
        // Verify persisted status (non-fatal)
        $chk2 = $db->prepare('SELECT status FROM users WHERE id=?');
        $chk2->execute([$row['user_id']]);
        $current = $chk2->fetch();
        if(!$current || (string)$current['status'] !== $appliedStatus){
            echo json_encode(['success'=>true, 'status'=>$appliedStatus, 'warning'=>'Verification mismatch, but update attempted']);
            exit;
        }
        if(!$toggleActive){
            // Remove active sessions so deactivated tech is logged out
            $db->prepare('DELETE FROM sessions WHERE user_id=?')->execute([$row['user_id']]);
        }
        echo json_encode(['success'=>true, 'status'=>$appliedStatus]);
        exit;
    }

    // Otherwise, handle password reset
    if($password===''){ http_response_code(400); echo json_encode(['error'=>'Password required']); exit; }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $upd = $db->prepare('UPDATE users SET password=?, updated_at=NOW() WHERE id=?');
    $upd->execute([$hash, $row['user_id']]);

    echo json_encode(['success'=>true]);
}catch(Exception $e){ http_response_code(200); echo json_encode(['error'=>'Server error','detail'=>$e->getMessage()]); }
?>



