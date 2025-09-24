<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TwoFactorAuth extends Model
{
    protected $table = 'two_factor_auth';
    
    protected $fillable = [
        'user_id',
        'secret_key',
        'is_enabled',
        'backup_codes',
        'last_used_at'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'backup_codes' => 'array',
        'last_used_at' => 'datetime'
    ];

    protected $hidden = [
        'secret_key',
        'backup_codes'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generateBackupCodes()
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        $this->backup_codes = $codes;
        $this->save();
        return $codes;
    }

    public function useBackupCode($code)
    {
        $codes = $this->backup_codes ?? [];
        $index = array_search(strtoupper($code), $codes);
        
        if ($index !== false) {
            unset($codes[$index]);
            $this->backup_codes = array_values($codes);
            $this->save();
            return true;
        }
        
        return false;
    }
}