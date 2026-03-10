@php
  $width = $width ?? '32';
  $height = $height ?? '22';
@endphp

<span class="d-inline-flex align-items-center" aria-hidden="true">
  <svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 48 32" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M6 22c0-3.866 7.163-7 18-7s18 3.134 18 7c0 2.487-2.526 4.189-7.191 4.958L33 30H15l-1.809-3.042C8.526 26.189 6 24.487 6 22z" fill="#00563b"/>
    <path d="M16 15c1.399-5.533 4.519-8.5 8-8.5s6.601 2.967 8 8.5H16z" fill="#0a6f4e"/>
    <path d="M13.5 19.6h21" stroke="#f2b24c" stroke-width="1.8" stroke-linecap="round"/>
    <circle cx="24" cy="11" r="1.7" fill="#d62839"/>
  </svg>
</span>
