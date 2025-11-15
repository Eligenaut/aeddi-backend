<?php

namespace App\Http\Controllers;

use App\Models\RecoveryCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class RecoveryCodeController extends Controller
{
    /**
     * Ajouter un email autorisé (pour les trésoriers)
     */
    public function addAuthorizedEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:recovery_codes,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $recoveryCode = RecoveryCode::createCodeForEmail(
                $request->email,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Email ajouté avec succès',
                'code' => $recoveryCode->code
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de l\'email'
            ], 500);
        }
    }

    /**
     * Obtenir la liste des emails autorisés
     */
    public function getAuthorizedEmails(): JsonResponse
    {
        try {
            $emails = RecoveryCode::with('creator')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $emails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des emails'
            ], 500);
        }
    }

    /**
     * Supprimer un email autorisé
     */
    public function deleteAuthorizedEmail(int $id): JsonResponse
    {
        try {
            $recoveryCode = RecoveryCode::findOrFail($id);
            $recoveryCode->delete();

            return response()->json([
                'success' => true,
                'message' => 'Email supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'email'
            ], 500);
        }
    }

    /**
     * Envoyer le code de validation par email
     */
    public function sendValidationCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;

        // Vérifier si l'email est autorisé
        if (!RecoveryCode::isEmailAuthorized($email)) {
            return response()->json([
                'success' => false,
                'message' => 'Email non autorisé. Contactez le trésorier de l\'AEDDI.'
            ], 403);
        }

        try {
            // Récupérer le code existant
            $recoveryCode = RecoveryCode::where('email', $email)
                ->where('used', false)
                ->where('expires_at', '>', now())
                ->first();

            if (!$recoveryCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun code valide trouvé pour cet email'
                ], 404);
            }

            // Envoyer l'email avec le code
            Mail::send('emails.validation', [
                'code' => $recoveryCode->code,
                'email' => $email
            ], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Code de validation AEDDI');
            });

            return response()->json([
                'success' => true,
                'message' => 'Code envoyé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du code'
            ], 500);
        }
    }

    /**
     * Valider le code de récupération
     */
    public function validateCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;
        $code = $request->code;

        // Vérifier si le code est valide
        if (!RecoveryCode::isValidCodeForEmail($email, $code)) {
            return response()->json([
                'success' => false,
                'message' => 'Code incorrect ou expiré'
            ], 400);
        }

        try {
            // Ne pas marquer le code comme utilisé ici, on le fera lors de l'inscription
            // Juste vérifier qu'il est valide
            return response()->json([
                'success' => true,
                'message' => 'Code validé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation du code'
            ], 500);
        }
    }

    /**
     * Nettoyer les codes expirés
     */
    public function cleanExpiredCodes(): JsonResponse
    {
        try {
            $deletedCount = RecoveryCode::cleanExpiredCodes();

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} codes expirés supprimés"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du nettoyage des codes'
            ], 500);
        }
    }

    /**
     * Vérifier si un email est autorisé (pour inscription)
     */
    public function checkEmailAllowed(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;

        if (\App\Models\RecoveryCode::isEmailAuthorized($email)) {
            return response()->json([
                'success' => true,
                'message' => 'Email autorisé !'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Email non autorisé.'
            ], 403);
        }
    }
}
