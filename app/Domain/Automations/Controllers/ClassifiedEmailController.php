<?php

declare(strict_types=1);

namespace App\Domain\Automations\Controllers;

use App\Domain\Automations\Models\ClassifiedEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class ClassifiedEmailController
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $automationId = $request->integer('automation_id');

        $query = ClassifiedEmail::query()
            ->where('user_id', $user->id)
            ->with(['automation'])
            ->orderBy('email_date', 'desc');

        if ($automationId) {
            $query->where('automation_id', $automationId);
        }

        $emails = $query->paginate(20);

        $automations = \App\Domain\Automations\Models\Automation::query()
            ->where('user_id', $user->id)
            ->whereNotNull('gmail_label')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'rule_text', 'gmail_label', 'status']);

        return Inertia::render('OrganizedEmails/Index', [
            'emails' => $emails,
            'automations' => $automations,
            'selectedAutomationId' => $automationId ?: null,
        ]);
    }
}
