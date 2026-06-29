@extends('layouts.imager')

@section('title', 'Habbo Imager')

@section('content')
    <div style="max-width: 800px; margin: 0 auto; text-align: center; padding: 20px 0 32px;">
        <h1 style="font-size: 32px; margin: 0 0 8px; color: var(--text-primary);">Habbo Imager</h1>
        <p style="color: var(--text-secondary); margin: 0; font-size: 15px; line-height: 1.6;">
            Render Habbo avatars with the quick imager or use the advanced dresser
            powered by local figure metadata and extracted assets.
        </p>
    </div>

    <section>
        @livewire('habbo-imager', ['defaultMode' => $defaultMode ?? 'normal'])
    </section>
@endsection
