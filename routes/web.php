<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\CotisationController;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Auth\GoogleAuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/google', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

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

// Routes API existantes
Route::get('/api/activites', [ActiviteController::class, 'index']);
Route::post('/api/activites', [ActiviteController::class, 'store']);
Route::get('/api/activites/{id}', [ActiviteController::class, 'show']);
Route::put('/api/activites/{id}', [ActiviteController::class, 'update']);
Route::delete('/api/activites/{id}', [ActiviteController::class, 'destroy']);

Route::get('/api/cotisations', [CotisationController::class, 'index']);
Route::post('/api/cotisations', [CotisationController::class, 'store']);
Route::get('/api/cotisations/{id}', [CotisationController::class, 'show']);
Route::put('/api/cotisations/{id}', [CotisationController::class, 'update']);
Route::delete('/api/cotisations/{id}', [CotisationController::class, 'destroy']);