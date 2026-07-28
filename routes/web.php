<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\CotisationController;
use App\Http\Controllers\AuthController;
Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'version' => '1.0',
    ]);
});

Route::get('auth/google',          [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::get('/about', function () {
    return response()->json(['message' => 'À propos d\'AEDDI']);
});

Route::get('/contact', function () {
    return response()->json(['message' => 'Contact AEDDI']);
});

Route::get('/activities', function () {
    return response()->json(['message' => 'Activités AEDDI']);
});

// Route pour le sitemap
Route::get('/sitemap.xml', function () {
    $baseUrl = config('app.url');
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    // Page d'accueil
    $sitemap .= '<url>';
    $sitemap .= '<loc>' . $baseUrl . '/</loc>';
    $sitemap .= '<lastmod>' . date('Y-m-d') . '</lastmod>';
    $sitemap .= '<changefreq>daily</changefreq>';
    $sitemap .= '<priority>1.0</priority>';
    $sitemap .= '</url>';

    // Pages statiques
    $pages = ['/about', '/contact', '/activities'];
    foreach ($pages as $page) {
        $sitemap .= '<url>';
        $sitemap .= '<loc>' . $baseUrl . $page . '</loc>';
        $sitemap .= '<lastmod>' . date('Y-m-d') . '</lastmod>';
        $sitemap .= '<changefreq>monthly</changefreq>';
        $sitemap .= '<priority>0.8</priority>';
        $sitemap .= '</url>';
    }

    $sitemap .= '</urlset>';

    return response($sitemap, 200)->header('Content-Type', 'text/xml');
});