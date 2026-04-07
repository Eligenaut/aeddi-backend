<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\CotisationController;
use App\Http\Controllers\AuthController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('auth/google',          [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Pages statiques pour le SEO
Route::get('/about', function () {
    return view('pages.about');
});

Route::get('/contact', function () {
    return view('pages.contact');
});

Route::get('/activities', function () {
    return view('pages.activities');
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