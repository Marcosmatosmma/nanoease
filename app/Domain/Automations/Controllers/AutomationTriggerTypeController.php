<?php

declare(strict_types=1);

namespace App\Domain\Automations\Controllers;

use App\Domain\Automations\Models\AutomationEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class AutomationTriggerTypeController extends Controller
{
    public function index(string $eventKey): JsonResponse
    {
        $event = AutomationEvent::where('key', $eventKey)
            ->where('active', true)
            ->first();

        if (!$event) {
            return response()->json([
                'message' => 'Evento não encontrado ou inativo.',
            ], 404);
        }

        $triggerTypes = $event->triggerTypes()
            ->select(['id', 'key', 'title', 'description', 'icon', 'placeholder', 'uses_ai', 'position'])
            ->get();

        return response()->json([
            'event' => [
                'id' => $event->id,
                'key' => $event->key,
                'title' => $event->title,
            ],
            'trigger_types' => $triggerTypes,
        ]);
    }
}
