<?php
/*

WARNING: THIS PAGE NEEDS TO RUN AS DAILY CRONJOB.
http://(websiteurl)/costandtimecomparemodule/cron/?pass=(cronPass defined in .env)&action=otoyolas_price_update
or you can run with php command.

Due to lack of internship permissions to the company system. This php file is made for to collect data from the company public website instead of directly getting from DB.

*/
require_once __DIR__ . '/../load.php';
$GOOGLE_API_KEY = $_ENV['GOOGLE_API_KEY'];
ini_set('max_execution_time', '3000');
if (php_sapi_name() === 'cli')
    $n = "\n";
else
    $n = "<br>\n";
if ($_GET['pass'] !== $_ENV['CRON_PASS']) {
    echo '401 Unauthorized. Please put pass= parameter to the url with the value of correct password. Password defined at .env .';
    exit();
}
function getCompany($search)
{
    global $mysqli;
    $search = "%" . $search . "%";
    $getCompany = $mysqli->prepare('SELECT * FROM highway_companies WHERE LOWER(price_check_api_url) LIKE ?');
    $getCompany->bind_param('s', $search);
    $getCompany->execute();

    $getCompanyResult = $getCompany->get_result();
    $getCompanyFetch = $getCompanyResult->fetch_all(MYSQLI_ASSOC);
    return $getCompanyFetch[0];
}
function getTolls($companyID)
{
    global $mysqli;
    $return = [];
    $getTolls = $mysqli->prepare("SELECT * FROM tolls WHERE company_id = ?");
    $getTolls->bind_param('i', $companyID);
    $getTolls->execute();

    $result = $getTolls->get_result();

    $return['data'] = $result->fetch_all(MYSQLI_ASSOC);

    $return['count'] = $result->num_rows;
    return $return;
}
function getTollRoute($enterTollID, $exitTollID)
{
    global $mysqli;
    $return = [];
    $getRoute = $mysqli->prepare("SELECT * FROM toll_routes WHERE enter_toll_id = ? AND exit_toll_id = ?");
    $getRoute->bind_param('ii', $enterTollID, $exitTollID);
    $getRoute->execute();

    $getRouteResult = $getRoute->get_result();
    $return['data'] = $getRouteResult->fetch_all(MYSQLI_ASSOC);
    $return['count'] = $getRouteResult->num_rows;
    return $return;
}
function getTollsFromOtoyolASWebsite()
{
    $getTollsFromWebsite = file_get_contents('https://isletme.otoyolas.com.tr/gecis-ucreti-hesapla/');
    preg_match('#var istasyonlarg = {(.*?)};
	
	
	var istasyonlarc =#is', $getTollsFromWebsite, $getTollsFromWebsite); //If this page changes It wont work.

    return $getTollsFromWebsite = safe_json_decode("{" . $getTollsFromWebsite[1] . "}");
}
function getFuelPrices()
{   
    $base_Benzin = 0;
    $base_Dizel = 0;
    $base_LPG = 0;
    $base_ElektrikAC = 0;
    $base_ElektrikDC = 0;
    
    $getFuelFromWebsite = file_get_contents('https://api.opet.com.tr/api/fuelprices/allprices');
    $getFuelFromWebsite = safe_json_decode($getFuelFromWebsite);
   
        $base_Benzin = $getFuelFromWebsite[20]['prices'][0]['amount'];
        $base_Dizel = $getFuelFromWebsite[20]['prices'][2]['amount'];
    //print_r($getFuelFromWebsite);
    //
    $getLPGFromWebsite = file_get_contents('https://www.petrolofisi.com.tr/akaryakit-fiyatlari');
    preg_match('#<tr class="price-row district-01600" data-disctrict-id="01600" data-disctrict-name="BURSA">
                    <td>BURSA</td>
                    <td>
                        <span class="with-tax">(.*?)</span>
                        <span class="without-tax">(.*?)</span>
                        TL/LT
                        <sup class="without-tax">(.*?)</sup>
                    </td>
                    <td>
                        <span class="with-tax">(.*?)</span>
                        <span class="without-tax">(.*?)</span>
                        TL/LT
                        <sup class="without-tax">(.*?)</sup>
                    </td>
                    <td>
                        <span class="with-tax">(.*?)</span>
                        <span class="without-tax">(.*?)</span>
                        TL/LT
                        <sup class="without-tax">(.*?)</sup>
                    </td>
                    <td>
                        <span class="with-tax">(.*?)</span>
                        <span class="without-tax">(.*?)</span>
                        TL/LT
                        <sup class="without-tax">(.*?)</sup>
                    </td>
                    <td>
                        <span class="with-tax">(.*?)</span>
                        <span class="without-tax">(.*?)</span>
                        TL/LT
                        <sup class="without-tax">(.*?)</sup>
                    </td>
                    <td>
                        <span class="with-tax">(.*?)</span>
                        <span class="without-tax">(.*?)</span>
                        TL/LT
                        <sup class="without-tax">(.*?)</sup>
                    </td>
                </tr>
                <tr class="price-row district-01700" data-disctrict-id="01700" data-disctrict-name="CANAKKALE">
                    <td>CANAKKALE</td>#is', $getLPGFromWebsite, $getLPGFromWebsitepreg); //If this page changes It wont work.
                    $base_LPG = $getLPGFromWebsitepreg[16];
    //$getLPGFromWebsite = safe_json_decode($getLPGFromWebsite);
    $headers = [
        ":authority"=>"sarjfiyat.com",
        ":method"=>"POST",
        ":path"=>"/get_prices.php",
        ":scheme"=>"https",
       "Accept: */*",
    "Accept-Encoding: gzip, deflate",
        "Accept-Language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7",
        "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
        "Origin: https://sarjfiyat.com",
        "Referer: https://sarjfiyat.com/",
        "Sec-CH-UA: \"Not)A;Brand\";v=\"8\", \"Chromium\";v=\"138\", \"Google Chrome\";v=\"138\"",
        "Sec-CH-UA-Mobile: ?0",
        "Sec-CH-UA-Platform: \"Windows\"",
        "Sec-Fetch-Dest: empty",
        "Sec-Fetch-Mode: cors",
        "Sec-Fetch-Site: same-origin",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36",
        "X-Requested-With: XMLHttpRequest"
    ];
    
    $getElectricPricesAC = callCurl('https://sarjfiyat.com/get_prices.php',$headers,['type'=>'AC']);
   
    preg_match_all('#<tr>
    <td rowspan="(.*?)">
        <div class="logo-container">
           (.*?)
            <span>(.*?)</span>
        </div>
    </td>
    <td>(.*?)TL(.*?)</td></tr></tr>#is', $getElectricPricesAC, $getElectricPricesACpreg1);
    preg_match_all('#<tr><td>(.*?)TL(.*?)</td></tr>#is', $getElectricPricesAC, $getElectricPricesACpreg2);
    //print_r($getElectricPricesACpreg1);
    $count_ac = 0;
    foreach($getElectricPricesACpreg1[4] as $key => $value){
        if($value == '' || !is_numeric($value) || $value == 0){ continue; }
        $count_ac++;
        $base_ElektrikAC += $value;
        
    }
    //print_r($getElectricPricesACpreg2);
    foreach($getElectricPricesACpreg2[1] as $key => $value){
        if($value == '' || !is_numeric($value) || $value == 0){ continue; }
        $count_ac++;
        $base_ElektrikAC += $value;
        
    }
    $base_ElektrikAC = $base_ElektrikAC/$count_ac;
    //echo $base_ElektrikAC;
    $getElectricPricesDC = callCurl('https://sarjfiyat.com/get_prices.php',$headers,['type'=>'DC']);
    preg_match_all('#<tr>
    <td rowspan="(.*?)">
        <div class="logo-container">
           (.*?)
            <span>(.*?)</span>
        </div>
    </td>
    <td>(.*?)TL(.*?)</td></tr></tr>#is', $getElectricPricesDC, $getElectricPricesDCpreg1);
    preg_match_all('#<tr><td>(.*?)TL(.*?)</td></tr>#is', $getElectricPricesDC, $getElectricPricesDCpreg2);
   // print_r($getElectricPricesDCpreg1);
    $count_dc = 0;
    foreach($getElectricPricesDCpreg1[4] as $key => $value){
        if($value == '' || !is_numeric($value) || $value == 0){ continue; }
        $count_dc++;
        $base_ElektrikDC += $value;
        
    }
   // print_r($getElectricPricesDCpreg2);
    foreach($getElectricPricesDCpreg2[1] as $key => $value){
        if($value == '' || !is_numeric($value) || $value == 0){ continue; }
            $count_dc++;
        $base_ElektrikDC += $value;
        
    }
    $base_ElektrikDC = $base_ElektrikDC/$count_dc;
    //echo $base_ElektrikDC;
   return ['Benzin'=>$base_Benzin,'Dizel'=>$base_Dizel,'LPG'=>$base_LPG,'Elektrikli (AC)'=>$base_ElektrikAC,'Elektrikli (DC)'=>$base_ElektrikDC];
 
}

function getGoogleRoute($origin, $destination, $apiKey = null)
{
    global $GOOGLE_API_KEY;
    if ($apiKey === null) {
        $apiKey = $GOOGLE_API_KEY;
    }
    $url = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    $body = [
        "origin" => [
            "location" => [
                "latLng" => ["latitude" => $origin['lat'], "longitude" => $origin['lng']]
            ]
        ],
        "destination" => [
            "location" => [
                "latLng" => ["latitude" => $destination['lat'], "longitude" => $destination['lng']]
            ]
        ],
        "travelMode" => "DRIVE",
        "routingPreference" => "TRAFFIC_UNAWARE",
        "polylineEncoding" => "ENCODED_POLYLINE",
        "polylineQuality" => "HIGH_QUALITY",
        "routeModifiers" => [
            "avoidFerries" => true,
            "avoidTolls" => false,
            "avoidHighways" => false
        ]

    ];

    $headers = [
        "Content-Type: application/json",
        "X-Goog-Api-Key: $apiKey",
        "X-Goog-FieldMask: routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline,routes.localizedValues"
    ]; //  "X-Goog-FieldMask: routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline,routes.legs,routes.travelAdvisory,routes.localizedValues"

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => $error];
    }

    $data = json_decode($response, true);
    //print_r($data);
    if (!isset($data['routes'][0])) {
        return ['error' => 'No route found'];
    }

    $route = $data['routes'][0];
    $route['polyline']['encodedPolyline'] = str_replace('\\', '\\\\', $route['polyline']['encodedPolyline']);


    return [
        'polyline' => $route['polyline']['encodedPolyline'],
        'distance_meters' => $route['distanceMeters'],
        'duration' => $route['duration'] // ISO 8601 format, e.g., "123s"
    ];
}
/* CHECK FOR encoded polyline

$origin = [];
$origin['lat'] = '40.77228805818461';
$origin['lng'] = '29.518380788635618';
$destination = [];
$destination['lat'] = '40.25627159558342';
$destination['lng'] = '28.709880262233117';
print_r(getGoogleRoute($origin, $destination));
die;*/
/* POLyLINE DECODE FIX */
/*$getRoute = $mysqli->prepare("SELECT * FROM toll_routes ");
   $getRoute->execute();

   $getRouteResult = $getRoute->get_result();
   $return = $getRouteResult->fetch_all(MYSQLI_ASSOC);
   $totalaffect = 0;
   foreach($return as $key => $value) {
       $decodedpolyline = decodeAndFormatPolyline($value['polyline_encoded']);
    $setRoutePrice = $mysqli->prepare("INSERT INTO toll_route_polyline_decoded (toll_route_id, polyline_decoded)
                                                               VALUES (?, ?)
                                                               ON DUPLICATE KEY UPDATE
                                                                polyline_decoded = VALUES(polyline_decoded);");
   $setRoutePrice->bind_param('is', $value['id'], $decodedpolyline);
   $setRoutePrice->execute();
   $affectedRows = $setRoutePrice->affected_rows;
   $totalaffect += $totalaffect; 
   echo $affectedRows;
   }
   echo '---'.$totalaffect;
   die;*/
/* END OF POLyLINE DECODE FIX */
/* test 2 -- WORKS DISTANCE POLYLINE TO POINT -- THIS PARTS LOGIC WILL BE USED FOR FRONT END PART.*/
/*
function decodexPolyline($encoded) {
     $encoded = str_replace('\\\\', '\\', $encoded);
    $points = [];
    $index = 0;
    $lat = 0;
    $lng = 0;

    while ($index < strlen($encoded)) {
        $b = 0;
        $shift = 0;
        $result = 0;

        do {
            $b = ord($encoded[$index++]) - 63;
            $result |= ($b & 0x1f) << $shift;
            $shift += 5;
        } while ($b >= 0x20);
        $deltaLat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
        $lat += $deltaLat;

        $shift = 0;
        $result = 0;

        do {
            $b = ord($encoded[$index++]) - 63;
            $result |= ($b & 0x1f) << $shift;
            $shift += 5;
        } while ($b >= 0x20);
        $deltaLng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
        $lng += $deltaLng;

        $points[] = [$lat / 1e5, $lng / 1e5];
    }

    return $points;
}

function haverxsine($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $lat1 = deg2rad($lat1);
    $lat2 = deg2rad($lat2);

    $a = sin($dLat / 2) ** 2 + sin($dLon / 2) ** 2 * cos($lat1) * cos($lat2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

function distanceToSegment($lat, $lng, $lat1, $lng1, $lat2, $lng2) {
    $toRad = fn($deg) => deg2rad($deg);
    $toDeg = fn($rad) => rad2deg($rad);

    $a = [$toRad($lat1), $toRad($lng1)];
    $b = [$toRad($lat2), $toRad($lng2)];
    $p = [$toRad($lat), $toRad($lng)];

    $dLat = $b[0] - $a[0];
    $dLng = $b[1] - $a[1];

    $dot = (($p[0] - $a[0]) * $dLat + ($p[1] - $a[1]) * $dLng) / ($dLat ** 2 + $dLng ** 2);
    $t = max(0, min(1, $dot));

    $projLat = $a[0] + $t * $dLat;
    $projLng = $a[1] + $t * $dLng;

    return haverxsine($lat, $lng, $toDeg($projLat), $toDeg($projLng));
}

function checkEachTollOnPolyline($polyline, $tollPoints, $threshold = 10.0) {
    $results = [];
    $coords = decodexPolyline($polyline);

    foreach ($tollPoints as $i => $toll) {
        $minDist = INF;
        $matched = false;

        for ($j = 0; $j < count($coords) - 1; $j++) {
            $dist = distanceToSegment(
                $toll[0], $toll[1],
                $coords[$j][0], $coords[$j][1],
                $coords[$j + 1][0], $coords[$j + 1][1]
            );

            if ($dist < $minDist) {
                $minDist = $dist;
            }

            if ($dist <= $threshold) {
                $matched = true;
                break;
            }
        }

        $results[] = [
            'index' => $i,
            'lat' => $toll[0],
            'lng' => $toll[1],
            'matched' => $matched,
            'min_distance_meters' => round($minDist, 2),
        ];
    }

    return $results;
}

// Example usage:
// Main check
$getRoutes = $mysqli->query("
    SELECT 
        tr.id AS trid, 
        tr.polyline_encoded, 
        t1.name as t1_name,
        t2.name as t2_name,
        t1.coordinates_enter AS t1_enter, 
        t1.coordinates_exit AS t1_exit,
        t2.coordinates_enter AS t2_enter, 
        t2.coordinates_exit AS t2_exit
    FROM toll_routes tr
    JOIN tolls t1 ON tr.enter_toll_id = t1.id
    JOIN tolls t2 ON tr.exit_toll_id = t2.id
    WHERE invalid = 0
");
$counts = 0;
while ($row = $getRoutes->fetch_assoc()) {


    $t1_enter = $row['t1_enter'];
    $t1_exit  = $row['t1_exit'];
    $t2_enter = $row['t2_enter'];
    $t2_exit  = $row['t2_exit'];
    $tollCoords = [
   explode(',', str_replace(' ', '', $row['t1_enter'])),
    explode(',', str_replace(' ', '', $row['t1_exit'])),
    explode(',', str_replace(' ', '', $row['t2_enter'])),
    explode(',', str_replace(' ', '', $row['t2_exit'])),
    ];

  $resultsx = checkEachTollOnPolyline($row['polyline_encoded'], $tollCoords);
    if(($resultsx[0]['matched'] && $resultsx[1]['matched']) || ($resultsx[2]['matched'] && $resultsx[3]['matched'])){  }else{ continue; }
    $counts++;
    echo "Route ".$row['trid']."-".$row['t1_name']."-".$row['t2_name'].$n;
foreach ($resultsx as $res) {
    echo " Toll #{$res['index']} @ ({$res['lat']}, {$res['lng']}) ";
    echo $res['matched'] ? "✅ Matched" : "❌ Not matched";
    echo " (min dist: {$res['min_distance_meters']} m)\n";
}
echo $n."--".$n;
}
echo $counts;



die;*/
/* test 2 end*/

function setRoutePrice($tall_route_id, $vehicleType, $price)
{
    global $mysqli;
    $setRoutePrice = $mysqli->prepare("INSERT INTO toll_route_costs (toll_route_id, vehicle_type, cost, updated_at)
                                                                VALUES (?, ?, ?, NOW())
                                                                ON DUPLICATE KEY UPDATE
                                                                 cost = VALUES(cost),
                                                                vehicle_type = VALUES(vehicle_type),
                                                                  updated_at = VALUES(updated_at);");
    $setRoutePrice->bind_param('iid', $tall_route_id, $vehicleType, $price);
    $setRoutePrice->execute();
    $affectedRows = $setRoutePrice->affected_rows;
    return $affectedRows;
}
function setFuelPrices($name,$price)
{
    global $mysqli;
    $setRoutePrice = $mysqli->prepare("INSERT INTO fuel_types (name, price, updated_at)
                                                                VALUES (?, ?, NOW())
                                                                ON DUPLICATE KEY UPDATE
                                                                 name = VALUES(name),
                                                                price = VALUES(price),
                                                                  updated_at = VALUES(updated_at);");
    $setRoutePrice->bind_param('sd', $name, $price);
    $setRoutePrice->execute();
    $affectedRows = $setRoutePrice->affected_rows;
    return $affectedRows;
}
function decodeAndFormatPolyline($encodedPolyline, $doubleBackslash = true)
{
    if ($doubleBackslash) {
        // Unescape double backslashes to single ones
        $encodedPolyline = str_replace('\\\\', '\\', $encodedPolyline);
    }
    $points = [];
    $index = 0;
    $len = strlen($encodedPolyline);
    $lat = 0;
    $lng = 0;

    while ($index < $len) {
        $b = 0;
        $shift = 0;
        $result = 0;

        do {
            $b = ord($encodedPolyline[$index++]) - 63;
            $result |= ($b & 0x1f) << $shift;
            $shift += 5;
        } while ($b >= 0x20);
        $dlat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
        $lat += $dlat;

        $shift = 0;
        $result = 0;
        do {
            $b = ord($encodedPolyline[$index++]) - 63;
            $result |= ($b & 0x1f) << $shift;
            $shift += 5;
        } while ($b >= 0x20);
        $dlng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
        $lng += $dlng;

        // Store as [lng, lat] for your format
        $points[] = sprintf('[%.6f,%.6f]', $lng / 1e5, $lat / 1e5);
    }

    return implode(',', $points); // Format: [lng,lat],[lng,lat],...
}
function setRoute($enter_toll_id, $exit_toll_id, $short_name, $polyline_encoded, $polyline_decoded, $distance_meters, $duration)
{
    global $mysqli;

    // Insert or update toll_routes
    $setRoutePrice = $mysqli->prepare("
        INSERT INTO toll_routes (
            enter_toll_id, exit_toll_id, short_name, polyline_encoded, distance_meters, duration, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            short_name = VALUES(short_name),
            polyline_encoded = VALUES(polyline_encoded),
            distance_meters = VALUES(distance_meters),
            duration = VALUES(duration),
            updated_at = VALUES(updated_at)
    ");

    $setRoutePrice->bind_param('iissii', $enter_toll_id, $exit_toll_id, $short_name, $polyline_encoded, $distance_meters, $duration);
    $setRoutePrice->execute();

    $affectedRows = $setRoutePrice->affected_rows;
    if ($affectedRows > 0) {
        // Get the route ID (either inserted or existing)
        $routeQuery = $mysqli->prepare("
        SELECT id FROM toll_routes WHERE enter_toll_id = ? AND exit_toll_id = ?
    ");
        $routeQuery->bind_param('ii', $enter_toll_id, $exit_toll_id);
        $routeQuery->execute();
        $result = $routeQuery->get_result();

        if ($row = $result->fetch_assoc()) {
            $toll_route_id = $row['id'];

            // Insert or update decoded polyline
            $decodedInsert = $mysqli->prepare("
            INSERT INTO toll_route_polyline_decoded (toll_route_id, polyline_decoded)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE
                polyline_decoded = VALUES(polyline_decoded)
        ");
            $decodedInsert->bind_param('is', $toll_route_id, $polyline_decoded);
            $decodedInsert->execute();
        }
    }
    return $affectedRows;
}

switch ($_GET['action']) {
    case 'otoyolas_price_update': // IT TAKES THE PRICES FROM ROAD COMPANYS WEBSITE AND UPDATE ON DB.


        $getCompany = getCompany('otoyolas');

        if (!$getCompany || $getCompany['id'] == null) {
            echo $n . 'Please check the company added to the database with correct api url.' . $n;
            exit;
        }
        $getTolls = getTolls($getCompany['id']);
        $tolls = $getTolls['data'];
        $tollsCount = $getTolls['count'];
        $getTollsFromWebsite = getTollsFromOtoyolASWebsite();
        if ($tollsCount !== count($getTollsFromWebsite) && $_GET['force'] !== 1) {
            echo $n . 'Toll counts different from the website and the database. Please make sure tolls are updated at DB. You can find coordinates of the tolls at Google Maps.' . $n .
                $n . 'DB Results:' . $n . json_encode($tolls, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) .
                $n . 'Website Results:' . $n . json_encode($getTollsFromWebsite, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $n . 'To continue to update please put force=1 parameter to the url.' . $n;
            die;
        }
        $headers = [
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding: gzip, deflate, br, zstd',
            'Accept-Language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
            'Connection: keep-alive',
            'Host: mobil.otoyolas.com.tr',
            'Origin: https://isletme.otoyolas.com.tr',
            'Referer: https://isletme.otoyolas.com.tr/',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-site',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',
            'sec-ch-ua: "Not)A;Brand";v="8", "Chromium";v="138", "Google Chrome";v="138"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
        ];
        $testCount = 0;
        $successCount = 0;
        $vehicleTypes = [1, 2, 3, 4, 5, 6];
        //print_r($tolls);
        foreach ($tolls as $keyEnter => $valueEnter) {
            foreach ($tolls as $keyExit => $valueExit) {
                if ($valueEnter['name'] == $valueExit['name'])
                    continue;

                $getRouteResult = getTollRoute($valueEnter['id'], $valueExit['id']);
                $getRouteFetch = $getRouteResult['data'];
                $getRouteCount = $getRouteResult['count'];
                if ($getRouteCount == 0) {
                    $testCount++;
                    echo $n . $valueEnter['name'] . '-' . $valueExit['name'] . ' cannot found at toll_routes table. To add this use otoyolas_add_update_tolls' . $n;
                    echo $testCount;
                    continue;
                }
                foreach ($vehicleTypes as $vehicleType) {
                    $tempValueEnterName = $tempValueExitName = '';

                    $url = 'https://mobil.otoyolas.com.tr/WS_Restful/calculatePrice2?' . http_build_query([
                        'enterStation' => $valueEnter['name'],
                        'exitStation' => $valueExit['name'],
                        'vehicleType' => $vehicleType
                    ]);
                    //echo $url;
                    $getPrice = safe_json_decode(file_get_contents($url));
                    //$getPrice = callCurl($url, $headers); // in Case of user-agent blockage use this line and remove the file_get_contents line. Because file_get_contents faster than this.
                    $getPrice = str_replace(',', '.', $getPrice['price']);

                    if ((!is_numeric($getPrice) || $getPrice < 0 || trim($getPrice) === "") && ($valueEnter['short_name'] == 'osm' || $valueExit['short_name'] == 'osm')) {
                        if ($valueEnter['short_name'] == 'osm') {
                            echo '*1*';
                            $tempValueEnterName = str_replace('İzmir', 'İstanbul', $valueEnter['name']);
                            $tempValueExitName = $valueExit['name'];
                        } elseif ($valueExit['short_name'] == 'osm') {
                            echo '*2*';
                            $tempValueEnterName = $valueEnter['name'];
                            $tempValueExitName = str_replace('İzmir', 'İstanbul', $valueExit['name']);
                        }
                        echo $url = 'https://mobil.otoyolas.com.tr/WS_Restful/calculatePrice2?' . http_build_query([
                            'enterStation' => $tempValueEnterName,
                            'exitStation' => $tempValueExitName,
                            'vehicleType' => $vehicleType
                        ]);
                        //echo $url;
                        $getPrice = safe_json_decode(file_get_contents($url));
                        $getPrice = str_replace(',', '.', $getPrice['price']);
                    }


                    /*if (!is_numeric($getPrice) || $getPrice < 0 || $getPrice == "" || is_null($getPrice))
                        continue;*/

                    $affectedRows = setRoutePrice($getRouteFetch[0]['id'], $vehicleType, $getPrice);
                    echo $n . $valueEnter['name'] . "-" . $valueExit['name'] . '-' . $vehicleType . ' Sınıf: ' . $getPrice;
                    if ($affectedRows > 0) {
                        $successCount++;
                        //echo $n . $valueEnter['name'] . '-' . $valueExit['name'] . " Success. Rows affected: $affectedRows" . $n;
                    } else {
                        echo $n . $valueEnter['name'] . '-' . $valueExit['name'] . "Query ran but nothing changed (maybe same values)." . $n;
                    }
                    //exit; // FOR TESTING PURPOSES ONLY.
                }
            }
        }
        echo $n . $successCount . ' rows successfully added/changed.' . $n;


        break;
    case 'otoyolas_add_update_tolls': // IT ADDS ROUTES OF SELECTED ROAD COMPANY BETWEEN TOLLS. IT USES GOOGLE API SO DONT USE IT UNNECCESARYLY.
        $getCompany = getCompany('otoyolas');

        if (!$getCompany || $getCompany['id'] == null) {
            echo $n . 'Please check the company added to the database with correct api url.' . $n;
            exit;
        }
        $getTolls = getTolls($getCompany['id']);
        $tolls = $getTolls['data'];
        $tollsCount = $getTolls['count'];
        $getTollsFromWebsite = getTollsFromOtoyolASWebsite();
        if ($tollsCount !== count($getTollsFromWebsite) && $_GET['force'] !== 1) {
            echo $n . 'Toll counts different from the website and the database. Please make sure tolls are updated at DB. You can find coordinates of the tolls at Google Maps.' . $n .
                $n . 'DB Results:' . $n . json_encode($tolls, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) .
                $n . 'Website Results:' . $n . json_encode($getTollsFromWebsite, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $n . 'To continue to update please put force=1 parameter to the url.' . $n;
            die;
        }
        $successCount = 0;
        foreach ($tolls as $keyEnter => $valueEnter) {
            foreach ($tolls as $keyExit => $valueExit) {
                if ($valueEnter['name'] == $valueExit['name'])
                    continue;
                //echo $n . $valueEnter['name'] . '-' . $valueExit['name'] . ' cannot found at toll_routes table. To add this use otoyolas_add_update_tolls' . $n;
                $getRouteResult = getTollRoute($valueEnter['id'], $valueExit['id']);
                $getRouteFetch = $getRouteResult['data'];
                $getRouteCount = $getRouteResult['count'];

                $coordinatesEnter = explode(',', str_replace(' ', '', $valueEnter['coordinates_enter']));
                $coordinatesExit = explode(',', str_replace(' ', '', $valueExit['coordinates_exit']));
                $origin = ['lat' => $coordinatesEnter[0], 'lng' => $coordinatesEnter[1]];
                $destination = ['lat' => $coordinatesExit[0], 'lng' => $coordinatesExit[1]];

                //print_r($origin);
                //print_r($destination);
                $result = getGoogleRoute($origin, $destination);

                if (isset($result['error'])) {
                    echo $n . "Error: " . $result['error'] . $n;
                    die();
                } else {
                    //echo "Polyline: " . $result['polyline'] . PHP_EOL;
                    //echo "Distance: " . $result['distance_meters'] . " meters" . PHP_EOL;
                    //echo "Duration: " . $result['duration'] . PHP_EOL;
                }
                $shortName = $valueEnter['short_name'] . "-" . $valueExit['short_name'];
                $decodedPolyline = decodeAndFormatPolyline($result['polyline']);
                $affectedRows = setRoute($valueEnter['id'], $valueExit['id'], $shortName, $result['polyline'], $decodedPolyline, $result['distance_meters'], $result['duration']);
                echo $n . $valueEnter['name'] . "-" . $valueExit['name'] . ': .' . $result['polyline'];
                if ($affectedRows > 0) {
                    $successCount++;
                    //echo $n . $valueEnter['name'] . '-' . $valueExit['name'] . " Success. Rows affected: $affectedRows" . $n;
                } else {
                    echo $n . $valueEnter['name'] . '-' . $valueExit['name'] . "Query ran but nothing changed (maybe same values)." . $n;
                }
                //die();


            }
        }
        echo $n . $successCount . " rows successfully added/changed" . $n;
        break;
        case 'update_fuel_prices':
            //Scrape the fuel prices from the website first
            $getFuelPrices = getFuelPrices();
            foreach($getFuelPrices as $key => $value){
                if($value == 0){ continue; }
                /*if($key == 'ElektrikAC'){
                    $key = 'Elektrikli (AC)';
                }elseif($key == 'ElektrikDC'){
                    $key = 'Elektrikli (DC)';
                }else{
                    $name = $key;
                }*/
                $result = setFuelPrices($key, $value);
                echo $n.$result." rows affected".$n;
            }
            
            break;
    default:
        echo $n . 'Unknown Action please put action= parameter on the url. Useful values: otoyolas_price_update,otoyolas_add_update_tolls,update_fuel_prices' . $n;
        die();
}