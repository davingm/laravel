@props(['seo' => []])

@if (filled($seo['title'] ?? null))
    <title>{{ $seo['title'] }}</title>
@endif

@if (filled($seo['description'] ?? null))
    <meta name="description" content="{{ $seo['description'] }}">
@endif

<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">

@if (filled($seo['ogTitle'] ?? $seo['title'] ?? null))
    <meta property="og:title" content="{{ $seo['ogTitle'] ?? $seo['title'] }}">
    <meta name="twitter:title" content="{{ $seo['ogTitle'] ?? $seo['title'] }}">
@endif

@if (filled($seo['ogDescription'] ?? $seo['description'] ?? null))
    <meta property="og:description" content="{{ $seo['ogDescription'] ?? $seo['description'] }}">
    <meta name="twitter:description" content="{{ $seo['ogDescription'] ?? $seo['description'] }}">
@endif

@if (filled($seo['ogImage'] ?? null))
    <meta property="og:image" content="{{ $seo['ogImage'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $seo['ogImage'] }}">
@endif

@if (filled($seo['articleAuthor'] ?? null))
    <meta property="article:author" content="{{ $seo['articleAuthor'] }}">
@endif