<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Province;
use Illuminate\Support\Facades\DB;

$jsonContent = file_get_contents('D:\Documents\skripsi\eventour\GeoJson-Indonesia-38-Provinsi-main\Provinsi\38 Provinsi Indonesia - Provinsi.json');
$data = json_decode($jsonContent, true);

$nameMap = [
    'Daerah Istimewa Yogyakarta' => 'DI Yogyakarta',
    // Check if others need mapping
];

$updatedCount = 0;

foreach ($data['features'] as $feature) {
    $geoName = $feature['properties']['PROVINSI'];
    $dbName = $nameMap[$geoName] ?? $geoName;
    
    // Some basic title case fixing if needed, but looks like DB uses same casing.
    // e.g. "Jawa Timur" in JSON -> "Jawa Timur" in DB.
    
    $province = Province::whereRaw('LOWER(name) = ?', [strtolower($dbName)])->first();
    
    if ($province) {
        $geojson = json_encode(['type' => $feature['geometry']['type'], 'coordinates' => $feature['geometry']['coordinates']]);
        $escaped = str_replace("'", "''", $geojson);
        
        DB::update("
            UPDATE provinces 
            SET geojson = ?, geom = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography 
            WHERE id = ?
        ", [$geojson, $geojson, $province->id]);
        
        $updatedCount++;
        echo "Updated: $dbName\n";
    } else {
        echo "NOT FOUND IN DB: $geoName (tried $dbName)\n";
    }
}

echo "Total updated: $updatedCount\n";

