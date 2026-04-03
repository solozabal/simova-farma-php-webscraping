@extends('layouts.site')
@section('title', 'Blog — SIMOVA FARMA')
@section('content')
<div class="max-w-5xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold mb-8">Blog SIMOVA FARMA</h1>
    @forelse($posts as $post)
    <article class="border-b border-gray-100 py-6">
        <h2 class="text-xl font-semibold mb-2"><a href="{{ route('blog.post', $post->slug) }}" class="hover:text-indigo-600">{{ $post->title }}</a></h2>
        @if($post->excerpt)<p class="text-gray-600 mb-2">{{ $post->excerpt }}</p>@endif
        <p class="text-xs text-gray-400">{{ $post->publish_at?->format('d/m/Y') }}</p>
    </article>
    @empty
    <p class="text-gray-500">Nenhum post publicado ainda.</p>
    @endforelse
    <div class="mt-8">{{ $posts->links() }}</div>
</div>
@endsection
