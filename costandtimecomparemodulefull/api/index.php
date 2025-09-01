<?php
require_once __DIR__ . '/../load.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Allow CORS for local development (adjust as needed)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Helpers
function parseCoord($coord) {
    if (!is_string($coord)) return null;
    $coord = trim($coord);
    $coord = preg_replace('/\s+/', ' ', $coord);
    $parts = preg_split('/[, ]+/', $coord);
    if (!$parts || count($parts) < 2) return null;
    $lat = floatval($parts[0]);
    $lng = floatval($parts[1]);
    if (!is_finite($lat) || !is_finite($lng)) return null;
    return [ 'lat' => $lat, 'lng' => $lng ];
}

try {
    // Parse optional JSON body
    $raw = file_get_contents('php://input');
    $jsonInput = null;
    if (!empty($raw)) {
        $tmp = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $jsonInput = $tmp;
        }
    }

    // Determine action
    $action = isset($_GET['action']) ? $_GET['action'] : null;
    if (!$action && isset($_POST['action'])) $action = $_POST['action'];
    if (!$action && is_array($jsonInput) && isset($jsonInput['action'])) $action = $jsonInput['action'];
    if (!$action) $action = 'tolls';

    if ($action === 'tolls') {
        $stmt = $mysqli->prepare(
            "SELECT id, toll_order, toll_id, company_id, short_name, name, coordinates_enter, coordinates_exit, toll_on_highway FROM tolls ORDER BY company_id, toll_order"
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        $data = array_map(function ($row) {
            $row['enter'] = parseCoord($row['coordinates_enter']);
            $row['exit'] = parseCoord($row['coordinates_exit']);
            $row['gate_name'] = $row['name'];
            $row['gate_short_name'] = $row['short_name'];
            $row['gate_toll_id'] = $row['toll_id'];
            return $row;
        }, $rows);

        echo json_encode([
            'ok' => true,
            'count' => count($data),
            'tolls' => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'costs') {
        // Expect legs: [{enter_toll_id, exit_toll_id}], vehicle_type
        $legs = [];
        $vehicleType = 1;
        if (isset($_POST['legs'])) $legs = $_POST['legs'];
        if (isset($_POST['vehicle_type'])) $vehicleType = intval($_POST['vehicle_type']);
        if (is_array($jsonInput)) {
            if (isset($jsonInput['legs'])) $legs = $jsonInput['legs'];
            if (isset($jsonInput['vehicle_type'])) $vehicleType = intval($jsonInput['vehicle_type']);
        }
        if (!is_array($legs)) $legs = [];

        $legsWithCost = [];
        $total = 0.0;
        foreach ($legs as $leg) {
            $enterId = isset($leg['enter_toll_id']) ? intval($leg['enter_toll_id']) : null;
            $exitId = isset($leg['exit_toll_id']) ? intval($leg['exit_toll_id']) : null;
            if (!$enterId || !$exitId) continue;

            $routeId = null;
            $routeStmt = $mysqli->prepare("SELECT id FROM toll_routes WHERE enter_toll_id = ? AND exit_toll_id = ? LIMIT 1");
            $routeStmt->bind_param('ii', $enterId, $exitId);
            $routeStmt->execute();
            $routeRes = $routeStmt->get_result();
            if ($r = $routeRes->fetch_assoc()) $routeId = intval($r['id']);

            $cost = null;
            if ($routeId !== null) {
                $costStmt = $mysqli->prepare("SELECT cost FROM toll_route_costs WHERE toll_route_id = ? AND vehicle_type = ? LIMIT 1");
                $costStmt->bind_param('ii', $routeId, $vehicleType);
                $costStmt->execute();
                $costRes = $costStmt->get_result();
                if ($cr = $costRes->fetch_assoc()) {
                    $cost = (float)$cr['cost'];
                    $total += $cost;
                }
            }

            $infoStmt = $mysqli->prepare("SELECT id, toll_id, short_name, name FROM tolls WHERE id IN (?, ?) ORDER BY FIELD(id, ?, ?)");
            $infoStmt->bind_param('iiii', $enterId, $exitId, $enterId, $exitId);
            $infoStmt->execute();
            $infoRes = $infoStmt->get_result();
            $items = [];
            while ($ir = $infoRes->fetch_assoc()) { $items[] = $ir; }

            $legsWithCost[] = [
                'enter_toll_id' => $enterId,
                'exit_toll_id' => $exitId,
                'route_id' => $routeId,
                'vehicle_type' => $vehicleType,
                'cost' => $cost,
                'enter' => isset($items[0]) ? $items[0] : null,
                'exit' => isset($items[1]) ? $items[1] : null
            ];
        }

        echo json_encode([
            'ok' => true,
            'vehicle_type' => $vehicleType,
            'total_cost' => $total,
            'legs' => $legsWithCost
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'unknown_action'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>


