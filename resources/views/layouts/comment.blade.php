<div class="border-t border-zinc-700 py-4">
    <p class="text-sm text-gray-400 mb-2">Iruzkina: {{ $comment->user_id ? \App\Models\User::find($comment->user_id)->name : 'Ez dago' }} | Sortua: {{ $comment->created_at->format('Y-m-d H:i') }}</p>
    <p>{{ $comment->content }}</p>
</div>