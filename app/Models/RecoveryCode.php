<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class RecoveryCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code',
        'used',
        'created_by',
        'expires_at'
    ];

    protected $casts = [
        'used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur qui a créé le code
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Créer un nouveau code de récupération pour un email
     */
    public static function createCodeForEmail(string $email, int $createdBy = null): self
    {
        // Générer un code à 6 chiffres unique
        do {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->exists());

        // Supprimer l'ancien code pour cet email s'il existe
        self::where('email', $email)->delete();

        return self::create([
            'email' => $email,
            'code' => $code,
            'used' => false,
            'created_by' => $createdBy,
            'expires_at' => Carbon::now()->addHours(24) // Expire dans 24h
        ]);
    }

    /**
     * Vérifier si un code est valide pour un email
     */
    public static function isValidCodeForEmail(string $email, string $code): bool
    {
        return self::where('email', $email)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }

    /**
     * Marquer un code comme utilisé
     */
    public static function markAsUsed(string $email, string $code): bool
    {
        $recoveryCode = self::where('email', $email)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($recoveryCode) {
            $recoveryCode->update(['used' => true]);
            return true;
        }

        return false;
    }

    /**
     * Vérifier si un email est autorisé
     */
    public static function isEmailAuthorized(string $email): bool
    {
        return self::where('email', $email)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }

    /**
     * Nettoyer les codes expirés
     */
    public static function cleanExpiredCodes(): int
    {
        return self::where('expires_at', '<', Carbon::now())->delete();
    }
}
