<div class="border-t border-zinc-800 py-5">
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
            <p class="text-xs text-zinc-500 mb-2">
                {{ $comment->user_id ? \App\Models\User::find($comment->user_id)?->name ?? 'Unknown' : 'Anonymous' }}
                <span class="mx-1">·</span>
                {{ $comment->created_at->format('M j, Y · H:i') }}
            </p>

            <p id="comment-text-{{ $comment->id }}" class="text-zinc-300 text-sm leading-relaxed">{!! $comment->content !!}</p>

            @auth
            @if(Auth::id() === $comment->user_id || Auth::user()->role === 'admin')
            <form id="comment-edit-form-{{ $comment->id }}"
                  method="POST"
                  action="{{ route('comments.update', $comment->id) }}"
                  class="hidden mt-3">
                @csrf
                @method('PUT')
                <textarea name="content" rows="3"
                    class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded text-zinc-100 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ $comment->content }}</textarea>
                <div class="flex gap-2 mt-2">
                    <button type="submit" class="bg-blue-600 text-white py-1 px-3 rounded text-xs hover:bg-blue-700 transition-colors">Save</button>
                    <button type="button" onclick="toggleEdit({{ $comment->id }})"
                        class="bg-zinc-600 text-white py-1 px-3 rounded text-xs hover:bg-zinc-500 transition-colors">Cancel</button>
                </div>
            </form>
            @endif
            @endauth
        </div>

        @auth
        @if(Auth::id() === $comment->user_id || Auth::user()->role === 'admin')
        <div class="flex items-center gap-3 shrink-0 pt-1">
            <button onclick="toggleEdit({{ $comment->id }})" class="text-xs text-zinc-500 hover:text-blue-400 transition-colors">Edit</button>
            <form method="POST" action="{{ route('comments.destroy', $comment->id) }}"
                  onsubmit="return confirm('Delete this comment?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-zinc-500 hover:text-red-400 transition-colors">Delete</button>
            </form>
        </div>
        @endif
        @endauth
    </div>
</div>

<script>
function toggleEdit(id) {
    document.getElementById('comment-text-' + id).classList.toggle('hidden');
    document.getElementById('comment-edit-form-' + id).classList.toggle('hidden');
}
</script>
