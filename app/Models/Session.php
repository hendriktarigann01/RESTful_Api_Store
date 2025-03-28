<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Metode untuk membersihkan session lama
    public static function cleanupOldSessions($hours = 24)
    {
        $threshold = now()->subHours($hours);
        self::where('last_activity', '<', $threshold)->delete();
    }

    // Metode untuk mencatat aktivitas mencurigakan
    public static function logSuspiciousActivity($user, $details)
    {
        return self::create([
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => json_encode($details),
            'last_activity' => now()->timestamp
        ]);
    }
}
