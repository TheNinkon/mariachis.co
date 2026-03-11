@php
  $viewErrors = $errors ?? session('errors');
@endphp

@if (session('status'))
  <div class="client-auth-alert success">{{ session('status') }}</div>
@endif

@if ($viewErrors && $viewErrors->has('auth'))
  <div class="client-auth-alert warning">{{ $viewErrors->first('auth') }}</div>
@endif
