# 🚗 Cost and Time Compare Module - Highway Route Comparison System

## 📋 Project Overview

This is an **internship project** that provides a comprehensive highway route comparison system for Turkish highways (Only Otoyol A.Ş. price data and coordinates implemented). The system allows users to compare toll roads (Ücretli Yol) with free alternative routes (Gişesiz Yol) by calculating total costs including toll fees, fuel consumption, and travel time.

## 🎯 Purpose & Features

### **Primary Purpose**
- **Route Comparison**: Compare toll highways vs. free alternative route
- **Cost Analysis**: Calculate total journey costs including tolls and fuel with Traffic Factor
- **Time Estimation**: Provide accurate travel time estimates with traffic awareness
- **Real-time Data**: Fetch current toll prices and fuel costs from official sources
- **Interactive Maps**: Google Maps integration with route visualization

### **Key Features**
- 🗺️ **Interactive Google Maps Interface** with route overlays
- 💰 **Toll Cost Calculation** for different vehicle types
- ⛽ **Fuel Cost Estimation** based on distance and consumption (Custom fuel price input allowed)
- ⏱️ **Traffic-Aware Routing** with departure time consideration
- 🔄 **Route Alternatives** (toll vs. free routes)
- 📱 **Responsive Design** for mobile and desktop
- 🚗 **Multi-Vehicle Support** (different toll rates per vehicle class)
- 🚫 **Smart Avoidance** (tolls for free routes, ferries for all routes are not allowed)
- 📱 **Iframe Compatible**: It can be implemented on mobile app or website with an iframe

## 🏗️ System Architecture

### **Frontend (index.php)**
- Google Maps API, Routes API, Autocomplete API, Geocoding API integration (You need to add these services to your API from Google Cloud Console)
- Bootstrap 5 responsive UI
- Real-time route calculation and visualization
- Interactive route selection and comparison

### **Backend API (api/index.php)**
- RESTful API endpoints for toll data
- Toll cost calculation services
- Vehicle type support
- JSON response handling

### **Data Collection (cron/index.php)**
- Automated toll price updates
- Fuel price scraping from official sources
- Route data collection using Google Routes API
- Database synchronization

### **Database (MySQL)**
- Toll station information
- Route data with encoded polylines
- Cost tables for different vehicle types
- Highway company data that already on their website

## 🚀 Installation & Setup

### **Prerequisites**
- PHP 8+ with MySQL extension
- MySQL/MariaDB database
- Google Cloud API key (with Enabled Services of Maps API, Routes API, Autocomplete API, Geocoding API)
- Web server (Apache/Nginx)
- Cron job access (to check and update prices daily)

### **1. Database Setup**
```sql
# Import the database structure
mysql -u username -p database_name < freeandpaidcomparemoduledb.sql
```

### **2. Environment Configuration**
Create a `.env` file in the root directory:
```env
# Database Configuration
DB_HOST=localhost
DB_USER=your_db_username
DB_PASS=your_db_password
DB_NAME=your_database_name

# Google Maps API
GOOGLE_API_KEY=your_google_maps_api_key

# Cron Job Security
CRON_PASS=your_secure_cron_password
```

### **3. File Permissions**
```bash
# Ensure proper permissions for cron jobs
chmod 755 cron/
chmod 644 .env
```

### **4. Google Maps API Setup**
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Enable Maps API, Routes API, Autocomplete API, Geocoding API
3. Create API key with proper restrictions
4. Add the key to your `.env` file

## ⏰ Cronjob Setup

### **Required Cron Jobs**

The system requires several automated tasks to keep data current:

#### **1. Toll Price Updates (Daily)**
```bash
# Update toll prices from official sources
0 6 * * * curl "http://yourdomain.com/cron/?pass=YOUR_CRON_PASS&action=otoyolas_price_update"
```

#### **2. Fuel Price Updates (Daily)**
```bash
# Update fuel prices from official sources
0 8 * * * curl "http://yourdomain.com/cron/?pass=YOUR_CRON_PASS&action=update_fuel_prices"
```

