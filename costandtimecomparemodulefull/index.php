<?php
require_once 'load.php';
$GOOGLE_API_KEY = $_ENV['GOOGLE_API_KEY'];
// Fetch fuel types from DB
$fuel_types = $mysqli->prepare("SELECT * FROM fuel_types");
$fuel_types->execute();

$fuel_types = $fuel_types->get_result();
$fuel_types = $fuel_types->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otoyol AŞ - Ücretli Geçiş ve Ücretsiz Geçiş Karşılaştırma Maliyet Hesaplama</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Roboto', Arial, sans-serif;
            background: #e5e3df;
            font-size: 15px;
            overflow: hidden!important;
        }

        #map {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            height: 100vh;
            width: 100vw;
            z-index: 1;
            cursor: crosshair;
        }

        .map-overlay {
            position: absolute;
            z-index: 1002;
        }

        .map-panel {
            top: 2rem;
            left: 2rem;
            min-width: 320px;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2), 0 -1px 0px rgba(0, 0, 0, 0.02);
            transition: left 0.4s cubic-bezier(.4,0,.2,1), top 0.4s cubic-bezier(.4,0,.2,1), right 0.4s cubic-bezier(.4,0,.2,1);
        }
       

        .inputs-stack {
            position: relative;
        }

        .reverse-btn-stack {
            color: #5e5e5e;
            background: transparent;
            border: none;
            padding: 0 15px 0 0;
            font-size: 1.4em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 48px;
            margin: 0;
            width: auto;
        }

        .geolocate-btn {
            position: absolute;
            right: 8px;
            top: 7px;
            z-index: 10;
            color: #5e5e5e;
            background: transparent;
            border: none;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .origin-input-with-btn {
            padding-right: 40px !important;
            font-size: 15px;
            height: 48px;
            padding: 11px 16px 11px 24px;
            border-radius: 16px 16px 0px 0px;
        }

        .destination-input-with-btn {
            padding-right: 40px !important;
            font-size: 15px;
            height: 48px;
            padding: 11px 16px 11px 24px;
            border-radius: 16px 16px 0px 0px;
        }

        .route-btn {
            border-radius: 0 0 16px 16px;

        }

        .origin-input-with-btn:hover,
        .origin-input-with-btn:focus,
        .origin-input-with-btn:active,
        .destination-input-with-btn:hover,
        .destination-input-with-btn:focus,
        .destination-input-with-btn:active,
        #fuel-type-select:hover,
        #fuel-type-select:focus,
        #fuel-type-select:active,
        #fuel-consumption-input:hover,
        #fuel-consumption-input:focus,
        #fuel-consumption-input:active,
        #departure-time-input:hover,
        #departure-time-input:focus,
        #departure-time-input:active, .waypoint-input:hover, .waypoint-input:focus, .waypoint-input:active {
            box-shadow: none !important;
        }
        .fuel-consumption-input{
            box-shadow: none !important;
        }

        .map-fab {
            position: absolute;
            bottom: 2rem;
            right: 2rem;
            z-index: 1002;
        }

        .map-logo {
            position: absolute;
            left: 1rem;
            bottom: 1rem;
            z-index: 1002;
        }

        /* Route label (speech box) styles */
        .route-label {
            position: absolute;
            transform: translate(-50%, -100%);
            z-index: 2000;
            pointer-events: none;
            font-family: 'Roboto', Arial, sans-serif;
        }
        .route-label .bubble {
            background: #ffffff;
            color: #202124;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
            padding: 6px 10px;
            font-size: 12px;
            line-height: 1.2;
            border: 2px solid #e0e0e0;
            white-space: nowrap;
        }
        .route-label.toll .bubble { border-color: #1A73E8; }
        .route-label.notoll .bubble { border-color: #9AA0A6; }
        .route-label.active .bubble { border-color: #4632f8; }
        .route-label.inactive .bubble { border-color: #9AA0A6; }
        .route-label .pointer {
            width: 0; height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 10px solid #ffffff;
            margin: 0 auto;
            filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
        }

        /* Fix Google Autocomplete dropdown z-index */
        .pac-container {
            z-index: 3000 !important;
        }

        .fs-10 {
            font-size: 12px;
            color: #6c757d;
        }

        .fs-11b {
            font-size: 13px;
        }

        .fs-12 {
            font-size: 14px !important;
            color: rgb(47, 48, 48) !important;
        }

        .h-45px {
            height: 45px;
        }
        


        .hgs-car-classes {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }

        .hgs-class-btn {
            border-radius: 1rem;
            border: 2px solid #ddd;
            padding: 1rem 1.2rem;
            min-width: 120px;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #f8f9fa;
            transition: border-color 0.2s, background 0.2s;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            margin-bottom: 0.5rem;
        }

        .hgs-class-btn .hgs-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .btn-check:checked+.hgs-class-btn {
            border-color: #0d6efd;
            background: #e7f1ff;
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.08);
        }

        .hgs-class-btn .small {
            font-size: 0.85em;
            color: #555;
        }

        .hgs-class-btn .text-success {
            color: #198754;
        }

        .hgs-car-classes-row .hgs-class-btn {
            min-height: 80px;
            min-width: 60px;
            width: 100%;
            aspect-ratio: 1 / 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 0.85em;
            border-radius: 0 !important;
            border: 1px solid #ddd;
            margin: 0;
        }

        .hgs-car-classes-row .col-2 {
            flex: 0 0 16.6667%;
            max-width: 16.6667%;
            padding: 0 !important;
        }

        .hgs-class-btn .hgs-icon {
            font-size: 1.2rem;
            margin-bottom: 0.1rem;
        }

        .hgs-class-btn .fw-bold {
            font-size: 0.95em;
        }

        .hgs-class-btn .small {
            font-size: 0.9em;
            color: #555;
            line-height: 1.1;
        }

        .hgs-class-btn .text-success {
            color: #198754;
            font-size: 0.7em;
        }

        .p-05 {
            padding: 0.1rem !important;
        }
        .add-waypoint-btn{
            width: 100%;
            border-radius: 0 !important;
            border: 1px solid #ddd;
            border-top: none !important;
            padding: 0.3rem 1rem;
            font-size: 0.75em;
            color: #555;
        }
        .add-waypoint-btn:hover, .add-waypoint-btn:focus, .add-waypoint-btn:active{
            background: #f8f9fa;
        }
        .collapse-expand-btn{
            border-radius: 0 !important;
            border: 1px solid #ddd;
            
            padding: 0.3rem 0.3rem;
            font-size: 0.75em;
            color: #555;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0!important;
            top: calc(50% - 75px);
            height: 150px;
            pointer-events: auto!important;
        }
        .collapse-expand-btn:hover, .collapse-expand-btn:focus, .collapse-expand-btn:active, .collapse-expand-btn:focus-visible, .collapse-expand-btn:focus-within{
         
            background: #f8f9fa!important;
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            

        }
         .map-panel.closed{
               top: 2rem;
                left: -27rem;
                pointer-events: none;
            }
            .dp-none{
                display: none!important;
                
            }

        @media (max-width: 768px) {
            .map-panel {
                top: 0.5rem;
                left: 0;
                right: 0;
                width: 100vw;
                min-width: unset;
                max-width: unset;
                transform: none;
                padding: 0;
                box-sizing: border-box;
                transition: top 0.4s cubic-bezier(.4,0,.2,1), left 0.4s cubic-bezier(.4,0,.2,1), right 0.4s cubic-bezier(.4,0,.2,1);
            }
            .map-panel.closed{
                left: 0;
                right: 0;
                top: -23rem;
                pointer-events: none;
            }

            .map-fab {
                bottom: 1rem;
                right: 1rem;
            }

            .map-logo {
                left: 0.5rem;
                bottom: 0.5rem;
                font-size: 0.8rem;
            }
            .collapse-expand-btn{
            border-radius: 0 !important;
            border: 1px solid #ddd;
            border-top: none !important;
            padding: 0.3rem 0.3rem;
            font-size: 0.75em;
            color: #555;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px!important;
            left: calc(50% - 75px);
            bottom: calc(0% - 27px)!important;
            top: unset!important;
            height: fit-content!important;
            width: 150px!important;
            
        }
        .collapse-expand-btn:hover, .collapse-expand-btn:focus, .collapse-expand-btn:active, .collapse-expand-btn:focus-visible, .collapse-expand-btn:focus-within{
         border-top: 0px!important;
            background: #f8f9fa!important;
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            
            border-top: none !important;

        }


        }
        
        #waypoints-container .waypoint-input {
            border-radius: 0 !important;
            font-size: 15px;
            height: 48px;
            padding: 11px 16px 11px 24px;
        }
        #waypoints-container .remove-waypoint-btn {
            color: #dc3545;
            font-size: 2em;
            height: 48px;
            background: none;
            border: none;
            padding: 0 15px 0 0px;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div id="map"></div>
    <!-- Search/Route Panel at Top-Left -->
    <form class="map-overlay map-panel" id="search-form" onsubmit="return false;">
        <div class="inputs-stack mb-0">
            <!-- HGS Car Classes Selector -->


            <input id="origin-input" type="text" class="form-control border-0 border-bottom origin-input-with-btn" placeholder="Başlangıç noktası seçin" autocomplete="off">
            <button type="button" class="geolocate-btn" id="geolocate-btn" title="Use current location">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="geolocate-btn" viewBox="0 0 16 16">
                    <path d="M8.5.5a.5.5 0 0 0-1 0v.518A7 7 0 0 0 1.018 7.5H.5a.5.5 0 0 0 0 1h.518A7 7 0 0 0 7.5 14.982v.518a.5.5 0 0 0 1 0v-.518A7 7 0 0 0 14.982 8.5h.518a.5.5 0 0 0 0-1h-.518A7 7 0 0 0 8.5 1.018zm-6.48 7A6 6 0 0 1 7.5 2.02v.48a.5.5 0 0 0 1 0v-.48a6 6 0 0 1 5.48 5.48h-.48a.5.5 0 0 0 0 1h.48a6 6 0 0 1-5.48 5.48v-.48a.5.5 0 0 0-1 0v.48A6 6 0 0 1 2.02 8.5h.48a.5.5 0 0 0 0-1zM8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4" />
                </svg> </button>
            <div id="waypoints-container"></div>
            <button title="Ara durak Ekle" class="add-waypoint-btn" id="add-waypoint-btn" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                </svg> Ara Durak Ekle
            </button>
            <div class="input-group mb-0 border-bottom ">
                <input id="destination-input" type="text" class="form-control border-0 destination-input-with-btn" placeholder="Varış noktası seçin" autocomplete="off">
                <button type="button" class="reverse-btn-stack" id="reverse-btn" title="Reverse origin and destination">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-down-up" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.5 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L11 2.707V14.5a.5.5 0 0 0 .5.5m-7-14a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L4 13.293V1.5a.5.5 0 0 1 .5-.5" />
                    </svg>
                </button>
            </div>

            <div class="row hgs-car-classes-row mb-1 ms-0 me-0">
                <div class="col-2">
                    <input type="radio" class="btn-check" name="car-class" id="car-class-1" value="1" autocomplete="off" checked>
                    <label class="btn hgs-class-btn w-100" for="car-class-1">
                        <span class="hgs-icon">🚗</span>
                        <span class="fw-bold">1. Sınıf</span>
                        <div class="small">Otomobil<br><span class="text-success">2 aks &lt; 3.20m</span></div>
                    </label>
                </div>
                <div class="col-2">
                    <input type="radio" class="btn-check" name="car-class" id="car-class-2" value="2" autocomplete="off">
                    <label class="btn hgs-class-btn w-100" for="car-class-2">
                        <span class="hgs-icon">🚐</span>
                        <span class="fw-bold">2. Sınıf</span>
                        <div class="small">B. Minibüs<br><span class="text-success">2 aks ≥ 3.20m</span></div>
                    </label>
                </div>
                <div class="col-2">
                    <input type="radio" class="btn-check" name="car-class" id="car-class-3" value="3" autocomplete="off">
                    <label class="btn hgs-class-btn w-100" for="car-class-3">
                        <span class="hgs-icon">🚛</span>
                        <span class="fw-bold">3. Sınıf</span>
                        <div class="small">3 Akslı<br><span class="text-success">Aks: 3</span></div>
                    </label>
                </div>
                <div class="col-2">
                    <input type="radio" class="btn-check" name="car-class" id="car-class-4" value="4" autocomplete="off">
                    <label class="btn hgs-class-btn w-100" for="car-class-4">
                        <span class="hgs-icon">🚚</span>
                        <span class="fw-bold">4. Sınıf</span>
                        <div class="small">Çift Dorse<br><span class="text-success">Aks: 4-5</span></div>
                    </label>
                </div>
                <div class="col-2">
                    <input type="radio" class="btn-check" name="car-class" id="car-class-5" value="5" autocomplete="off">
                    <label class="btn hgs-class-btn w-100" for="car-class-5">
                        <span class="hgs-icon">🚚</span>
                        <span class="fw-bold">5. Sınıf</span>
                        <div class="small">Ağır Yük<br><span class="text-success">Aks: 6+</span></div>
                    </label>
                </div>
                <div class="col-2">
                    <input type="radio" class="btn-check" name="car-class" id="car-class-6" value="6" autocomplete="off">
                    <label class="btn hgs-class-btn w-100" for="car-class-6">
                        <span class="hgs-icon">🏍️</span>
                        <span class="fw-bold">6. Sınıf</span>
                        <div class="small">Motosiklet<br><span class="text-success">Motosiklet</span></div>
                    </label>
                </div>
            </div>

            <div class="row border-bottom">
                <label for="fuel-consumption-input" class="form-label fs-10 ms-4 mb-0 ">Ort. Yakıt Tüketimi (L/100km) | (kWh/100km)</label>
                <div class="input-group fs-1 ">
                    <select class="form-select border-0 fs-11b ps-4" id="fuel-type-select" style="max-width: 180px;">
                        <?php foreach ($fuel_types as $fuel): ?>
                            <option value="<?php echo htmlspecialchars($fuel['price']); ?>">
                                <?php echo htmlspecialchars($fuel['name']); ?> (<?php echo htmlspecialchars($fuel['price']); ?> ₺)
                            </option>
                        <?php endforeach; ?>
                        <option value="customYakıt">Özel Fiyat (Yakıt)</option>
                        <option value="customElektrik">Özel Fiyat (Elektrik)</option>
                    </select>
                    <input id="custom-fuel-price" type="text" class="form-control border-0 fuel-consumption-input fs-11b mt-2" placeholder="Fiyat (₺/L | kWh)" style="display:none; max-width: 90px;">
                    <input id="fuel-consumption-input" type="text" class="form-control border-0 fuel-consumption-input fs-11b mt-2" placeholder="Tüketim (L | kWh / 100km)" autocomplete="off">
                </div>
            </div>
            <div class="row g-2 align-items-end mb-0">
                <!-- <div class="col-8 border-bottom border-end">

                </div> -->
                <div class="col-12 border-bottom">
                    
                    <div class="input-group  d-flex align-items-center">
                    <label for="departure-time-input" class="form-label fs-10 mb-0 ms-3 flex-grow-1  ps-2">Kalkış Tarihi ve Saati: </label>
                        <input id="departure-time-input" type="datetime-local" class="form-control border-0 fs-11b departure-time-input" placeholder="Kalkış Tarihi ve Saati" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-primary h-45px w-100 route-btn" id="route-btn" type="submit" aria-label="Get Route">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-signpost-split" viewBox="0 0 16 16">
                <path d="M7 7V1.414a1 1 0 0 1 2 0V2h5a1 1 0 0 1 .8.4l.975 1.3a.5.5 0 0 1 0 .6L14.8 5.6a1 1 0 0 1-.8.4H9v10H7v-5H2a1 1 0 0 1-.8-.4L.225 9.3a.5.5 0 0 1 0-.6L1.2 7.4A1 1 0 0 1 2 7zm1 3V8H2l-.75 1L2 10zm0-5h6l.75-1L14 3H8z" />
            </svg>
            <span class="ms-2">Gişeli & Gişesiz Yol Tariflerini Karşılaştır</span>
        </button>
        <button class="btn collapse-expand-btn  d-xl-inline position-absolute" id="collapse-expand-btn" type="button" aria-label="">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-compact-left d-none d-md-inline d-lg-inline panel-toggle-icon" id="collapse-expand-btn-left" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M9.224 1.553a.5.5 0 0 1 .223.67L6.56 8l2.888 5.776a.5.5 0 1 1-.894.448l-3-6a.5.5 0 0 1 0-.448l3-6a.5.5 0 0 1 .67-.223" />
</svg>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-compact-up d-xs-inline d-sm-inline d-md-none d-lg-none d-xl-none panel-toggle-icon" id="collapse-expand-btn-up" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.776 5.553a.5.5 0 0 1 .448 0l6 3a.5.5 0 1 1-.448.894L8 6.56 2.224 9.447a.5.5 0 1 1-.448-.894z"/>
    </svg>
     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-compact-right d-none d-md-inline d-lg-inline panel-toggle-icon dp-none" id="collapse-expand-btn-right"  viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M6.776 1.553a.5.5 0 0 1 .671.223l3 6a.5.5 0 0 1 0 .448l-3 6a.5.5 0 1 1-.894-.448L9.44 8 6.553 2.224a.5.5 0 0 1 .223-.671"/>
</svg>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-compact-down d-xs-inline d-sm-inline d-md-none d-lg-none d-xl-none panel-toggle-icon dp-none" id="collapse-expand-btn-down"  viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M1.553 6.776a.5.5 0 0 1 .67-.223L8 9.44l5.776-2.888a.5.5 0 1 1 .448.894l-6 3a.5.5 0 0 1-.448 0l-6-3a.5.5 0 0 1-.223-.67"/>
    </svg>
        </button>
       
    </form>
   
  
    <!-- Logo -->
    <div class="map-logo bg-white rounded-3 shadow-sm px-2 py-1 small">
        Sadece Otoyol A.Ş. (İstanbul-İzmir O-5) gişe ücretleri hesaplanabilir. Bu bir staj projesidir. Doğruluklarından dolayı sorumluluk kabul edilmemektedir.
    </div>
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/3.3.4/jquery.inputmask.bundle.min.js"></script>
    <script>
        let map, originAutocomplete, destinationAutocomplete;
        let tollRouteOverlays = [], noTollRouteOverlays = [];
        let tollRouteLabels = [], noTollRouteLabels = [];
        let originPlace, destinationPlace;
        let waypointAutocompletes = [];
        let geocoder;
         // Global array to keep track of all map markers
         let mapMarkers = [];
        // Global array to keep track of all waypoint PlaceResult objects, in order
        let WaypointPlaces = [];
        let lastTollRoute = null, lastNoTollRoute = null;
        // Cache for label contents so costs persist across redraws
        let cachedTollTotals = { cost: null, fuel: null, distance: null, duration: null };
        let cachedNoTollTotals = { fuel: null, distance: null, duration: null };
        
        // Function to clear all routes and labels from the map
        function clearAllRoutesAndLabels() {
            console.log('Clearing all routes and labels...');
            
            // Clear route overlays
            if (Array.isArray(tollRouteOverlays) && tollRouteOverlays.length) {
                console.log('Clearing', tollRouteOverlays.length, 'toll route overlays');
                tollRouteOverlays.forEach(o => o && o.setMap && o.setMap(null));
                tollRouteOverlays = [];
            }
            if (Array.isArray(noTollRouteOverlays) && noTollRouteOverlays.length) {
                console.log('Clearing', noTollRouteOverlays.length, 'no-toll route overlays');
                noTollRouteOverlays.forEach(o => o && o.setMap && o.setMap(null));
                noTollRouteOverlays = [];
            }
            
            // Clear route labels
            if (Array.isArray(tollRouteLabels) && tollRouteLabels.length) {
                console.log('Clearing', tollRouteLabels.length, 'toll route labels');
                tollRouteLabels.forEach(el => {
                    if (el && typeof el._cleanup === 'function') {
                        el._cleanup();
                    } else if (el && el.remove) {
                        el.remove();
                    } else if (el && el.setMap) {
                        el.setMap(null);
                    }
                });
                tollRouteLabels = [];
            }
            if (Array.isArray(noTollRouteLabels) && noTollRouteLabels.length) {
                console.log('Clearing', noTollRouteLabels.length, 'no-toll route labels');
                noTollRouteLabels.forEach(el => {
                    if (el && typeof el._cleanup === 'function') {
                        el._cleanup();
                    } else if (el && el.remove) {
                        el.remove();
                    } else if (el && el.setMap) {
                        el.setMap(null);
                    }
                });
                noTollRouteLabels = [];
            }
            
            // Hide route legend
            if (typeof window.hideRouteLegend === 'function') {
                window.hideRouteLegend();
            }
            
            console.log('Routes and labels cleared successfully');
        }
        // Helper: load Google Maps script dynamically with callback
        function loadGoogleMaps(lat, lng) {
            window.initMap = function() {
                map = new google.maps.Map(document.getElementById('map'), {
                    center: {
                        lat: lat,
                        lng: lng
                    },
                    zoom: 10,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false
                });
                // Create autocomplete instances with location bias
                const userLocation = new google.maps.LatLng(lat, lng);
                console.log('Setting location bias for autocomplete:', { lat, lng });
                
                originAutocomplete = new google.maps.places.Autocomplete(
                    document.getElementById('origin-input'), {
                        fields: ["place_id", "geometry", "name", "formatted_address"],
                        //componentRestrictions: { country: 'tr' } // Use it only if you want to restrict the autocomplete to a specific country
                    }
                );
                // Set location bias after creation
                originAutocomplete.setBounds(new google.maps.LatLngBounds(userLocation, userLocation));
                
                destinationAutocomplete = new google.maps.places.Autocomplete(
                    document.getElementById('destination-input'), {
                        fields: ["place_id", "geometry", "name", "formatted_address"],
                        //componentRestrictions: { country: 'tr' } // Use it only if you want to restrict the autocomplete to a specific country
                    }
                );
                // Set location bias after creation
                destinationAutocomplete.setBounds(new google.maps.LatLngBounds(userLocation, userLocation));
                originAutocomplete.addListener('place_changed', function() {
                    originPlace = originAutocomplete.getPlace();
                    fitMapToAllPoints(map, originPlace, destinationPlace, WaypointPlaces);
                });
                destinationAutocomplete.addListener('place_changed', function() {
                    destinationPlace = destinationAutocomplete.getPlace();
                    fitMapToAllPoints(map, originPlace, destinationPlace, WaypointPlaces);
                });
                
                // Prevent form submission on Enter key for autocomplete inputs
                document.getElementById('origin-input').addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                    }
                });
                document.getElementById('destination-input').addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                    }
                });
                
                // Handle input clearing for origin and destination
                document.getElementById('origin-input').addEventListener('input', function(e) {
                    if (e.target.value === '') {
                        originPlace = null;
                        fitMapToAllPoints(map, originPlace, destinationPlace, WaypointPlaces);
                    } else {
                        // Check if input contains coordinates
                        checkForCoordinates(e.target.value, 'origin');
                    }
                    // Clear all routes and labels when input changes
                    clearAllRoutesAndLabels();
                });
                document.getElementById('destination-input').addEventListener('input', function(e) {
                    if (e.target.value === '') {
                        destinationPlace = null;
                        fitMapToAllPoints(map, originPlace, destinationPlace, WaypointPlaces);
                    } else {
                        // Check if input contains coordinates
                        checkForCoordinates(e.target.value, 'destination');
                    }
                    // Clear all routes and labels when input changes
                    clearAllRoutesAndLabels();
                });
                
                // Add click to select all functionality for origin and destination inputs
                document.getElementById('origin-input').addEventListener('click', function(e) {
                    if (e.target.value && e.target.value.trim() !== '') {
                        e.target.select();
                    }
                });
                document.getElementById('destination-input').addEventListener('click', function(e) {
                    if (e.target.value && e.target.value.trim() !== '') {
                        e.target.select();
                    }
                });
                geocoder = new google.maps.Geocoder();
                
                // Long-press (hold) to select coordinates on map
                let holdTimerId = null;
                let holdLatLng = null;
                const HOLD_MS = 700;
                function clearHoldTimer(){ if(holdTimerId){ clearTimeout(holdTimerId); holdTimerId = null; } }
                function startHold(event){
                    if(!event || !event.latLng) return;
                    holdLatLng = event.latLng;
                    clearHoldTimer();
                    holdTimerId = setTimeout(function(){
                        const lat = holdLatLng.lat();
                        const lng = holdLatLng.lng();
                        const coordinates = `${lat}, ${lng}`;
                        console.log('Map long-pressed at coordinates:', coordinates);
                        clearAllRoutesAndLabels();
                        const targetInput = determineTargetInput();
                        if (targetInput) {
                            targetInput.value = coordinates;
                            targetInput.dispatchEvent(new Event('input'));
                            showCoordinateFeedback(holdLatLng);
                        }
                    }, HOLD_MS);
                }
                function cancelHold(){ clearHoldTimer(); }
                map.addListener('mousedown', startHold);
                map.addListener('mouseup', cancelHold);
                map.addListener('dragstart', cancelHold);
                map.addListener('touchstart', startHold);
                map.addListener('touchend', cancelHold);
                map.addListener('touchmove', cancelHold);
                
                // Initialize existing waypoint autocompletes if any
                updateWaypointAutocompletes(lat, lng);
                
                // Legend removed per requirement
            };
            // Remove any previous script
            const oldScript = document.getElementById('gmaps-script');
            if (oldScript) oldScript.remove();
            const script = document.createElement('script');
            script.id = 'gmaps-script';
            script.src = "https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($GOOGLE_API_KEY); ?>&libraries=places&callback=initMap";
            script.async = true;
            script.defer = true;
            document.body.appendChild(script);
        }
        // Use IP-based geolocation
        function fetchIpLocation() {
            return fetch('https://ipapi.co/json/')
                .then(r => r.json())
                .then(data => {
                    if (data && data.latitude && data.longitude) {
                        return {
                            lat: data.latitude,
                            lng: data.longitude
                        };
                    } else {
                        return {
                            lat: 41.0082,
                            lng: 28.9784
                        }; // Istanbul default
                    }
                })
                .catch(() => ({
                    lat: 41.0082,
                    lng: 28.9784
                }));
        }
        // On page load, check geolocation permission
        window.addEventListener('DOMContentLoaded', function() {
            if (navigator.permissions) {
                navigator.permissions.query({
                    name: 'geolocation'
                }).then(function(result) {
                    if (result.state === 'granted') {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            console.log(position);
                            loadGoogleMaps(position.coords.latitude, position.coords.longitude);
                        }, function() {
                            // fallback to IP if error
                            fetchIpLocation().then(({
                                lat,
                                lng
                            }) => loadGoogleMaps(lat, lng));
                        });
                    } else {
                        // Not granted: use IP-based geolocation
                        fetchIpLocation().then(({
                            lat,
                            lng
                        }) => loadGoogleMaps(lat, lng));
                    }
                }).catch(function() {
                    // Permissions API not available: fallback to IP
                    fetchIpLocation().then(({
                        lat,
                        lng
                    }) => loadGoogleMaps(lat, lng));
                });
            } else {
                // Permissions API not available: fallback to IP
                fetchIpLocation().then(({
                    lat,
                    lng
                }) => loadGoogleMaps(lat, lng));
            }
        });


        document.getElementById('reverse-btn').onclick = function() {
            // Clear all routes and labels before reversing
            clearAllRoutesAndLabels();
            
            // Swap input values
            const originInput = document.getElementById('origin-input');
            const destinationInput = document.getElementById('destination-input');
            const tempValue = originInput.value;
            originInput.value = destinationInput.value;
            destinationInput.value = tempValue;
            // Swap autocomplete places if available
            const tempPlace = originPlace;
            originPlace = destinationPlace;
            destinationPlace = tempPlace;

            // Reverse waypoint input values in the DOM
            const waypointInputs = Array.from(document.querySelectorAll('.waypoint-input'));
            const waypointValues = waypointInputs.map(input => input.value);
            const waypointDatas = waypointInputs.map(input => input.dataset.place);
            // Reverse values and data-place attributes
            waypointInputs.forEach((input, idx) => {
                const revIdx = waypointInputs.length - 1 - idx;
                input.value = waypointValues[revIdx];
                if (waypointDatas[revIdx] !== undefined) {
                    input.dataset.place = waypointDatas[revIdx];
                } else {
                    delete input.dataset.place;
                }
            });
            // Reverse WaypointPlaces array
            if (Array.isArray(WaypointPlaces)) {
                WaypointPlaces.reverse();
            }
            // Reverse waypointAutocompletes array if it exists
            if (Array.isArray(waypointAutocompletes)) {
                waypointAutocompletes.reverse();
            }
            fitMapToAllPoints(map, originPlace, destinationPlace, WaypointPlaces);
        };
        
        
        // Only the browser's Geolocation API can get the user's real location. Places/Routes API cannot do this.
        document.getElementById('geolocate-btn').onclick = function(e) {
           
            e.preventDefault();
            // Clear all routes and labels before geolocating
            clearAllRoutesAndLabels();
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const latlng = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    console.log(latlng);
                    geocoder.geocode({
                        location: latlng
                    }, function(results, status) {
                        //console.log(results);
                        //console.log(status);
                        if (status === 'OK' && results[0]) {
                            document.getElementById('origin-input').value = "Konumunuz";
                            // Set originPlace to a mock PlaceResult for routing
                            originPlace = {
                                geometry: {
                                    location: {
                                        lat: () => latlng.lat,
                                        lng: () => latlng.lng
                                    }
                                },
                                name: 'Konumunuz',
                                formatted_address: results[0].formatted_address
                            };
                           

                            // Fit map to all points after setting originPlace from geolocation
                            fitMapToAllPoints(map, originPlace, destinationPlace, WaypointPlaces);
                    
                        } else {
                            alert('Konumunuz bulunamadı.');
                        }
                    });
                }, function(error) {
                    if (error.code === error.PERMISSION_DENIED) {
                        alert('Konum erişimi engellendi. Lütfen tarayıcı ayarlarınızdan konum erişimini etkinleştiriniz.');
                    } else {
                        alert('Konum arızası: ' + error.message);
                    }
                });
            } else {
                alert('Konum bilgisi alınamadı. Tarayıcınız bunu desteklemiyor olabilir.');
            }
        };
        // Helper: geocode an address string to a PlaceResult-like object
        function geocodeAddress(address) {
            return new Promise((resolve, reject) => {
                geocoder.geocode({
                    address: address
                }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        resolve({
                            geometry: {
                                location: {
                                    lat: () => results[0].geometry.location.lat(),
                                    lng: () => results[0].geometry.location.lng()
                                }
                            },
                            name: results[0].formatted_address,
                            formatted_address: results[0].formatted_address
                        });
                    } else {
                        reject('Bu adres bulunamadı: ' + address);
                    }
                });
            });
        }

        // Waypoint logic   
        function updateWaypointAutocompletes(lat, lng) {
            waypointAutocompletes = [];
            // Update WaypointPlaces to match the order and values of .waypoint-inputs
            WaypointPlaces = [];
            const waypointInputs = document.querySelectorAll('.waypoint-input');
            // Create waypoint autocompletes with location bias
            const userLocation = lat && lng ? new google.maps.LatLng(lat, lng) : null;
            waypointInputs.forEach((input, idx) => {
                const autocomplete = new google.maps.places.Autocomplete(input, {
                    fields: ["place_id", "geometry", "name", "formatted_address"],
                    //componentRestrictions: { country: 'tr' } // Use it only if you want to restrict the autocomplete to a specific country
                });
                // Set location bias after creation if we have user location
                if (userLocation) {
                    autocomplete.setBounds(new google.maps.LatLngBounds(userLocation, userLocation));
                }
                autocomplete.addListener('place_changed', function() {
                    input.dataset.place = JSON.stringify(autocomplete.getPlace());
                    // Update WaypointPlaces immediately when a place changes
                    WaypointPlaces = Array.from(document.querySelectorAll('.waypoint-input')).map(inp => {
                        try {
                            return inp.dataset.place ? JSON.parse(inp.dataset.place) : null;
                        } catch (e) {
                            return null;
                        }
                    }).filter(Boolean);
                    fitMapToAllPoints(map, originPlace, destinationPlace, WaypointPlaces);
                });
                
                // Prevent form submission on Enter key for waypoint inputs
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                    }
                });
                
                // Handle waypoint input clearing
                input.addEventListener('input', function(e) {
                    if (e.target.value === '') {
                        // Remove this waypoint from WaypointPlaces
                        const inputIndex = Array.from(document.querySelectorAll('.waypoint-input')).indexOf(input);
                        if (inputIndex >= 0 && WaypointPlaces[inputIndex]) {
                            WaypointPlaces[inputIndex] = null;
                            // Clean up null entries
                            WaypointPlaces = WaypointPlaces.filter(Boolean);
                        }
                        fitMapToAllPoints(map, originPlace, destinationPlace, WaypointPlaces);
                    } else {
                        // Check if input contains coordinates
                        checkForCoordinates(e.target.value, 'waypoint', input);
                    }
                });
                // On init, also try to populate WaypointPlaces
                if (input.dataset.place) {
                    try {
                        const place = JSON.parse(input.dataset.place);
                        if (place) WaypointPlaces.push(place);
                    } catch (e) {}
                }
                waypointAutocompletes.push(autocomplete);
            });
            fitMapToAllPoints(map, originPlace, destinationPlace, WaypointPlaces);
        }
        document.getElementById('add-waypoint-btn').onclick = function(e) {
            e.preventDefault();
            const container = document.getElementById('waypoints-container');
            const idx = container.children.length;
            if (idx >= 2) return; // Prevent adding more than 2 waypoints
            const waypointNumber = idx + 1;
            const div = document.createElement('div');
            div.className = 'input-group mb-0 waypoint-group border-bottom';
            div.innerHTML = `
                <input type="text" class="form-control border-0 border-radius-0 waypoint-input" id="waypoint-input-${waypointNumber}" name="waypoint${waypointNumber}" placeholder="Ara durak adresi" autocomplete="off" >
                <button type="button" class="btn btn-link text-danger remove-waypoint-btn" tabindex="-1" title="Ara durak kaldır">&times;</button>
            `;
            container.appendChild(div);
            
            // Add click to select all functionality for the new waypoint input
            const waypointInput = div.querySelector('.waypoint-input');
            waypointInput.addEventListener('click', function(e) {
                if (e.target.value && e.target.value.trim() !== '') {
                    e.target.select();
                }
            });
            
            // Add input change listener to clear routes and labels
            waypointInput.addEventListener('input', function(e) {
                clearAllRoutesAndLabels();
            });
            div.querySelector('.remove-waypoint-btn').onclick = function() {
                div.remove();
                // Renumber remaining waypoint inputs
                const waypointInputs = container.querySelectorAll('.waypoint-input');
                waypointInputs.forEach((input, i) => {
                    input.id = `waypoint-input-${i+1}`;
                    input.name = `waypoint${i+1}`;
                });
                updateWaypointAutocompletes(map.getCenter().lat(), map.getCenter().lng());
                // Show add button if less than 2 waypoints
                if (container.children.length < 2) {
                    document.getElementById('add-waypoint-btn').style.display = '';
                }
            };
            updateWaypointAutocompletes(map.getCenter().lat(), map.getCenter().lng());
            // Hide add button if 2 waypoints
            if (container.children.length >= 2) {
                document.getElementById('add-waypoint-btn').style.display = 'none';
            }
        };
        // On page load, ensure add-waypoint-btn is visible if less than 2 waypoints
        window.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('waypoints-container');
            if (container && container.children.length < 2) {
                document.getElementById('add-waypoint-btn').style.display = '';
            } else if (container && container.children.length >= 2) {
                document.getElementById('add-waypoint-btn').style.display = 'none';
            }
            
            // Add click to select all functionality for existing waypoint inputs
            const existingWaypointInputs = document.querySelectorAll('.waypoint-input');
            existingWaypointInputs.forEach(input => {
                input.addEventListener('click', function(e) {
                    if (e.target.value && e.target.value.trim() !== '') {
                        e.target.select();
                    }
                });
                
                // Add input change listener to clear routes and labels
                input.addEventListener('input', function(e) {
                    clearAllRoutesAndLabels();
                });
            });
        });
       
        // Function to determine which input field should receive coordinates
        function determineTargetInput() {
            const originInput = document.getElementById('origin-input');
            const destinationInput = document.getElementById('destination-input');
            const waypointInputs = document.querySelectorAll('.waypoint-input');
            
            // Check if any input is currently focused
            const focusedElement = document.activeElement;
            
            // If an input is focused, use that one
            if (focusedElement && (focusedElement === originInput || 
                                 focusedElement === destinationInput || 
                                 Array.from(waypointInputs).includes(focusedElement))) {
                return focusedElement;
            }
            
            // Priority order: origin → waypoints → destination
            // Check origin first
            if (!originInput.value.trim()) {
                return originInput;
            }
            
            // Check waypoints (in order)
            for (let i = 0; i < waypointInputs.length; i++) {
                if (!waypointInputs[i].value.trim()) {
                    return waypointInputs[i];
                }
            }
            
            // Check destination
            if (!destinationInput.value.trim()) {
                return destinationInput;
            }
            
            // If all are filled, default to origin (replace it)
            return null;
        }
        
        // Function to show visual feedback when coordinates are selected
        function showCoordinateFeedback(latLng) {
            console.log(latLng.lat + " " + latLng.lng);
            // Create a temporary marker to show the selected point
            const tempMarker = new google.maps.Marker({
                position: {
                    lat: latLng.lat(),
                    lng: latLng.lng()
                },
                map: map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: '#4285F4',
                    fillOpacity: 1,
                    strokeColor: '#FFFFFF',
                    strokeWeight: 2
                },
                title: 'Seçilen koordinatlar'
            });
            
            // Remove the temporary marker after 2 seconds
            setTimeout(() => {
                tempMarker.setMap(null);
            }, 2000);
        }
        
        // Function to check if input contains coordinates and handle them
        function checkForCoordinates(inputValue, type, inputElement = null) {
            // Regex to match coordinate patterns
            const coordPatterns = [
                // Pattern 1: lat, lng (e.g., "40.2751488, 29.0553856")
                /^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/,
                // Pattern 2: lat,lng (no spaces)
                /^(-?\d+\.?\d*),(-?\d+\.?\d*)$/,
                // Pattern 3: lat lng (space separated)
                /^(-?\d+\.?\d*)\s+(-?\d+\.?\d*)$/
            ];
            
            for (let pattern of coordPatterns) {
                const match = inputValue.match(pattern);
                if (match) {
                    const lat = parseFloat(match[1]);
                    const lng = parseFloat(match[2]);
                    
                    // Validate coordinates
                    if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                        console.log(`Coordinates detected for ${type}:`, { lat, lng });
                        
                        // Create a place object from coordinates
                        const coordPlace = {
                            geometry: {
                                location: {
                                    lat: () => lat,
                                    lng: () => lng
                                }
                            },
                            name: `${lat}, ${lng}`,
                            formatted_address: `${lat}, ${lng}`
                        };
                        
                        // Update the appropriate place variable
                        if (type === 'origin') {
                            originPlace = coordPlace;
                            document.getElementById('origin-input').value = `${lat}, ${lng}`;
                        } else if (type === 'destination') {
                            destinationPlace = coordPlace;
                            document.getElementById('destination-input').value = `${lat}, ${lng}`;
                        } else if (type === 'waypoint' && inputElement) {
                            // Find the waypoint index
                            const waypointInputs = Array.from(document.querySelectorAll('.waypoint-input'));
                            const waypointIndex = waypointInputs.indexOf(inputElement);
                            if (waypointIndex >= 0) {
                                // Update WaypointPlaces array
                                WaypointPlaces[waypointIndex] = coordPlace;
                                inputElement.dataset.place = JSON.stringify(coordPlace);
                                inputElement.value = `${lat}, ${lng}`;
                            }
                        }
                        
                        // Update map
                        fitMapToAllPoints(map, originPlace, destinationPlace, WaypointPlaces);
                        return true;
                    }
                }
            }
            return false;
        }
        
        // Place this function at a global scope for reuse
        function fitMapToAllPoints(map, originPlace, destinationPlace, waypoints) {
            const bounds = new google.maps.LatLngBounds();
            // Clear all existing markers
            mapMarkers.forEach(marker => marker.setMap(null));
            mapMarkers = [];
            let labelIndex = 0;
            // Origin marker: always 'A'
            if (originPlace && originPlace.geometry && originPlace.geometry.location) {
                const originLatLng = {
                    lat: originPlace.geometry.location.lat(),
                    lng: originPlace.geometry.location.lng()
                };
                bounds.extend(originLatLng);
                const marker = new google.maps.Marker({
                    position: originLatLng,
                    label: String.fromCharCode(65 + labelIndex), // 'A'
                    map: map,
                    title: originPlace.name
                });
                mapMarkers.push(marker);
                labelIndex++;
            }
            //console.log(waypoints);
            // Waypoints: 'B', 'C', ...
            if (waypoints && waypoints.length) {
                waypoints.forEach((wp, i) => {
                    //console.log(wp);
                    if (wp && wp.geometry && wp.geometry.location) {
                        // Handle both Google Maps objects and parsed JSON objects
                        let wpLat, wpLng;
                        if (typeof wp.geometry.location.lat === 'function') {
                            // Google Maps object
                            wpLat = wp.geometry.location.lat();
                            wpLng = wp.geometry.location.lng();
                        } else {
                            // Parsed JSON object
                            wpLat = wp.geometry.location.lat;
                            wpLng = wp.geometry.location.lng;
                        }
                        
                        const wpLatLng = {
                            lat: wpLat,
                            lng: wpLng
                        };
                        bounds.extend(wpLatLng);
                        const marker = new google.maps.Marker({
                            position: wpLatLng,
                            label: String.fromCharCode(65 + labelIndex),
                            map: map,
                            title: wp.name
                        });
                        mapMarkers.push(marker);
                        labelIndex++;
                    }
                });
            }
            // Destination: next letter after last waypoint (or 'B' if no waypoints)
            if (destinationPlace && destinationPlace.geometry && destinationPlace.geometry.location) {
                const destLatLng = {
                    lat: destinationPlace.geometry.location.lat(),
                    lng: destinationPlace.geometry.location.lng()
                };
                bounds.extend(destLatLng);
                const marker = new google.maps.Marker({
                    position: destLatLng,
                    label: String.fromCharCode(65 + labelIndex),
                    map: map,
                    title: destinationPlace.name
                });
                mapMarkers.push(marker);
            }
            if (
    (originPlace && originPlace.geometry && originPlace.geometry.location) ||
    (destinationPlace && destinationPlace.geometry && destinationPlace.geometry.location) ||
    (WaypointPlaces && WaypointPlaces.length > 0)
) {
            // Set padding based on device type
            let padding;
            if (window.innerWidth > 768) {
                // Desktop: left panel, more left padding
                padding = { top: 50, left: 500, bottom: 50, right: 50 };
            } else {
                // Mobile: top panel, more top padding
                padding = { top: 450, left: 30, bottom: 30, right: 30 };
            }
            map.fitBounds(bounds, padding);
            // Ensure zoom is at least 14
            const listener = google.maps.event.addListenerOnce(map, 'bounds_changed', function() {
                if (map.getZoom() > 14) {
                    map.setZoom(14);
                }
            });
        }
        }
        // Helper to extract lat/lng from a place object
        function extractLatLng(place) {
            if (!place || !place.geometry || !place.geometry.location) return null;
            let lat, lng;
            if (typeof place.geometry.location.lat === 'function') {
                lat = place.geometry.location.lat();
                lng = place.geometry.location.lng();
            } else {
                lat = place.geometry.location.lat;
                lng = place.geometry.location.lng;
            }
            return { latitude: lat, longitude: lng };
        }

        // Helper to build route request
        function buildRouteRequest(origin, destination, waypoints, avoidTolls, departureTimeString) {
            const req = {
                origin: { location: { latLng: extractLatLng(origin) } },
                destination: { location: { latLng: extractLatLng(destination) } },
                travelMode: 'DRIVE',
                routingPreference: 'TRAFFIC_AWARE_OPTIMAL',
                departureTime: departureTimeString,
                polylineEncoding: 'ENCODED_POLYLINE',
                polylineQuality: 'HIGH_QUALITY',
                extraComputations: ['TRAFFIC_ON_POLYLINE'],
                routeModifiers: {
                    avoidFerries: true,
                    avoidTolls: !!avoidTolls,
                    avoidHighways: false
                }
            };
            if (waypoints.length > 0) req.intermediates = waypoints;
            return req;
        }

        // Helper to parse duration and distance
        function parseRouteDetails(route) {
            let duration = '', distance = '', durationSec = 0, staticDurationSec = 0, distanceMeters = 0;
            if (route) {
                durationSec = parseInt(String(route.duration || '0s').replace('s', '')) || 0;
                if (route.staticDuration) {
                    staticDurationSec = parseInt(String(route.staticDuration).replace('s', '')) || durationSec;
                } else {
                    // If staticDuration is not provided by API, default to duration (no traffic multiplier)
                    staticDurationSec = durationSec;
                }
                const h = Math.floor(durationSec / 3600);
                const m = Math.floor((durationSec % 3600) / 60);
                const s = durationSec % 60;
                if (h > 0) duration = `${h} sa ${m} dk`;
                else if (m > 0) duration = `${m} dk`;
                else duration = `${s} sn`;
                distanceMeters = parseInt(route.distanceMeters || 0) || 0;
                distance = distanceMeters > 1000 ? (distanceMeters / 1000).toFixed(2) + ' km' : distanceMeters + ' m';
            }
            return { duration, distance, durationSec, staticDurationSec, distanceMeters };
        }

        // Price/consumption helpers
        function parseTurkishNumber(str) {
            if (typeof str !== 'string') return NaN;
            const cleaned = str.trim().replace(/\s+/g, '').replace(/\./g, '').replace(/,/g, '.').replace(/[^0-9.\-]/g, '');
            return parseFloat(cleaned);
        }
        // Locale-aware generic number parser (keeps dot decimals in DB labels like '52.29 ₺')
        function parseLocaleNumber(str) {
            if (typeof str !== 'string') return NaN;
            let s = str.trim().replace(/[^0-9.,\-]/g, '');
            const hasComma = s.indexOf(',') !== -1;
            const hasDot = s.indexOf('.') !== -1;
            if (hasComma && hasDot) {
                // Decide decimal by last occurring separator
                const lastComma = s.lastIndexOf(',');
                const lastDot = s.lastIndexOf('.');
                const decimalSep = lastDot > lastComma ? '.' : ',';
                const thousandSep = decimalSep === '.' ? ',' : '.';
                s = s.split(thousandSep).join('');
                if (decimalSep === ',') s = s.replace(',', '.');
            } else if (hasComma && !hasDot) {
                s = s.replace(',', '.');
            } else {
                // only dot or only digits: leave as is
            }
            return parseFloat(s);
        }
        // Extract the first numeric token from a string (e.g., '2 L/100km' => 2)
        function extractFirstNumber(str) {
            if (typeof str !== 'string') return NaN;
            const m = str.match(/[-+]?\d+(?:[.,]\d+)?/);
            if (!m) return NaN;
            return parseLocaleNumber(m[0]);
        }
        function getSelectedFuelPrice() {
            const fuelTypeEl = document.getElementById('fuel-type-select');
            const customPriceEl = document.getElementById('custom-fuel-price');
            if (!fuelTypeEl) return NaN;
            const val = fuelTypeEl.value;
            if (val === 'customYakıt' || val === 'customElektrik') {
                return parseLocaleNumber(customPriceEl ? customPriceEl.value : '');
            }
            // Prefer the option value as price (user switched it to price)
            const fromValue = parseLocaleNumber(val);
            if (isFinite(fromValue) && fromValue > 0) {
                return fromValue;
            }
            // Fallback to parsing inside parentheses in the label
            const txt = fuelTypeEl.options[fuelTypeEl.selectedIndex]?.text || '';
            const m = txt.match(/\(([^)]+)\)/);
            const parsed = m ? parseLocaleNumber(m[1]) : NaN;
            // Sanity: expected fuel price per L/kWh should be reasonable (0.1 - 200)
            if (!isFinite(parsed) || parsed <= 0 || parsed > 200) {
                console.warn('[fuel] unreasonable fuel price from DB', { optionText: txt, parsed });
                return NaN;
            }
            return parsed;
        }
        function getConsumptionPer100() {
            const el = document.getElementById('fuel-consumption-input');
            const raw = el ? el.value : '';
            const parsed = extractFirstNumber(raw);
            console.log('[fuel] consumption parse', { raw, parsed });
            return parsed;
        }
        function formatTRY(n) {
            const num = typeof n === 'number' && isFinite(n) ? n : 0;
            return num.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        function computeFuelCost(distanceMeters, durationSec, staticDurationSec) {
            const km = (distanceMeters || 0) / 1000;
            let consumption = getConsumptionPer100();
            let unitPrice = getSelectedFuelPrice();
            if (!isFinite(km) || km <= 0) { console.log('[fuel] invalid km', { distanceMeters, km }); return 0; }
            if (!isFinite(consumption)) { console.log('[fuel] invalid consumption', { consumption }); return 0; }
            if (!isFinite(unitPrice)) { console.log('[fuel] invalid unitPrice', { unitPrice }); return 0; }
            // Sanity clamps
            const raw = { rawKm: km, rawConsumption: consumption, rawUnitPrice: unitPrice };
            consumption = Math.min(Math.max(consumption, 0.1), 100); // 0.1 - 100 per 100km
            unitPrice = Math.min(Math.max(unitPrice, 0.1), 200); // ₺ per L/kWh (tight clamp)
            const safeKm = Math.min(Math.max(km, 0), 5000); // cap 5,000 km

            const litersOrKwh = (consumption / 100) * safeKm;
            let baseCost = litersOrKwh * unitPrice;

            let tf = null;
            if (isFinite(durationSec) && isFinite(staticDurationSec) && staticDurationSec > 0 && durationSec > 0) {
               console.log('duration', { durationSec },'staticDurationSec', { staticDurationSec }); 
                tf = durationSec / staticDurationSec;
                if (tf >= 0.5 && tf <= 3) baseCost *= tf;
            }

            const logObj = {
                km: safeKm,
                consumptionPer100: consumption,
                unitPrice,
                litersOrKwh,
                durationSec,
                staticDurationSec,
                trafficFactor: tf,
                costBeforeClamp: baseCost
            };
            if (!isFinite(baseCost) || baseCost < 0) { console.log('[fuel] invalid baseCost', logObj, raw); return 0; }
            const finalCost = Math.min(baseCost, 100000);
            console.log('[fuel] calc', logObj, raw, { finalCost });
            return finalCost;
        }
        function buildPaidOverlayHTML(distanceStr, durationStr, tollTotal, fuelCost) {
            const total = (tollTotal || 0) + (fuelCost || 0);
            return `Ücretli Yol<br>` +
                   `🛣️: ${distanceStr} • ⏱️: ${durationStr}<br>` +
                   `Gişe Ücret Top: ${formatTRY(tollTotal)} ₺<br>` +
                   `Tahmini Yakıt Ücreti: ${formatTRY(fuelCost)} ₺<br>` +
                   `Toplam: ${formatTRY(total)} ₺`;
        }
        function buildFreeOverlayHTML(distanceStr, durationStr, fuelCost) {
            const total = fuelCost || 0;
            return `Gişesiz Yol<br>` +
                   `🛣️: ${distanceStr} • ⏱️: ${durationStr}<br>` +
                   `Gişe Ücret Top: 0,00 ₺<br>` +
                   `Tahmini Yakıt Ücreti: ${formatTRY(fuelCost)} ₺<br>` +
                   `Toplam: ${formatTRY(total)} ₺`;
        }

        // Geometry helpers for toll matching
        function toRad(deg) { return deg * Math.PI / 180; }
        function distanceToSegmentMeters(lat, lng, lat1, lng1, lat2, lng2) {
            const R = 6371000.0;
            const meanLat = toRad((lat1 + lat2) / 2.0);
            const projX = (la, lo) => toRad(lo) * Math.cos(meanLat) * R;
            const projY = (la) => toRad(la) * R;
            const px = projX(lat, lng), py = projY(lat);
            const ax = projX(lat1, lng1), ay = projY(lat1);
            const bx = projX(lat2, lng2), by = projY(lat2);
            const vx = bx - ax, vy = by - ay;
            const wx = px - ax, wy = py - ay;
            const vv = vx*vx + vy*vy;
            let t = vv > 0 ? ((wx*vx + wy*vy) / vv) : 0.0;
            if (t < 0) t = 0; else if (t > 1) t = 1;
            const cx = ax + t*vx, cy = ay + t*vy;
            const dx = px - cx, dy = py - cy;
            return { dist: Math.sqrt(dx*dx + dy*dy), t };
        }
        function nearestDistanceOnPath(pathLatLngArray, lat, lng) {
            let minDist = Infinity, bestIndex = -1, bestT = 0;
            for (let i = 0; i < pathLatLngArray.length - 1; i++) {
                const a = pathLatLngArray[i];
                const b = pathLatLngArray[i+1];
                const { dist, t } = distanceToSegmentMeters(lat, lng, a.lat, a.lng, b.lat, b.lng);
                if (dist < minDist) { minDist = dist; bestIndex = i; bestT = t; }
            }
            const pathPos = bestIndex >= 0 ? (bestIndex + bestT) : -1;
            return { minDist, bestIndex, pathPos };
        }
        function buildTollLegsFromPolyline(pathLatLngArray, tolls, thresholdMeters = 10.0) {
            const events = [];
            const matched = [];
            (tolls || []).forEach(row => {
                const enter = row.enter, exit = row.exit;
                let enterInfo = null, exitInfo = null;
                if (enter && isFinite(enter.lat) && isFinite(enter.lng)) {
                    const r = nearestDistanceOnPath(pathLatLngArray, enter.lat, enter.lng);
                    enterInfo = { matched: r.minDist <= thresholdMeters, min_distance_meters: Math.round(r.minDist * 100) / 100, segment_index: r.bestIndex, path_pos: r.pathPos };
                    if (enterInfo.matched) events.push({ type: 'enter', path_pos: r.pathPos, toll_id: row.id });
                }
                if (exit && isFinite(exit.lat) && isFinite(exit.lng)) {
                    const r2 = nearestDistanceOnPath(pathLatLngArray, exit.lat, exit.lng);
                    exitInfo = { matched: r2.minDist <= thresholdMeters, min_distance_meters: Math.round(r2.minDist * 100) / 100, segment_index: r2.bestIndex, path_pos: r2.pathPos };
                    if (exitInfo.matched) events.push({ type: 'exit', path_pos: r2.pathPos, toll_id: row.id });
                }
                matched.push({ id: row.id, toll_id: row.toll_id, name: row.name, short_name: row.short_name, enter, exit, enter_match: enterInfo, exit_match: exitInfo });
            });
            events.sort((a,b)=> a.path_pos === b.path_pos ? 0 : (a.path_pos < b.path_pos ? -1 : 1));
            const legs = [];
            let currentEnter = null;
            for (const ev of events) {
                if (ev.type === 'enter' && currentEnter === null) {
                    currentEnter = ev.toll_id;
                } else if (ev.type === 'exit' && currentEnter !== null) {
                    legs.push({ enter_toll_id: currentEnter, exit_toll_id: ev.toll_id });
                    currentEnter = null;
                }
            }
            return { legs, matched };
        }
        async function fetchTollCosts(legs, vehicleType) {
            const res = await fetch('api/index.php?action=costs', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'costs', legs, vehicle_type: vehicleType })
            });
            return res.json();
        }

        document.getElementById('search-form').onsubmit = async function() {
            event.preventDefault();
            const originInput = document.getElementById('origin-input');
            const destinationInput = document.getElementById('destination-input');
            let originPromise = Promise.resolve(originPlace);
            if (!originPlace || !originPlace.geometry || !originPlace.geometry.location) {
                if (originInput.value.trim() !== '') {
                    originPromise = geocodeAddress(originInput.value.trim());
                }
            }
            let destinationPromise = Promise.resolve(destinationPlace);
            if (!destinationPlace || !destinationPlace.geometry || !destinationPlace.geometry.location) {
                if (destinationInput.value.trim() !== '') {
                    destinationPromise = geocodeAddress(destinationInput.value.trim());
                }
            }
            const waypointInputs = document.querySelectorAll('.waypoint-input');
            let waypointsPromises = Array.from(waypointInputs).map((input, index) => {
                let place = WaypointPlaces[index] || null;
                if (!place || !place.geometry || !place.geometry.location) {
                    try { place = input.dataset.place ? JSON.parse(input.dataset.place) : null; } catch (e) { place = null; }
                }
                if (!place || !place.geometry || !place.geometry.location) {
                    if (input.value.trim() !== '') {
                        return geocodeAddress(input.value.trim());
                    } else {
                        return null;
                    }
                }
                return Promise.resolve(place);
            });
            waypointsPromises = waypointsPromises.filter(p => p !== null);
            let waypoints = [];
            try {
                [originPlace, destinationPlace, ...waypoints] = await Promise.all([originPromise, destinationPromise, ...waypointsPromises]);
            } catch (err) {
                alert(err);
                return false;
            }
            if (!originPlace || !destinationPlace || !originPlace.geometry || !destinationPlace.geometry) {
                alert('Lütfen haritadan başlangıç ve bitiş koordinatlarını seçiniz.');
                return false;
            }
            // Validate consumption and optional custom price before proceeding
            const consumptionEl = document.getElementById('fuel-consumption-input');
            const fuelTypeEl = document.getElementById('fuel-type-select');
            const customPriceEl = document.getElementById('custom-fuel-price');

            const normalizeNumber = (val) => {
                if (typeof val !== 'string') return NaN;
                // remove spaces and group separators, replace comma with dot
                const cleaned = val.trim().replace(/\s+/g, '').replace(/\./g, '').replace(/,/g, '.');
                return parseFloat(cleaned);
            };

            const consumptionVal = normalizeNumber(consumptionEl ? consumptionEl.value : '');
            // Basic sanity range: 0.1 to 100 (L/100km or kWh/100km)
            if (!isFinite(consumptionVal) || consumptionVal <= 0 || consumptionVal < 0.1 || consumptionVal > 100) {
                alert('Lütfen mantıklı bir tüketim değeri giriniz. (0.1 - 100)');
                if (consumptionEl) consumptionEl.focus();
                return false;
            }

            const fuelTypeVal = fuelTypeEl ? fuelTypeEl.value : '';
            const isCustomPrice = fuelTypeVal === 'customYakıt' || fuelTypeVal === 'customElektrik';
            if (isCustomPrice) {
                const customPriceVal = normalizeNumber(customPriceEl ? customPriceEl.value : '');
                // Price sanity range: 0.1 to 10000 (₺/L or ₺/kWh)
                if (!isFinite(customPriceVal) || customPriceVal <= 0 || customPriceVal < 0.1 || customPriceVal > 10000) {
                    alert('Özel fiyat seçildi. Lütfen mantıklı bir fiyat giriniz. (0.1 - 10000)');
                    if (customPriceEl) customPriceEl.focus();
                    return false;
                }
            }
        
            fitMapToAllPoints(map, originPlace, destinationPlace, waypoints);
            clearAllRoutesAndLabels();
            const departureTimeInput = document.getElementById('departure-time-input');
            const departureTime = departureTimeInput ? new Date(departureTimeInput.value) : new Date();
            const departureTimeString = departureTime.toISOString();
            try {
                const hasValidWaypoints = Array.from(waypointInputs).some(input => input.value.trim() !== '');
                let waypointsForAPI = [];
                if (hasValidWaypoints) {
                    waypointsForAPI = waypoints
                        .filter(waypoint => {
                            const latlng = extractLatLng(waypoint);
                            return latlng && isFinite(latlng.latitude) && isFinite(latlng.longitude);
                        })
                        .map(waypoint => ({ location: { latLng: extractLatLng(waypoint) } }));
                }
                const tollRouteRequest = buildRouteRequest(originPlace, destinationPlace, waypointsForAPI, false, departureTimeString);
                const noTollRouteRequest = buildRouteRequest(originPlace, destinationPlace, waypointsForAPI, true, departureTimeString);
                const [tollRouteResponse, noTollRouteResponse] = await Promise.all([
                    fetch('https://routes.googleapis.com/directions/v2:computeRoutes', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Goog-Api-Key': '<?php echo htmlspecialchars($GOOGLE_API_KEY); ?>',
                            'X-Goog-FieldMask': 'routes.distanceMeters,routes.duration,routes.staticDuration,routes.polyline.encodedPolyline,routes.localizedValues,routes.travelAdvisory.speedReadingIntervals'
                        },
                        body: JSON.stringify(tollRouteRequest)
                    }).then(res => res.json()).catch(error => { console.error('Toll route error:', error); return null; }),
                    fetch('https://routes.googleapis.com/directions/v2:computeRoutes', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Goog-Api-Key': '<?php echo htmlspecialchars($GOOGLE_API_KEY); ?>',
                            'X-Goog-FieldMask': 'routes.distanceMeters,routes.duration,routes.staticDuration,routes.polyline.encodedPolyline,routes.localizedValues,routes.travelAdvisory.speedReadingIntervals'
                        },
                        body: JSON.stringify(noTollRouteRequest)
                    }).then(res => res.json()).catch(error => { console.error('No-toll route error:', error); return null; })
                ]);
                // Helper to map speed category to color
                const getSpeedColor = (speed) => {
                    switch (speed) {
                        case 'TRAFFIC_JAM': return '#EA4335'; // red
                        case 'SLOW': return '#FBBC05'; // yellow
                        //case 'NORMAL': return '#34A853'; // blue
                       // default: return '#9E9E9E'; // gray fallback
                    }
                };
                // Helper to draw a route with a base polyline plus traffic-colored segments
                function drawRouteWithTraffic(route, mainRoad = true) {
                    const enc = route && route.polyline && route.polyline.encodedPolyline;
                    if (!enc || typeof enc !== 'string' || enc.length === 0) return [];
                    const decoded = google.maps.geometry.encoding.decodePath(enc);
                    if (!Array.isArray(decoded) || decoded.length === 0) return [];
                    const pathLatLng = decoded.map(p => new google.maps.LatLng(p.lat(), p.lng()));
                    const drawn = [];
                    if(mainRoad){ var haloStrokeColor = '#2f00d0'; var baseColor = '#4632f8'; var addMainIndex = 100; } else { var haloStrokeColor = '#777bd3'; var baseColor = '#c4cbf8'; var addMainIndex = 0; }
                    // Underlay halo (white) for visual separation
                    const halo = new google.maps.Polyline({
                        path: pathLatLng,
                                geodesic: true,
                            strokeColor: haloStrokeColor,
                        strokeOpacity: 1,
                        strokeWeight: 6,
                        zIndex: 3+addMainIndex,
                        clickable: true,
                                map: map
                            });
                    drawn.push(halo);
                    // Base polyline
                    const base = new google.maps.Polyline({
                        path: pathLatLng,
                                geodesic: true,
                        strokeColor: baseColor,
                        strokeOpacity: 1,
                                strokeWeight: 3,
                        zIndex: 6+addMainIndex,
                        clickable: true,
                                map: map
                            });
                    drawn.push(base);
                    // Traffic segments overlay
                    const intervals = route.travelAdvisory && route.travelAdvisory.speedReadingIntervals || [];
                    intervals.forEach(iv => {
                        const start = Math.max(0, iv.startPolylinePointIndex || 0);
                        const end = Math.min(pathLatLng.length - 1, iv.endPolylinePointIndex || 0);
                        if (end > start) {
                            const segPath = pathLatLng.slice(start, end + 1);
                            if(iv.speed == 'TRAFFIC_JAM' || iv.speed == 'SLOW'){
                            const seg = new google.maps.Polyline({
                                path: segPath,
                                geodesic: true,
                                strokeColor: getSpeedColor(iv.speed),
                                strokeOpacity: 1,
                                strokeWeight: 4,
                                zIndex: 12+addMainIndex,
                                clickable: true,
                                map: map
                            });
                            drawn.push(seg);
                            }
                        }
                    });
                    return drawn;
                }
                // Helper: create a speech-box label overlay at position
                function createRouteLabelOverlay(position, cssClass, text, onClick) {
                    function LabelOverlay(pos, cls, content, clickHandler) {
                        this.position = pos;
                        this.cls = cls;
                        this.content = content;
                        this.onClick = clickHandler || null;
                        this.div = null;
                    }
                    LabelOverlay.prototype = new google.maps.OverlayView();
                    LabelOverlay.prototype.onAdd = function() {
                        const div = document.createElement('div');
                        div.className = `route-label ${this.cls}`;
                        div.innerHTML = `<div class="bubble">${this.content}</div><div class="pointer"></div>`;
                        div.style.pointerEvents = 'auto';
                        if (this.onClick) {
                            div.style.cursor = 'pointer';
                            div.addEventListener('click', this.onClick);
                        }
                        this.div = div;
                        const panes = this.getPanes();
                        panes.overlayMouseTarget.appendChild(div);
                    };
                    LabelOverlay.prototype.draw = function() {
                        if (!this.div) return;
                        const projection = this.getProjection();
                        const point = projection.fromLatLngToDivPixel(this.position);
                        this.div.style.left = point.x + 'px';
                        this.div.style.top = point.y + 'px';
                    };
                    LabelOverlay.prototype.onRemove = function() {
                        if (this.div && this.div.parentNode) {
                            if (this.onClick) {
                                this.div.removeEventListener('click', this.onClick);
                            }
                            this.div.parentNode.removeChild(this.div);
                        }
                        this.div = null;
                    };
                    return new LabelOverlay(position, cssClass, text, onClick);
                }
                // Click helpers
                function attachClickToOverlays(overlays, handler) {
                    (overlays || []).forEach(o => {
                        if (o && typeof o.addListener === 'function') {
                            o.addListener('click', handler);
                        }
                    });
                }
                function addLabelForRoute(route, cssClass, labelsArray) {
                    const enc = route && route.polyline && route.polyline.encodedPolyline;
                    if (!enc) return;
                    const pts = google.maps.geometry.encoding.decodePath(enc);
                    if (pts && pts.length) {
                        const idx = cssClass.indexOf('notoll') >= 0 ? Math.floor(pts.length * 0.65) : Math.floor(pts.length * 0.35);
                        const mid = pts[Math.min(Math.max(idx, 0), pts.length - 1)];
                        const details = parseRouteDetails(route);
                        const which = cssClass.indexOf('notoll') >= 0 ? 'notoll' : 'toll';
                        let content;
                        if (which === 'toll') {
                            const fuel = computeFuelCost(details.distanceMeters, details.durationSec, details.staticDurationSec);
                            // Use cached cost if available
                            const cost = (cachedTollTotals && typeof cachedTollTotals.cost === 'number') ? cachedTollTotals.cost : 0;
                            content = buildPaidOverlayHTML(details.distance, details.duration, cost, fuel);
                            // Update cache for distance/duration to help later updates
                            cachedTollTotals.distance = details.distance;
                            cachedTollTotals.duration = details.duration;
                        } else {
                            const fuel = computeFuelCost(details.distanceMeters, details.durationSec, details.staticDurationSec);
                            content = buildFreeOverlayHTML(details.distance, details.duration, fuel);
                            cachedNoTollTotals.distance = details.distance;
                            cachedNoTollTotals.duration = details.duration;
                            cachedNoTollTotals.fuel = fuel;
                        }
                        const overlay = createRouteLabelOverlay(new google.maps.LatLng(mid.lat(), mid.lng()), cssClass, content, () => selectRoute(which));
                        overlay.setMap(map);
                        labelsArray.push(overlay);
                    }
                }
                function selectRoute(which) {
                    clearAllRoutesAndLabels();
                    if (which === 'toll') {
                        if (lastTollRoute) tollRouteOverlays = drawRouteWithTraffic(lastTollRoute, true);
                        if (lastNoTollRoute) noTollRouteOverlays = drawRouteWithTraffic(lastNoTollRoute, false);
                    } else {
                        if (lastTollRoute) tollRouteOverlays = drawRouteWithTraffic(lastTollRoute, false);
                        if (lastNoTollRoute) noTollRouteOverlays = drawRouteWithTraffic(lastNoTollRoute, true);
                    }
                    if (lastTollRoute) addLabelForRoute(lastTollRoute, which === 'toll' ? 'toll active' : 'toll inactive', tollRouteLabels);
                    if (lastNoTollRoute) addLabelForRoute(lastNoTollRoute, which === 'toll' ? 'notoll inactive' : 'notoll active', noTollRouteLabels);
                    attachClickToOverlays(tollRouteOverlays, () => selectRoute('toll'));
                    attachClickToOverlays(noTollRouteOverlays, () => selectRoute('notoll'));
                    if (typeof window.showRouteLegend === 'function') {
                        window.showRouteLegend();
                    }
                }
              
                // Draw no-toll route (green base + traffic overlay)
                if (noTollRouteResponse && noTollRouteResponse.routes && noTollRouteResponse.routes.length > 0) {
                    const noTollRoute = noTollRouteResponse.routes[0];
                    lastNoTollRoute = noTollRoute;
                    // Alternative route styled gray with white halo underlay via drawRouteWithTraffic
                    noTollRouteOverlays = drawRouteWithTraffic(noTollRoute, false);
                    attachClickToOverlays(noTollRouteOverlays, () => selectRoute('notoll'));
                    const nd = parseRouteDetails(noTollRoute);
                    const enc2 = noTollRoute && noTollRoute.polyline && noTollRoute.polyline.encodedPolyline;
                    if (enc2) {
                        const pts2 = google.maps.geometry.encoding.decodePath(enc2);
                        if (pts2 && pts2.length) {
                            const mid2 = pts2[Math.floor(pts2.length * 0.65)];
                            const initialFuelFree = computeFuelCost(nd.distanceMeters, nd.durationSec, nd.staticDurationSec);
                            const overlay2 = createRouteLabelOverlay(new google.maps.LatLng(mid2.lat(), mid2.lng()), 'notoll inactive', buildFreeOverlayHTML(nd.distance, nd.duration, initialFuelFree) , () => selectRoute('notoll'));
                            overlay2.setMap(map);
                            noTollRouteLabels.push(overlay2);
                        }
                    }
                }
                if ((!tollRouteResponse || !tollRouteResponse.routes || tollRouteResponse.routes.length === 0) && 
                    (!noTollRouteResponse || !noTollRouteResponse.routes || noTollRouteResponse.routes.length === 0)) {
                    alert('No routes found. Please check your origin and destination.');
                    return false;
                }
                  // Draw toll route (blue base + traffic overlay)
                  if (tollRouteResponse && tollRouteResponse.routes && tollRouteResponse.routes.length > 0) {
                    const tollRoute = tollRouteResponse.routes[0];
                    lastTollRoute = tollRoute;
                    tollRouteOverlays = drawRouteWithTraffic(tollRoute, true);
                    attachClickToOverlays(tollRouteOverlays, () => selectRoute('toll'));
                    const td = parseRouteDetails(tollRoute);
                    const enc1 = tollRoute && tollRoute.polyline && tollRoute.polyline.encodedPolyline;
                    if (enc1) {
                        const pts1 = google.maps.geometry.encoding.decodePath(enc1);
                        if (pts1 && pts1.length) {
                            const mid1 = pts1[Math.floor(pts1.length * 0.35)];
                            // Initial overlay with fuel estimate and zero toll until we fetch
                            const initialFuel = computeFuelCost(td.distanceMeters, td.durationSec, td.staticDurationSec);
                            const overlay1 = createRouteLabelOverlay(new google.maps.LatLng(mid1.lat(), mid1.lng()), 'toll active', buildPaidOverlayHTML(td.distance, td.duration, (cachedTollTotals.cost||0), initialFuel) , () => selectRoute('toll'));
                            overlay1.setMap(map);
                            tollRouteLabels.push(overlay1);

                            // Build toll legs from polyline and fetch costs
                            try {
                                const pathArray = pts1.map(p => ({ lat: p.lat(), lng: p.lng() }));
                                const thresholdMeters = 10.0; // per requirement
                                const tolls = Array.isArray(window.tollsData) ? window.tollsData : [];
                                const { legs } = buildTollLegsFromPolyline(pathArray, tolls, thresholdMeters);
                                const vehicleTypeEl = document.querySelector('input[name="car-class"]:checked');
                                const vehicleType = vehicleTypeEl ? parseInt(vehicleTypeEl.value, 10) : 1;
                                if (legs.length > 0) {
                                    const costsResp = await fetchTollCosts(legs, vehicleType);
                                    if (costsResp && costsResp.ok) {
                                        const totalCost = costsResp.total_cost || 0;
                                        const fuel = computeFuelCost(td.distanceMeters, td.durationSec, td.staticDurationSec);
                                        cachedTollTotals.cost = totalCost;
                                        cachedTollTotals.fuel = fuel;
                                        cachedTollTotals.distance = td.distance;
                                        cachedTollTotals.duration = td.duration;
                                        if (overlay1 && overlay1.div) {
                                            const bubble = overlay1.div.querySelector('.bubble');
                                            if (bubble) bubble.innerHTML = buildPaidOverlayHTML(td.distance, td.duration, totalCost, fuel);
                                        }
                                        console.log('Toll legs and costs:', costsResp);
                                    } else {
                                        console.warn('Cost API error', costsResp);
                                    }
                                } else {
                                    console.log('No toll legs detected on paid route.');
                                }
                            } catch (e) {
                                console.error('Toll matching/cost fetch failed:', e);
                            }
                        }
                    }
                }
                // Details are shown on speech-box overlays only
                if (typeof window.showRouteLegend === 'function') { window.showRouteLegend(); }
            } catch (error) {
                console.error('Route computation error:', error);
                alert('Route error: ' + error.message);
                return false;
            }
            var sidebar = new bootstrap.Offcanvas(document.getElementById('sidebar'));
            sidebar.show();
            return false;
        };
       
        // Departure time auto-update logic
        function pad2(n) {
            return n < 10 ? '0' + n : n;
        }

        function getCurrentDateTimeLocal() {
            const now = new Date();
            now.setSeconds(0, 0); // zero out seconds and ms
            now.setMinutes(now.getMinutes() + 1); // add 1 minute
            const year = now.getFullYear();
            const month = pad2(now.getMonth() + 1);
            const day = pad2(now.getDate());
            const hour = pad2(now.getHours());
            const min = pad2(now.getMinutes());
            return `${year}-${month}-${day}T${hour}:${min}`;
        }

        function checkAndUpdateDepartureTime() {
            const input = document.getElementById('departure-time-input');
            if (!input) return;
            const val = input.value;
            const inputDate = new Date(val);
            const nowPlus1 = new Date();
            nowPlus1.setSeconds(0, 0);
            nowPlus1.setMinutes(nowPlus1.getMinutes() + 1);
            if (isNaN(inputDate.getTime()) || inputDate < nowPlus1) {
                input.value = getCurrentDateTimeLocal();
            }
        }
        // Initial check on load
        window.addEventListener('DOMContentLoaded', function() {
            checkAndUpdateDepartureTime();
            setInterval(checkAndUpdateDepartureTime, 1000); // every 1sec
        });

        $(function() {
            var customPriceSuffix = ' ₺/L';
    var customConsumptionSuffix = ' L/100km';
    if ($('#fuel-type-select option:selected').text().includes('Elektrik')) {
        customPriceSuffix = ' ₺/kWh';
        customConsumptionSuffix = ' kWh/100km';
    }
    
    $('#custom-fuel-price').attr('placeholder', 'Fiyat'+customPriceSuffix+'');
    $('#fuel-consumption-input').attr('placeholder', 'Tüketim'+customConsumptionSuffix+'');
  $('#custom-fuel-price').inputmask('decimal', {
    radixPoint: ',',
    groupSeparator: '',
    digits: 2,
    allowMinus: false,
    autoGroup: false,
    rightAlign: false,
    suffix: customPriceSuffix,
    placeholder: '_',
    onBeforePaste: function (pastedValue, opts) {
      return pastedValue.replace('.', ',');
    }
  });

  $('#fuel-consumption-input').inputmask('decimal', {
    radixPoint: ',',
    groupSeparator: '',
    digits: 2,
    allowMinus: false,
    autoGroup: false,
    rightAlign: false,
    suffix: customConsumptionSuffix,
    placeholder: '_',
    onBeforePaste: function (pastedValue, opts) {
      return pastedValue.replace('.', ',');
    }
  });

  // Show/hide custom price input
  $('#fuel-type-select').on('change', function() {
    var customPriceSuffix = ' ₺/L';
    var customConsumptionSuffix = ' L/100km';

    if ($(this).val() === 'customYakıt' || $(this).val() === 'customElektrik') {
      $('#custom-fuel-price').show();
    } else {
      $('#custom-fuel-price').hide();
    }
    
    if ($('option:selected', this).text().includes('Elektrik')) {
        customPriceSuffix = ' ₺/kWh';
        customConsumptionSuffix = ' kWh/100km';
    }
    $('#custom-fuel-price').attr('placeholder', 'Fiyat'+customPriceSuffix+'');
    $('#fuel-consumption-input').attr('placeholder', 'Tüketim'+customConsumptionSuffix+'');
    $('#custom-fuel-price').inputmask('remove');
    $('#fuel-consumption-input').inputmask('remove');
    $('#custom-fuel-price').inputmask('decimal', {
    radixPoint: ',',
    groupSeparator: '',
    digits: 2,
    allowMinus: false,
    autoGroup: false,
    rightAlign: false,
    suffix: customPriceSuffix,
    placeholder: '_',
    onBeforePaste: function (pastedValue, opts) {
      return pastedValue.replace('.', ',');
    }
  });

  $('#fuel-consumption-input').inputmask('decimal', {
    radixPoint: ',',
    groupSeparator: '',
    digits: 2,
    allowMinus: false,
    autoGroup: false,
    rightAlign: false,
    suffix: customConsumptionSuffix,
    placeholder: '_',
    onBeforePaste: function (pastedValue, opts) {
      return pastedValue.replace('.', ',');
    }
  });
  });
});
document.getElementById('collapse-expand-btn').onclick = function() {
    $('.map-panel').css('transition', 'left 0.4s cubic-bezier(.4,0,.2,1), top 0.4s cubic-bezier(.4,0,.2,1), right 0.4s cubic-bezier(.4,0,.2,1)');
    $('.map-panel').toggleClass('closed');
    setTimeout(function() {
        if($('.map-panel').hasClass('closed')){
            $('#collapse-expand-btn-up').addClass('dp-none');
            $('#collapse-expand-btn-left').addClass('dp-none');
            $('#collapse-expand-btn-right').removeClass('dp-none');
            $('#collapse-expand-btn-down').removeClass('dp-none');
        }else{
            $('#collapse-expand-btn-up').removeClass('dp-none');
            $('#collapse-expand-btn-left').removeClass('dp-none');
            $('#collapse-expand-btn-right').addClass('dp-none');
            $('#collapse-expand-btn-down').addClass('dp-none');
        }
    }, 300);
};
    </script>
    <script>
