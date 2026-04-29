<div class="border-t border-zinc-700 py-4">
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
            <p class="text-sm text-gray-400 mb-2">
                {{ $comment->user_id ? \App\Models\User::find($comment->user_id)?->name ?? 'Ezezaguna' : 'Ez dago' }}
                | Sortua: {{ $comment->created_at->format('Y-m-d H:i') }}
            </p>

            {{-- Modo lectura --}}
            <p id="comment-text-{{ $comment->id }}" class="text-zinc-200">{{ $comment->content }}</p>

            {{-- Modo edición (oculto por defecto) --}}
            @auth
            @if(Auth::id() === $comment->user_id || Auth::user()->role === 'admin')
            <form id="comment-edit-form-{{ $comment->id }}"
                  method="POST"
                  action="{{ route('comments.update', $comment->id) }}"
                  class="hidden mt-2">
                @csrf
                @method('PUT')
                <textarea name="content" rows="3"
                    class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded-md text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $comment->content }}</textarea>
                <div class="flex gap-2 mt-2">
                    <button type="submit"
                        class="bg-blue-600 text-white py-1 px-3 rounded text-sm hover:bg-blue-700 transition-colors">
                        Gorde
                    </button>
                    <button type="button"
                        onclick="toggleEdit({{ $comment->id }})"
                        class="bg-zinc-600 text-white py-1 px-3 rounded text-sm hover:bg-zinc-500 transition-colors">
                        Utzi
                    </button>
                </div>
            </form>
            @endif
            @endauth
        </div>

        {{-- Botones edit/delete (solo autor o admin) --}}
        @auth
        @if(Auth::id() === $comment->user_id || Auth::user()->role === 'admin')
        <div class="flex items-center gap-3 shrink-0">
            <button onclick="toggleEdit({{ $comment->id }})"
                class="text-blue-400 hover:text-blue-300 text-sm transition-colors">
                Editatu
            </button>
            <form method="POST" action="{{ route('comments.destroy', $comment->id) }}"
                  onsubmit="return confirm('Iruzkina ezabatu nahi duzu?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="text-red-400 hover:text-red-300 text-sm transition-colors">
                    Ezabatu
                </button>
            </form>
        </div>
        @endif
        @endauth
    </div>
</div>

<script>
function toggleEdit(id) {
    const text = document.getElementById('comment-text-' + id);
    const form = document.getElementById('comment-edit-form-' + id);
    text.classList.toggle('hidden');
    form.classList.toggle('hidden');
}
</script>