<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    public function generateQuestion(Request $request)
    {
        $topic = $request->input('topic', 'basic arithmetic');

        $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a math teacher creating quiz questions.'],
                ['role' => 'user', 'content' => "Create a single multiple-choice math question on the topic: $topic. Format it like this:\n\nQuestion:\nA.\nB.\nC.\nD.\nAnswer:\nExplanation:"]
            ],
            'temperature' => 0.5,
            'max_tokens' => 300,
        ]);

        return response()->json([
            'data' => $response->json()['choices'][0]['message']['content'] ?? 'No response from AI.',
        ]);
    }
}
