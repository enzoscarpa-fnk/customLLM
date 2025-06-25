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

    /**
     * Update specific instruction type
     */
    public function update(Request $request)
    {
        $request->validate([
            'type' => 'required|in:about_you,behavior,custom_commands',
            'data' => 'nullable'
        ]);

        $user = Auth::user();
        $instructions = $user->instructions;

        if (!$instructions) {
            $instructions = $user->instructions()->create([
                'about_you' => '',
                'behavior' => '',
                'custom_commands' => [],
                'enabled' => true
            ]);
        }

        // Validate the data based on type
        if ($request->type === 'about_you' || $request->type === 'behavior') {
            $request->validate([
                'data' => 'nullable|string|max:2000'
            ]);
        } elseif ($request->type === 'custom_commands') {
            $request->validate([
                'data' => 'nullable|array',
                'data.*.name' => 'required|string|starts_with:/',
                'data.*.description' => 'required|string|max:200',
                'data.*.response' => 'required|string|max:500'
            ]);
        }

        $dataToUpdate = $request->data;
        if ($request->type === 'custom_commands' && is_null($dataToUpdate)) {
            $dataToUpdate = [];
        } elseif (($request->type === 'about_you' || $request->type === 'behavior') && is_null($dataToUpdate)) {
            $dataToUpdate = '';
        }

        $instructions->update([
            $request->type => $dataToUpdate
        ]);

        // Get fresh instructions data
        $user->refresh();
        $updatedInstructions = $user->getInstructionsOrDefault();

        // Log for debugging
        Log::info('UserInstructionController update - Updated instructions:', $updatedInstructions);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Instruction updated successfully',
                'instructions' => $updatedInstructions
            ]);
        }

        return redirect()->back()
            ->with('message', 'Instruction updated successfully')
            ->with('userInstructions', $updatedInstructions);
    }

    /**
     * Toggle instructions enabled/disabled
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean'
        ]);

        $user = Auth::user();
        $instructions = $user->instructions;

        if (!$instructions) {
            $instructions = $user->instructions()->create([
                'about_you' => '',
                'behavior' => '',
                'custom_commands' => [],
                'enabled' => $request->enabled
            ]);
        } else {
            $instructions->update(['enabled' => $request->enabled]);
        }

        // Get fresh instructions data
        $user->refresh();
        $updatedInstructions = $user->getInstructionsOrDefault();

        Log::info('UserInstructionController toggle - Updated instructions:', $updatedInstructions);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Instructions ' . ($request->enabled ? 'enabled' : 'disabled'),
                'instructions' => $updatedInstructions
            ]);
        }

        return redirect()->back()
            ->with('message', 'Instructions ' . ($request->enabled ? 'enabled' : 'disabled'))
            ->with('userInstructions', $updatedInstructions);
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
