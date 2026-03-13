@extends('front.layouts.marketplace')

@section('body_page', 'static-page')

@section('content')
  <main>
    <section class="layout-shell py-12 md:py-16">
      <article class="listing-flow-section">
        <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-600">{{ $page['eyebrow'] }}</p>
        <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.015em] text-slate-900">{{ $page['title'] }}</h1>
        <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600">{{ $page['lead'] }}</p>

        <div class="mt-8 grid gap-4 md:grid-cols-3">
          @foreach($page['sections'] as $section)
            <section class="surface rounded-3xl p-6">
              <h2 class="text-lg font-extrabold text-slate-900">{{ $section['title'] }}</h2>
              <p class="mt-3 text-sm leading-6 text-slate-600">{{ $section['body'] }}</p>
            </section>
          @endforeach
        </div>
      </article>
    </section>
  </main>
@endsection
