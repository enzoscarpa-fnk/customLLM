<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInstruction extends Model
{
    protected $fillable = [
        'user_id',
        'about_you',
        'behavior',
        'custom_commands',
        'enabled'
    ];

    protected $casts = [
        'custom_commands' => 'array',
        'enabled' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedInstructionsAttribute(): string
    {
        $instructions = [];

        if ($this->enabled && $this->about_you) {
            $instructions[] = "About the user: " . $this->about_you;
        }

        if ($this->enabled && $this->behavior) {
            $instructions[] = "Behavior instructions: " . $this->behavior;
        }

        if ($this->enabled && $this->custom_commands) {
            $commands = collect($this->custom_commands)->map(function ($command) {
                return "Command {$command['name']}: {$command['response']}";
            })->implode("\n");

            if ($commands) {
                $instructions[] = "Custom commands available:\n" . $commands;
            }
        }

        return implode("\n\n", $instructions);
    }
}
