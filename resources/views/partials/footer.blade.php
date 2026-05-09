<footer class="border-t border-zinc-800 bg-zinc-950 mt-16">
    <div class="max-w-6xl mx-auto px-4 py-10 grid grid-cols-1 sm:grid-cols-3 gap-8 text-sm text-zinc-400">
        <div>
            <p class="font-mono font-bold text-zinc-200 mb-3">
                <span class="text-green-400">›_</span> VBlog
            </p>
            <p class="leading-relaxed text-zinc-500">
                A technical cybersecurity blog covering vulnerabilities, offensive tooling, malware analysis, and secure development practices.
            </p>
        </div>

        <div>
            <p class="font-semibold text-zinc-300 mb-3 uppercase tracking-widest text-xs">Categories</p>
            <ul class="space-y-2">
                <li><a href="{{ route('posts.index', ['category' => 'vulnerabilities']) }}" class="hover:text-zinc-200 transition-colors">Vulnerabilities</a></li>
                <li><a href="{{ route('posts.index', ['category' => 'analysis']) }}" class="hover:text-zinc-200 transition-colors">Analysis</a></li>
                <li><a href="{{ route('posts.index', ['category' => 'tools']) }}" class="hover:text-zinc-200 transition-colors">Tools</a></li>
                <li><a href="{{ route('posts.index', ['category' => 'good-practices']) }}" class="hover:text-zinc-200 transition-colors">Good Practices</a></li>
            </ul>
        </div>

        <div>
            <p class="font-semibold text-zinc-300 mb-3 uppercase tracking-widest text-xs">Community</p>
            <ul class="space-y-2">
                <li><a href="{{ route('posts.index') }}" class="hover:text-zinc-200 transition-colors">All Posts</a></li>
                <li><a href="{{ route('signup') }}" class="hover:text-zinc-200 transition-colors">Join VBlog</a></li>
                <li><a href="{{ route('login') }}" class="hover:text-zinc-200 transition-colors">Sign in</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-zinc-800 py-4 text-center text-xs text-zinc-600">
        © {{ date('Y') }} VBlog — For educational and research use only.
    </div>
</footer>
