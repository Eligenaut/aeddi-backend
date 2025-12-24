<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceStatusCode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Si c'est une route API
        if ($request->is('api/*')) {
            $contentType = $response->headers->get('Content-Type', '');
            
            // Vérifier si c'est une réponse JSON (peut être application/json ou application/json; charset=utf-8)
            if (str_contains($contentType, 'application/json')) {
                $content = $response->getContent();
                $decoded = json_decode($content, true);
                
                // Si le contenu indique une erreur 404 mais le statut est 200, corriger
                if (is_array($decoded) && isset($decoded['message']) && 
                    ($decoded['message'] === 'Endpoint introuvable' || 
                     $decoded['message'] === 'Route not found' ||
                     str_contains($decoded['message'], 'introuvable'))) {
                    
                    $currentStatus = $response->getStatusCode();
                    if ($currentStatus === 200) {
                        \Log::info('ForceStatusCode: Correcting status from 200 to 404', [
                            'uri' => $request->getRequestUri(),
                            'message' => $decoded['message']
                        ]);
                        $response->setStatusCode(404);
                    }
                }
            }
        }
        
        return $response;
    }
}

