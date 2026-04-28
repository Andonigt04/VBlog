<div>
    <div class="bg-zinc-900 text-zinc-200 p-4 flex items-center justify-between">
        <a href="{{ route('home') }}" class="text-lg font-bold">{{ config('app.name') }}</a>
        <nav class="flex items-center space-x-6">
            <ul class="flex space-x-4">
                <li><a href="{{ route('posts.index', ['category' => 'vulnerabilities']) }}" class="hover:text-white transition">Vulnerabilities</a></li>
                <li><a href="{{ route('posts.index', ['category' => 'analysis']) }}" class="hover:text-white transition">Analysis</a></li>
                <li><a href="{{ route('posts.index', ['category' => 'tools']) }}" class="hover:text-white transition">Tools</a></li>
                <li><a href="{{ route('posts.index', ['category' => 'good-practices']) }}" class="hover:text-white transition">Good Practices</a></li>
            </ul>
            @if (!Auth::check())
                <ul class="flex flex-row space-x-4">
                    <li><a href="{{ route('login') }}" class="hover:text-white transition">Login</a></li>
                    <li><a href="{{ route('signup') }}" class="hover:text-white transition">Signup</a></li>
                </ul>
            @endif
            @auth (Auth::check())
            <ul class="flex flex-row space-x-4">
                <li><a href="{{ route('users.profile') }}" class="hover:text-white transition">Profile</a></li>
                <li><a href="{{ url('/api/logout') }}" class="hover:text-white transition">Logout</a></li>
            </ul>
            @endauth
        </nav>
    </div>
</div>
