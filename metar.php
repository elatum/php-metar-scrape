<?php
/**
 * Scrapes METAR/TAF airport weather data from the NOAA Aviation Weather
 * data API and renders a short human-readable summary.
 *
 * References:
 *   https://aviationweather.gov/data/metar/
 *   https://aviationweather.gov/api/data/metar?ids=MPDA&format=json&hours=10
 *   https://aviationweather.gov/api/data/taf?ids=MPDA&format=json&hours=10
 *   https://en.allmetsat.com/metar-taf/nicaragua-costa-rica-panama.php?icao=MNCH
 */

// --- Station configuration ---------------------------------------------------
$station_name = 'Chinandega, Nicaragua';
$station_icao = 'MNCH';
$timezone     = -6;      // hours offset from UTC
$timezonedesc = 'CST';

$station_url = 'https://aviationweather.gov/api/data/metar'
    . '?ids=' . urlencode($station_icao)
    . '&format=json&hours=10';

/**
 * Fetch a URL, preferring cURL (works on most hosts and handles HTTPS/TLS
 * cleanly) and falling back to file_get_contents where cURL is unavailable.
 *
 * @return string|false Response body, or false on failure.
 */
function fetch_url($url)
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'php-metar-scrape',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body !== false && $code >= 200 && $code < 300) {
            return $body;
        }
    }

    $context = stream_context_create([
        'http' => ['timeout' => 15, 'user_agent' => 'php-metar-scrape'],
    ]);

    return @file_get_contents($url, false, $context);
}

// --- Fetch and parse the feed ------------------------------------------------
// The API returns a JSON array of observations sorted newest first.
$response = fetch_url($station_url);
$reports  = $response !== false ? json_decode($response, true) : null;

if (!is_array($reports) || empty($reports)) {
    echo 'Weather data is currently unavailable.';
    return;
}

$metar = $reports[0];

$station_id       = (string) ($metar['icaoId'] ?? '');
$temp_c           = (float)  ($metar['temp'] ?? 0);
$wind_speed_kt    = (float)  ($metar['wspd'] ?? 0);
$wind_dir_degrees = $metar['wdir'] ?? 0;   // integer, or "VRB" for variable
$sky_condition    = (string) ($metar['cover'] ?? '');
$obs_timestamp    = (int)    ($metar['obsTime'] ?? 0);   // Unix timestamp (UTC)

// --- Derived values ----------------------------------------------------------
$temp_f         = $temp_c * 1.8 + 32;
$wind_speed_mph = round($wind_speed_kt * 1.15078);

$local_time = $obs_timestamp + (3600 * $timezone);
$obsvtime   = date('g A', $local_time);
$obsvdate   = date('j M Y', $local_time);

// Wind cardinal direction: 8-point compass in 45 degree sectors.
// The API reports "VRB" (or 0) when the wind direction is variable/calm.
if (!is_numeric($wind_dir_degrees) || (int) $wind_dir_degrees === 0) {
    $wind_dir_quad = 'VARIABLE';
} else {
    $compass       = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
    $wind_dir_quad = $compass[(int) round($wind_dir_degrees / 45) % 8];
}

// Sky cover code -> description. SKC|CLR|CAVOK|FEW|SCT|BKN|OVC|OVX
$sky_descriptions = [
    'SKC'   => 'clear skies',
    'CLR'   => 'clear skies',
    'CAVOK' => 'scattered clouds',
    'FEW'   => 'scattered clouds',
    'SCT'   => 'scattered clouds',
    'BKN'   => 'broken clouds',
    'OVC'   => 'overcast',
    'OVX'   => 'overcast',
];
$sky_desc = $sky_descriptions[$sky_condition] ?? 'n/a';

// --- Output ------------------------------------------------------------------
echo $station_name . '<br/>';
echo 'wind is ' . $wind_dir_quad . ' at ' . $wind_speed_mph . 'mph<br/>';
echo $sky_desc . '<br/>';
echo $temp_c . '&deg;C/' . $temp_f . '&deg;F at ' . $obsvtime . ' ' . $timezonedesc . ' on ' . $obsvdate . '<br/>';

echo "<span id='metartemp'>" . $temp_c . '&deg;C/' . $temp_f . '&deg;F</span> '
    . "<span id='metartime'>@ " . $obsvtime . ' ' . $timezonedesc . '</span>';
