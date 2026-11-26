<?php
/**
 * FORT EXPLORER - Weather & Travel Advisories Helper
 * Integrates with Open-Meteo API for real-time weather and generates Sahyadri mountain advisories.
 */

function get_destination_weather(float $lat, float $lon): array {
    // Default safe fallback if network is offline or throttled
    $fallback = [
        'available' => false,
        'temperature' => 26,
        'weather_code' => 0,
        'weather_description' => 'Pleasant Sahyadri Climate',
        'wind_speed' => 12,
        'precipitation_probability' => 10,
        'advisory' => 'Check local ranger advisories and carry sufficient drinking water before ascending.',
        'advisory_level' => 'info' // info, warning, danger
    ];

    if ($lat == 0 || $lon == 0) {
        return $fallback;
    }

    // Cache weather locally in tmp for 30 minutes to reduce calls
    $cacheKey = md5(round($lat, 2) . '_' . round($lon, 2));
    $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fe_weather_cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.json';

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 1800)) {
        $cachedData = @json_decode(file_get_contents($cacheFile), true);
        if ($cachedData) {
            return $cachedData;
        }
    }

    $url = sprintf(
        "https://api.open-meteo.com/v1/forecast?latitude=%.4f&longitude=%.4f&current=temperature_2m,weather_code,wind_speed_10m,precipitation&daily=precipitation_probability_max,temperature_2m_max,temperature_2m_min&timezone=Asia%%2FKolkata",
        $lat,
        $lon
    );

    $context = stream_context_create([
        'http' => [
            'timeout' => 2.5,
            'ignore_errors' => true,
            'user_agent' => 'FortExplorer/2.0 (Heritage & Tourism Platform)'
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if (!$response) {
        return $fallback;
    }

    $json = @json_decode($response, true);
    if (!$json || !isset($json['current'])) {
        return $fallback;
    }

    $current = $json['current'];
    $daily = $json['daily'] ?? [];

    $temp = round((float)($current['temperature_2m'] ?? 25));
    $code = (int)($current['weather_code'] ?? 0);
    $wind = round((float)($current['wind_speed_10m'] ?? 10));
    $precip = (float)($current['precipitation'] ?? 0);
    $rainProb = isset($daily['precipitation_probability_max'][0]) ? (int)$daily['precipitation_probability_max'][0] : ($precip > 0 ? 80 : 15);

    // Interpret WMO Weather Code
    $condition = 'Clear Sky';
    $advisory = 'Favorable weather for fort exploration. Maintain steady pace and drink water.';
    $advisoryLevel = 'info';

    if ($code === 0) {
        $condition = 'Sunny & Clear';
    } elseif (in_array($code, [1, 2, 3])) {
        $condition = 'Partly Cloudy';
    } elseif (in_array($code, [45, 48])) {
        $condition = 'Misty / Foggy';
        $advisory = 'Dense fog alert! Sahyadri cliffs and ridge trails have low visibility. Stay on main stone paths.';
        $advisoryLevel = 'warning';
    } elseif (in_array($code, [51, 53, 55, 61, 63])) {
        $condition = 'Light to Moderate Rain';
        $advisory = 'Wet rock warning: Basalt steps become slippery. Use shoes with firm grip and trek with caution.';
        $advisoryLevel = 'warning';
    } elseif (in_array($code, [65, 80, 81, 82])) {
        $condition = 'Heavy Monsoon Downpour';
        $advisory = 'Monsoon Torrential Alert: High risk of waterfall flash swells and loose boulder falls. Avoid steep exposed edges.';
        $advisoryLevel = 'danger';
    } elseif (in_array($code, [95, 96, 99])) {
        $condition = 'Thunderstorm with Gusty Winds';
        $advisory = 'Severe Weather Warning: Lightning risk on high fort plateaus. Do not take shelter under solitary trees or exposed battlements.';
        $advisoryLevel = 'danger';
    }

    if ($temp >= 36) {
        $advisory = 'High Temperature Warning: Risk of heat exhaustion during midday trek ascent. Start early (before 7:00 AM) and carry ORS/electrolytes.';
        $advisoryLevel = 'warning';
    }

    $result = [
        'available' => true,
        'temperature' => $temp,
        'weather_code' => $code,
        'weather_description' => $condition,
        'wind_speed' => $wind,
        'precipitation_probability' => $rainProb,
        'advisory' => $advisory,
        'advisory_level' => $advisoryLevel
    ];

    @file_put_contents($cacheFile, json_encode($result));
    return $result;
}
