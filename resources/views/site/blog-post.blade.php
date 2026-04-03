@extends('layouts.site')
@section('title', $post->seo_title ?? $post->title)
@section('description', $post->seo_description ?? $post->excerpt)
@section('content')
<article class="max-w-3xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold mb-3">{{ $post->title }}</h1>
    <p class="text-sm text-gray-400 mb-8">{{ $post->publish_at?->format('d/m/Y') }}@if($post->author) · {{ $post->author->name }}@endif</p>
    <div class="prose max-w-none">
        {!! nl2br(e($post->content)) !!}
    </div>
    <div class="mt-10 pt-6 border-t border-gray-100">
        <a href="{{ route('blog') }}" class="text-indigo-600 hover:underline">← Voltar ao blog</a>
    </div>
</article>
@endsection
