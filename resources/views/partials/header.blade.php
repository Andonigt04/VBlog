<header class="bg-zinc-950 border-b border-zinc-800 text-zinc-300">
    <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-14">
        <a href="{{ route('home') }}" class="font-mono font-bold text-zinc-100 tracking-tight flex items-center gap-1">
            <span class="text-green-400 text-lg">›_</span>
            <span>VBlog</span>
        </a>

        <nav class="hidden md:flex items-center gap-6 text-sm text-zinc-400">
            <a href="{{ route('posts.index', ['category' => 'vulnerabilities']) }}" class="hover:text-zinc-100 transition-colors">Vulnerabilities</a>
            <a href="{{ route('posts.index', ['category' => 'analysis']) }}" class="hover:text-zinc-100 transition-colors">Analysis</a>
            <a href="{{ route('posts.index', ['category' => 'tools']) }}" class="hover:text-zinc-100 transition-colors">Tools</a>
            <a href="{{ route('posts.index', ['category' => 'good-practices']) }}" class="hover:text-zinc-100 transition-colors">Good Practices</a>
        </nav>

        <div class="flex items-center gap-4 text-sm">
            @auth
                @if(in_array(Auth::user()->role, ['editor', 'admin']))
                    <a href="{{ route('posts.create') }}" class="text-zinc-400 hover:text-zinc-100 transition-colors">Write</a>
                @endif
                <a href="{{ route('users.profile') }}" class="text-zinc-400 hover:text-zinc-100 transition-colors truncate max-w-32">{{ Auth::user()->name }}</a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-zinc-500 hover:text-zinc-300 transition-colors text-xs">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-zinc-400 hover:text-zinc-100 transition-colors">Login</a>
                <a href="{{ route('signup') }}" class="bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-zinc-200 text-sm px-3 py-1 rounded transition-colors">Sign up</a>
            @endauth
        </div>
    </div>
</header>
