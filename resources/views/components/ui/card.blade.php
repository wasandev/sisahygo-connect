@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'connect-card p-6']) }}>
    @if($title || $description)
        <div class="mb-4">
            @if($title)
                <h3 class="text-lg font-bold text-brand-navy">{{ $title }}</h3>
            @endif
            @if($description)
                <p class="mt-1 text-sm text-brand-muted">{{ $description }}</p>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>
