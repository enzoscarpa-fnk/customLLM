<?php

namespace App\Http\Controllers;

use App\Models\UserInstruction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserInstructionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'about_you' => 'nullable|string|max:2000',
            'behavior' => 'nullable|string|max:2000',
            'custom_commands' => 'nullable|array',
            'custom_commands.*.name' => 'required|string|starts_with:/',
            'custom_commands.*.description' => 'required|string|max:200',
            'custom_commands.*.response' => 'required|string|max:500',
            'enabled' => 'boolean'
        ]);

        $user = Auth::user();

        $existingInstructions = $user->instructions;

        $dataToSave = [
            'about_you' => $this->mergeField($request->about_you, $existingInstructions?->about_you),
            'behavior' => $this->mergeField($request->behavior, $existingInstructions?->behavior),
            'custom_commands' => $this->mergeCommands($request->custom_commands, $existingInstructions?->custom_commands),
            'enabled' => $request->enabled ?? true
        ];

        $user->instructions()->updateOrCreate(
            ['user_id' => $user->id],
            $dataToSave
        );

        return redirect()->back()->with('message', 'Instructions saved successfully!');
    }

    private function mergeField(?string $newValue, ?string $existingValue): ?string
    {
        if ($newValue !== null) {
            return $newValue;
        }

        return $existingValue;
    }

    private function mergeCommands(?array $newCommands, ?array $existingCommands): array
    {
        if ($newCommands !== null) {
            return $newCommands;
        }

        return $existingCommands ?? [];
    }
}
