<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgebaseNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgebaseNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $searchPattern = '%'.$search.'%';

        $notes = KnowledgebaseNote::query()
            ->when($search !== '', function ($query) use ($searchPattern): void {
                $query->where(function ($searchQuery) use ($searchPattern): void {
                    $searchQuery
                        ->where('title', 'ilike', $searchPattern)
                        ->orWhere('note', 'ilike', $searchPattern);
                });
            })
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (KnowledgebaseNote $note): array => $this->transformNote($note));

        return response()->json($notes);
    }

    public function show(KnowledgebaseNote $knowledgebaseNote): JsonResponse
    {
        return response()->json($this->transformNote($knowledgebaseNote));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $note = KnowledgebaseNote::query()->create([
            'title' => $data['title'],
            'note' => $data['note'] ?? '<p></p>',
        ]);

        return response()->json($this->transformNote($note), 201);
    }

    public function update(Request $request, KnowledgebaseNote $knowledgebaseNote): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $knowledgebaseNote->update([
            'title' => $data['title'],
            'note' => $data['note'] ?? '<p></p>',
        ]);

        return response()->json($this->transformNote($knowledgebaseNote->fresh()));
    }

    public function destroy(KnowledgebaseNote $knowledgebaseNote): JsonResponse
    {
        $knowledgebaseNote->delete();
        return response()->json(null, 204);
    }

    private function transformNote(KnowledgebaseNote $note): array
    {
        return [
            'id' => $note->id,
            'title' => $note->title,
            'note' => $note->note,
            'created_at' => $note->created_at,
            'updated_at' => $note->updated_at,
        ];
    }
}
