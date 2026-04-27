<div>
    @yield('title')

    <div class="bg-zinc-900 text-zinc-200 p-4 flex items-center justify-between">
        <a href="{{ route('home') }}" class="text-lg font-bold">{{ config('app.name') }}</a>
        <nav>
            <ul class="flex space-x-4">
                <li><a href="{{ route('home') }}" class="hover:text-white transition">Home</a></li>
                <li><a href="{{ route('posts.index') }}" class="hover:text-white transition">Posts</a></li>
                <li><a href="{{ route('users.index') }}" class="hover:text-white transition">Users</a></li>
            </ul>
        </nav>
    </div>
</div>
