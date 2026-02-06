<?php

declare(strict_types=1);

namespace App\Domain\Automations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Automations\Http\Actions\ExecuteHttpRequestAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HttpRequestController extends Controller
{
    public function test(Request $request, ExecuteHttpRequestAction $action): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
            'method' => 'required|string',
            'headers' => 'array',
            'body' => 'nullable|string',
        ]);

        $result = $action->execute(
            $request->input('url'),
            $request->input('method'),
            $request->input('headers', []),
            $request->input('body')
        );

        return response()->json($result);
    }
}