#### **3. Route Data Updates**
```bash
# Check tolls if any tolls added or removed (Dont use it as cronjob, Use it with care, it uses a lot of Google Routes API request when used force=1 paramater.)
curl "http://yourdomain.com/cron/?pass=YOUR_CRON_PASS&action=otoyolas_add_update_tolls"
```

### **Cron Job Actions**

| Action | Purpose | Frequency | API Usage |
|--------|---------|-----------|-----------|
| `otoyolas_price_update` | Update toll prices | Daily | Low |
| `update_fuel_prices` | Update fuel prices | Daily | Low |
| `otoyolas_add_update_tolls` | Update route data | Never | High (Google API) |

### **Manual Execution**
You can also run cron jobs manually:
```bash
# Via web browser
http://yourdomain.com/cron/?pass=YOUR_CRON_PASS&action=otoyolas_price_update

# Via command line
php cron/index.php pass=YOUR_CRON_PASS action=otoyolas_price_update
```

## 🔧 Configuration

### **Database Tables**
- `highway_companies`: Highway company information
- `tolls`: Toll station details with coordinates
- `toll_routes`: Route data between toll stations
- `toll_route_costs`: Cost tables per vehicle type
- `fuel_types`: Fuel type definitions

### **API Endpoints**
- `GET /api/?action=tolls`: Fetch all toll stations
- `POST /api/?action=costs`: Calculate toll costs for routes

### **Environment Variables**
- `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`: Database connection
- `GOOGLE_API_KEY`: Google Maps API access
- `CRON_PASS`: Secure password for cron job access

## 📊 Data Sources

### **Toll Prices**
- **Otoyol AŞ**: Official Turkish highway company
- **Scraping**: Automated data collection from public websites
- **Fallback**: Manual database updates when needed

### **Fuel Prices**
- **OPET API**: Official fuel price API
- **Petrol Ofisi**: LPG prices from website
- **Şarj Fiyat**: Website to get avarage prices of charging station companies
- **Real-time**: Daily updates for accurate calculations

### **Route Data**
- **Google Maps API**: Route calculation and polylines
- **Traffic Data**: Real-time traffic conditions
- **Distance/Time**: Accurate measurements for cost calculations


## 🔒 Security Features

- **Cron Authentication**: Secure password protection for automated tasks
- **API Rate Limiting**: Google API usage monitoring
- **Input Validation**: Secure coordinate and data handling
- **CORS Protection**: Configurable cross-origin restrictions

## 📱 User Interface Features

### **Route Input**
- Origin and destination autocomplete
- Waypoint support (up to 2 intermediate points)
- Reverse route functionality
- Geolocation support

### **Route Comparison**
- Side-by-side route visualization
- Cost breakdown (tolls + fuel*traffic multiplier)
- Time comparison with traffic
- Interactive map overlays

### **Navigation Integration**
- Google Maps navigation links
- Route-specific avoid options
- Departure time consideration
- Traffic-aware routing

## 🛠️ Development & Maintenance

### **Code Structure**
```
├── index.php          # Main application interface
├── api/               # REST API endpoints
├── cron/              # Automated data collection
├── load.php           # Configuration and utilities
├── .env               # Environment variables
└── README.md          # This documentation
```

### **Key Functions**
- `buildRouteRequest()`: Route calculation requests
- `computeFuelCost()`: Fuel consumption calculations
- `fetchTollCosts()`: Toll cost API integration
- `getFuelPrices()`: Fuel price scraping
- `getTollsFromOtoyolasWebsite()`: Toll data collection

### **Error Handling**
- Error logging
- Graceful fallbacks for API failures
- User-friendly error messages
- Debug mode for development

## 📈 Performance Considerations

- **API Limits**: Google Maps API usage monitoring
- **Database Indexing**: Optimized queries for large datasets
- **Caching**: Route data caching to reduce API calls
- **Batch Processing**: Efficient bulk data updates


## 📞 Support & Contact

This is an **internship project** developed for educational purposes. For questions or support, please contact me.

## 📄 License

This project is developed as part of an internship program. All rights reserved.

---

**Note**: This system is designed specifically for Turkish highways and may require modifications for use in other regions. Always ensure compliance with local regulations and API usage policies.