// Utility: Convert rem to px
function remToPx(rem) {
    return rem * parseFloat(getComputedStyle(document.documentElement).fontSize);
}

// Universal draggable panel utility (class-based positioning)
function makeDraggablePanel(options) {
    const {
        element,
        axis,
        openPosRem,
        closedPosRem,
        toggleClass,
        handle // new option for drag handle selector
    } = options;
    const $el = $(element);
    const $handle = handle ? $(handle) : $el; // default to panel if no handle
    let dragging = false, start = 0, startPanelPx = 0, delta = 0;
    let openPx = remToPx(openPosRem), closedPx = remToPx(closedPosRem);

    function getPos(e) {
        if (e.type.startsWith('touch')) {
            return axis === 'x' ? e.originalEvent.touches[0].clientX : e.originalEvent.touches[0].clientY;
        } else {
            return axis === 'x' ? e.clientX : e.clientY;
        }
    }
    function getCurrentClassPos() {
        return $el.hasClass(toggleClass) ? closedPx : openPx;
    }
    function setPanelTempTransform(offsetPx) {
        // Desktop exception: dragging right to open from closed state
        if (axis === 'x' && window.innerWidth > 768 && $el.hasClass(toggleClass)) {
            offsetPx += $el.outerWidth();
            // Clamp: don't allow dragging left of closed position
            offsetPx = Math.max(openPx, offsetPx);
            //console.log(openPx, closedPx, offsetPx, delta);
        }
        // Mobile exception: dragging down to open from closed state
        if (axis === 'y' && window.innerWidth <= 768 && $el.hasClass(toggleClass)) {
            offsetPx += $el.outerHeight();
            // Clamp: don't allow dragging above closed position
            offsetPx = Math.max(openPx, offsetPx);
        }
        $el.css('transform', axis === 'x' ? `translateX(${offsetPx}px)` : `translateY(${offsetPx}px)`);
    }
    function clearPanelTransform() {
        $el.css('transition', 'none');
        $el.css('transform', '');
        setTimeout(function() {
            $el.css('transition', 'left 0.4s cubic-bezier(.4,0,.2,1), top 0.4s cubic-bezier(.4,0,.2,1), right 0.4s cubic-bezier(.4,0,.2,1)');
        }, 100);
    }
    function endPanel(finalPx) {
        const threshold = 5;
        let shouldOpen = false;
        if (axis === 'x') {
            // Desktop: right to open, left to close
            if (delta > threshold) shouldOpen = true;      // open
            else if (delta < -threshold) shouldOpen = false; // close
            else shouldOpen = !$el.hasClass(toggleClass);   // fallback: keep current
        } else if (axis === 'y') {
            // Mobile: down to open, up to close
            if (delta > threshold) shouldOpen = true;      // open
            else if (delta < -threshold) shouldOpen = false; // close
            else shouldOpen = !$el.hasClass(toggleClass);   // fallback: keep current
        }
        if (toggleClass) {
            if (shouldOpen) $el.removeClass(toggleClass);
            else $el.addClass(toggleClass);
        }
        clearPanelTransform();
    }
    // Replace $el.on('touchstart mousedown', ...) with $handle.on(...)
    $handle.on('touchstart mousedown', function(e) {
        if ((axis === 'x' && window.innerWidth <= 768) || (axis === 'y' && window.innerWidth > 768)) return;
        dragging = true;
        $el.addClass('dragging');
        start = getPos(e);
        startPanelPx = getCurrentClassPos();
        e.preventDefault();
    });
    $(window).on('touchmove mousemove', function(e) {
        if (!dragging) return;
        let pos = getPos(e);
        delta = pos - start;
        let offsetPx = startPanelPx + delta;
        // Clamp between open and closed
        let min = Math.min(openPx, closedPx), max = Math.max(openPx, closedPx);
        offsetPx = Math.max(min, Math.min(max, offsetPx));
        setPanelTempTransform(offsetPx);
    });
    $(window).on('touchend mouseup', function(e) {
        if (!dragging) return;
        dragging = false;
        $el.removeClass('dragging');
        let finalPx = startPanelPx + delta;
        let min = Math.min(openPx, closedPx), max = Math.max(openPx, closedPx);
        finalPx = Math.max(min, Math.min(max, finalPx));
        endPanel(finalPx);
        if($('.map-panel').hasClass('closed')){
            $('#collapse-expand-btn-up').addClass('dp-none');
            $('#collapse-expand-btn-left').addClass('dp-none');
            $('#collapse-expand-btn-right').removeClass('dp-none');
            $('#collapse-expand-btn-down').removeClass('dp-none');
        }else{
            $('#collapse-expand-btn-up').removeClass('dp-none');
            $('#collapse-expand-btn-left').removeClass('dp-none');
            $('#collapse-expand-btn-right').addClass('dp-none');
            $('#collapse-expand-btn-down').addClass('dp-none');
        }
        delta = 0;
    });
}
// Usage: Desktop (X axis)
makeDraggablePanel({
    element: '.map-panel',
    axis: 'x',
    openPosRem: 2,      // left: 2rem (open)
    closedPosRem: -27,  // left: -27rem (closed)
    toggleClass: 'closed',
    handle: '.collapse-expand-btn' // specify handle
});
// Usage: Mobile (Y axis)
makeDraggablePanel({
    element: '.map-panel',
    axis: 'y',
    openPosRem: 0.5,    // top: 0.5rem (open)
    closedPosRem: -23,  // top: -23rem (closed)
    toggleClass: 'closed',
    handle: '.collapse-expand-btn' // specify handle
});
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($GOOGLE_API_KEY); ?>&libraries=places,geometry&callback=initMap" async defer></script>
    <script>
    window.addEventListener('DOMContentLoaded', function() {
        fetch('api/index.php', { cache: 'no-store' })
            .then(function(res){ return res.json(); })
            .then(function(data){
                window.tollsData = data && data.ok ? data.tolls : [];
                console.log('Tolls loaded:', window.tollsData);
            })
            .catch(function(err){ console.error('Failed to load tolls:', err); });
    });
    </script>
</body>

</html>