<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'preferred_model',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class)->orderBy('last_message_at', 'desc');
    }

    public function getPreferredModelAttribute($value)
    {
        return $value ?? \App\Services\ChatService::DEFAULT_MODEL;
    }

    public function updatePreferredModel(string $model): void
    {
        $this->update(['preferred_model' => $model]);
    }

    public function instructions(): HasOne
    {
        return $this->hasOne(UserInstruction::class);
    }

    public function getInstructionsOrDefault()
    {
        // Load the relationship if not already loaded
        if (!$this->relationLoaded('instructions')) {
            $this->load('instructions');
        }

        if ($this->instructions) {
            return [
                'id' => $this->instructions->id,
                'user_id' => $this->instructions->user_id,
                'about_you' => $this->instructions->about_you ?? '',
                'behavior' => $this->instructions->behavior ?? '',
                'custom_commands' => $this->instructions->custom_commands ?? [],
                'enabled' => $this->instructions->enabled ?? true,
                'created_at' => $this->instructions->created_at,
                'updated_at' => $this->instructions->updated_at,
            ];
        }

        return [
            'id' => null,
            'user_id' => $this->id,
            'about_you' => '',
            'behavior' => '',
            'custom_commands' => [],
            'enabled' => true,
            'created_at' => null,
            'updated_at' => null,
        ];
    }
}
