@php
  $editorMode = $editorMode ?? request()->query('editor') === 'fullscreen';
  if ($editorMode) {
    $pageConfigs = ['myLayout' => 'blank'];
  }
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Editar anuncio')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('page-style')
  <style>
    @if($editorMode)
    body {
      background: #eef2f6;
    }

    .listing-editor-fullscreen {
      height: 100dvh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background:
        linear-gradient(180deg, rgba(244, 247, 250, 0.98) 0%, rgba(238, 242, 246, 1) 100%);
    }

    .listing-editor-fullscreen__toolbar {
      position: sticky;
      top: 0;
      z-index: 40;
      block-size: 4.25rem;
      min-block-size: 4.25rem;
      max-block-size: 4.25rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 0 1rem;
      border-bottom: 1px solid rgba(75, 70, 92, 0.08);
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(10px);
    }

    .listing-editor-fullscreen__toolbar-group {
      display: flex;
      align-items: center;
      block-size: 100%;
      gap: 0.75rem;
      min-width: 0;
    }

    .listing-editor-fullscreen__toolbar-group--actions {
      justify-content: flex-end;
      flex-wrap: nowrap;
    }

    .listing-editor-fullscreen__title {
      min-width: 0;
      display: grid;
      align-content: center;
      gap: 0.15rem;
      line-height: 1.1;
    }

    .listing-editor-fullscreen__title .small {
      line-height: 1;
      margin: 0;
    }

    .listing-editor-fullscreen__title strong {
      line-height: 1.1;
    }

    .listing-editor-fullscreen__title strong,
    .listing-editor-fullscreen__title span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .listing-editor-fullscreen__viewport {
      flex: 1 1 auto;
      min-height: 0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      padding: 0;
    }

    .listing-editor-fullscreen .card.mb-6 {
      margin-bottom: 0.85rem !important;
    }

    .listing-editor-fullscreen #listing-wizard {
      margin-bottom: 0 !important;
      flex: 1 1 auto;
      min-height: 0;
      display: grid;
      grid-template-columns: 18rem minmax(0, 1fr);
      overflow: hidden;
    }

    .listing-editor-fullscreen .bs-stepper.vertical {
      height: 100%;
      min-height: 0;
      border: 0;
      border-radius: 0;
      overflow: visible;
      background: transparent;
      box-shadow: none;
    }

    .listing-editor-fullscreen .bs-stepper-header {
      position: relative;
      display: block;
      grid-column: 1;
      min-height: 100%;
      max-height: none;
      min-inline-size: 18rem !important;
      inline-size: 18rem;
      overflow: auto;
      padding: 0.7rem 0.65rem;
      background: #fff;
      border-right: 1px solid rgba(75, 70, 92, 0.08);
    }

    .listing-editor-fullscreen .bs-stepper-header .step {
      display: block;
      width: 100%;
      margin-bottom: 0.25rem;
    }

    .listing-editor-fullscreen .bs-stepper-header .line {
      display: none;
    }

    .listing-editor-fullscreen .bs-stepper-content {
      grid-column: 2;
      min-width: 0;
      min-height: 0;
      height: 100%;
      display: flex;
      flex-direction: column;
      padding: 0;
      background: linear-gradient(180deg, #f6f8fb 0%, #edf2f7 100%);
      overflow: hidden;
    }

    .listing-editor-fullscreen .listing-editor-form-shell {
      display: flex;
      flex: 1 1 auto;
      flex-direction: column;
      min-height: 0;
    }

    .listing-editor-fullscreen .listing-editor-form-shell__body {
      display: flex;
      flex-direction: column;
      flex: 1 1 auto;
      min-height: 0;
      overflow: hidden;
    }

    .listing-editor-fullscreen .listing-editor-form-shell__body > .content {
      flex: 1 1 auto;
      height: 100%;
      min-height: 0;
      overflow: hidden;
      width: 100%;
    }

    .listing-editor-fullscreen .listing-editor-step-layout {
      height: 100%;
      min-height: 0;
      display: flex;
      flex-direction: column;
    }

    .listing-editor-fullscreen .listing-editor-step-scroll {
      display: flex;
      flex-direction: column;
      flex: 1 1 auto;
      min-height: 0;
      overflow: auto;
      padding: 1.25rem 1.5rem 0.75rem;
      scrollbar-gutter: stable;
    }

    .listing-editor-fullscreen .listing-editor-step-frame {
      display: flex;
      flex: 0 0 auto;
      flex-direction: column;
      gap: 1.25rem;
      width: 100%;
      min-height: 100%;
      padding: 1.25rem;
      border: 1px solid rgba(75, 70, 92, 0.08);
      border-radius: 1.25rem;
      background: rgba(255, 255, 255, 0.96);
      box-shadow: 0 1rem 2rem -1.7rem rgba(15, 23, 42, 0.18);
    }

    .listing-editor-fullscreen .listing-editor-step-frame > .row {
      --bs-gutter-x: 1.5rem;
      --bs-gutter-y: 1.5rem;
      width: 100%;
      margin-bottom: 0;
      margin-left: 0;
      margin-right: 0;
    }

    .listing-editor-fullscreen .listing-editor-step-frame > .row > [class*='col-'] {
      padding-left: calc(var(--bs-gutter-x) * 0.5);
      padding-right: calc(var(--bs-gutter-x) * 0.5);
    }

    .listing-editor-fullscreen .bs-stepper .step-trigger {
      display: grid;
      grid-template-columns: 3rem minmax(0, 1fr);
      align-items: center;
      justify-content: flex-start;
      column-gap: 1rem;
      width: 100%;
      min-height: 4rem;
      border-radius: 1.1rem;
      padding: 0.6rem 0.85rem;
      text-align: left;
      border: 1px solid transparent;
      background: transparent;
      transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .listing-editor-fullscreen .bs-stepper .step-trigger:hover {
      background: linear-gradient(90deg, rgba(0, 86, 59, 0.04) 0%, rgba(0, 86, 59, 0.02) 100%);
      border-color: rgba(0, 86, 59, 0.07);
      transform: translateX(2px);
    }

    .listing-editor-fullscreen .bs-stepper .step.active .step-trigger {
      background: linear-gradient(90deg, rgba(233, 245, 239, 0.95) 0%, rgba(246, 251, 248, 0.98) 100%);
      border-color: rgba(0, 86, 59, 0.1);
      box-shadow: 0 12px 28px -22px rgba(0, 86, 59, 0.22);
    }

    .listing-editor-fullscreen .bs-stepper .step-trigger .bs-stepper-circle {
      display: grid;
      place-items: center;
      inline-size: 2.55rem;
      block-size: 2.55rem;
      flex: 0 0 auto;
      margin: 0;
      border-radius: 0.9rem;
      background: #f6f8f9;
      color: #6d6b77;
      box-shadow: inset 0 0 0 1px rgba(75, 70, 92, 0.06);
      transition: background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .listing-editor-fullscreen .bs-stepper .step-trigger:hover .bs-stepper-circle {
      background: #edf6f1;
      color: #00563b;
      box-shadow:
        inset 0 0 0 1px rgba(0, 86, 59, 0.08),
        0 8px 18px -16px rgba(0, 86, 59, 0.35);
    }

    .listing-editor-fullscreen .bs-stepper .step.active .step-trigger .bs-stepper-circle {
      background: linear-gradient(180deg, #0b6a4a 0%, #00563b 100%);
      color: #00563b;
      box-shadow:
        0 14px 26px -18px rgba(0, 86, 59, 0.55),
        0 0 0 4px rgba(255, 255, 255, 0.7);
      color: #fff;
      transform: translateX(0);
    }

    .listing-editor-fullscreen .bs-stepper .step-trigger .bs-stepper-label {
      min-width: 0;
      display: grid;
      align-content: center;
      gap: 0.12rem;
      flex: 1 1 auto;
    }

    .listing-editor-fullscreen .bs-stepper .step-trigger .bs-stepper-title,
    .listing-editor-fullscreen .bs-stepper .step-trigger .bs-stepper-subtitle {
      display: block;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .listing-editor-fullscreen .bs-stepper .step-trigger .bs-stepper-title {
      font-weight: 700;
      color: #444050;
      line-height: 1.15;
    }

    .listing-editor-fullscreen .bs-stepper .step-trigger .bs-stepper-subtitle {
      font-size: 0.9rem;
      line-height: 1.15;
      color: #8a8d93;
    }

    .listing-editor-fullscreen .bs-stepper .step.active .bs-stepper-title {
      color: #2f2b3d;
    }

    .listing-editor-fullscreen .listing-step-actions {
      display: none !important;
    }

    .listing-editor-fullscreen .listing-editor-global-footer {
      z-index: 8;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1rem 1.5rem;
      border-top: 1px solid rgba(75, 70, 92, 0.08);
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(12px);
      box-shadow: 0 -18px 40px -34px rgba(15, 23, 42, 0.45);
      flex: 0 0 auto;
    }

    .listing-editor-fullscreen .listing-editor-global-footer__actions {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 0.75rem;
      width: 100%;
    }

    .listing-editor-fullscreen .listing-editor-global-footer .btn {
      min-width: 10rem;
    }

    .listing-editor-fullscreen .alert {
      border-radius: 1rem;
    }

    .listing-editor-fullscreen.is-nav-collapsed .bs-stepper.vertical {
      display: grid;
    }

    .listing-editor-fullscreen.is-nav-collapsed #listing-wizard {
      grid-template-columns: minmax(0, 1fr);
    }

    .listing-editor-fullscreen.is-nav-collapsed .bs-stepper-header {
      display: none;
    }

    @media (max-width: 991.98px) {
      .listing-editor-fullscreen .bs-stepper.vertical {
        height: auto;
      }

      .listing-editor-fullscreen #listing-wizard {
        grid-template-columns: 1fr;
      }

      .listing-editor-fullscreen .bs-stepper-header,
      .listing-editor-fullscreen .bs-stepper-content {
        min-height: 0;
        max-height: none;
      }

      .listing-editor-fullscreen .bs-stepper-header {
        border-right: 0;
        border-bottom: 1px solid rgba(75, 70, 92, 0.08);
      }

      .listing-editor-fullscreen.is-nav-collapsed .bs-stepper.vertical {
        display: block;
      }

      .listing-editor-fullscreen .listing-editor-fullscreen__toolbar {
        height: auto;
        min-height: 4.25rem;
        align-items: flex-start;
        flex-direction: column;
        padding-block: 0.85rem;
      }

      .listing-editor-fullscreen__viewport {
        padding: 0;
      }

      .listing-editor-fullscreen .listing-editor-step-scroll {
        padding-inline: 1rem;
        padding-top: 1rem;
      }

      .listing-editor-fullscreen .listing-editor-global-footer {
        padding-inline: 1rem;
      }
    }
    @endif

    .listing-zone-shell,
    .listing-media-shell {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
    }

    .listing-zone-panel,
    .listing-media-panel {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
    }

    .listing-zone-panel--localities {
      position: relative;
      isolation: isolate;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      max-height: 25.5rem;
      overflow-y: auto;
      padding: 0.9rem !important;
      scrollbar-gutter: stable;
      background: #fff;
    }

    .listing-zone-panel__header {
      position: sticky;
      top: -0.9rem;
      z-index: 2;
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
      margin: -0.9rem -0.9rem 0;
      padding: 1rem 0.9rem 0.7rem;
      background: linear-gradient(180deg, #fff 0%, #fff 88%, rgba(255, 255, 255, 0.98) 100%);
      border-bottom: 1px solid rgba(75, 70, 92, 0.08);
      box-shadow: 0 10px 18px -18px rgba(75, 70, 92, 0.45);
    }

    .listing-zone-panel__header-main {
      width: 100%;
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .listing-zone-panel__search {
      width: 100%;
      background: #fff;
      z-index: 3;
      padding-top: 0.1rem;
      padding-bottom: 0.1rem;
    }

    .listing-zone-panel__hint {
      display: block;
      margin: 0;
      line-height: 1.45;
    }

    .listing-zone-panel__hint.is-limit {
      color: #8a5d00 !important;
      font-weight: 500;
    }

    .listing-zone-panel__upgrade-link[hidden] {
      display: none !important;
    }

    .listing-zone-list,
    .listing-video-list {
      display: grid;
      gap: 0.75rem;
    }

    .listing-zone-list {
      min-height: 18rem;
      max-height: 26rem;
      overflow: auto;
      padding-right: 0.25rem;
    }

    .listing-zone-list--compact {
      min-height: 0;
      max-height: none;
      overflow: visible;
      gap: 0.55rem;
      padding-right: 0;
    }

    .listing-zone-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.6rem;
      padding: 0.7rem 0.8rem;
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 0.75rem;
      background: #fff;
    }

    .listing-zone-item__meta {
      min-width: 0;
      display: grid;
      gap: 0.125rem;
    }

    .listing-zone-item__name {
      font-weight: 600;
      font-size: 0.875rem;
      line-height: 1.2;
      color: #444050;
    }

    .listing-zone-item__city {
      font-size: 0.75rem;
      line-height: 1.2;
      color: #8a8d93;
    }

    .listing-zone-list--compact .listing-zone-item .btn {
      padding: 0.3rem 0.55rem;
      line-height: 1.1;
    }

    .listing-zone-item--selected {
      border-color: rgba(0, 86, 59, 0.2);
      background: rgba(0, 86, 59, 0.04);
    }

    .listing-zone-item--primary {
      border-color: rgba(0, 86, 59, 0.3);
      background: rgba(0, 86, 59, 0.08);
    }

    .listing-zone-empty,
    .listing-upgrade-tile,
    .listing-photo-add-tile,
    .listing-video-upgrade {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      text-align: center;
      min-height: 11rem;
      padding: 1.25rem;
      border-radius: 1rem;
      border: 1px dashed rgba(75, 70, 92, 0.18);
      background: rgba(115, 103, 240, 0.03);
    }

    .listing-upgrade-tile {
      border-style: solid;
      background: linear-gradient(180deg, rgba(255, 193, 7, 0.12), rgba(255, 255, 255, 0.92));
    }

    button.listing-upgrade-tile {
      width: 100%;
      appearance: none;
      cursor: pointer;
    }

    .listing-zone-list--compact .listing-zone-empty {
      min-height: 7rem;
      padding: 1rem;
      border-radius: 0.85rem;
    }

    .listing-upgrade-tile--compact {
      min-height: auto;
      padding: 0.9rem 1rem;
      align-items: flex-start;
      text-align: left;
      gap: 0.35rem;
      border-radius: 0.85rem;
    }

    .listing-upgrade-tile--compact .avatar {
      width: 2.4rem;
      height: 2.4rem;
      margin-bottom: 0.15rem !important;
    }

    .listing-upgrade-tile--compact strong {
      font-size: 0.92rem;
      line-height: 1.2;
    }

    .listing-upgrade-tile--compact .small {
      line-height: 1.3;
      margin-top: 0 !important;
    }

    .listing-photo-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1rem;
    }

    .listing-photo-add-tile {
      cursor: pointer;
      min-height: 14rem;
      background: rgba(0, 86, 59, 0.03);
      border-style: dashed;
    }

    .listing-photo-add-tile[disabled] {
      cursor: not-allowed;
      opacity: 0.65;
    }

    .listing-photo-card {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      overflow: hidden;
      background: #fff;
      display: flex;
      flex-direction: column;
      min-height: 14rem;
    }

    .listing-photo-card__media {
      position: relative;
      aspect-ratio: 1 / 1;
      overflow: hidden;
      background: #f8f7fa;
    }

    .listing-photo-card__media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .listing-photo-card__badges {
      position: absolute;
      top: 0.75rem;
      left: 0.75rem;
      right: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
    }

    .listing-photo-card__actions,
    .listing-video-card__actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .listing-photo-card__body,
    .listing-video-card {
      padding: 1rem;
    }

    .listing-video-card {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      align-items: start;
    }

    .listing-video-card__meta {
      min-width: 0;
    }

    .listing-video-card__url {
      font-weight: 600;
      color: #444050;
      word-break: break-word;
    }

    .listing-step-note {
      color: #8a8d93;
      font-size: 0.9375rem;
    }

    .listing-step-actions {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
    }

    .listing-base-price-card {
      display: grid;
      gap: 0.85rem;
      padding: 1rem;
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: linear-gradient(180deg, rgba(0, 86, 59, 0.05), rgba(255, 255, 255, 0.98));
    }

    .listing-base-price-card__value {
      display: flex;
      align-items: end;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .listing-base-price-card__amount {
      font-size: 1.55rem;
      line-height: 1;
      font-weight: 800;
      color: #00563b;
      letter-spacing: -0.02em;
    }

    .listing-base-price-card__range {
      width: 100%;
      accent-color: #00563b;
    }

    .listing-base-price-card__scale {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      font-size: 0.75rem;
      color: #8a8d93;
    }

    .listing-inline-note {
      font-size: 0.8rem;
      line-height: 1.35;
      color: #8a8d93;
    }

    .listing-owner-actions {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .listing-rich-editor {
      position: relative;
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
      overflow: hidden;
    }

    .listing-rich-editor__toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      padding: 0.85rem;
      border-bottom: 1px solid rgba(75, 70, 92, 0.1);
      background: #f8f7fa;
    }

    .listing-rich-editor__toolbar .btn {
      min-width: 2.5rem;
    }

    .listing-rich-editor__link-panel {
      position: absolute;
      top: 4.35rem;
      right: 1rem;
      width: min(25rem, calc(100% - 2rem));
      z-index: 5;
      display: grid;
      gap: 0.85rem;
      padding: 1rem;
      border: 1px solid rgba(75, 70, 92, 0.14);
      border-radius: 1rem;
      background: #fff;
      box-shadow: 0 18px 44px -28px rgba(47, 43, 61, 0.45);
    }

    .listing-rich-editor__link-panel[hidden] {
      display: none !important;
    }

    .listing-rich-editor__link-head {
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 0.75rem;
    }

    .listing-rich-editor__link-actions {
      display: flex;
      justify-content: flex-end;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .listing-rich-editor__link-error:empty {
      display: none;
    }

    .listing-rich-editor__surface {
      min-height: 17rem;
      padding: 1rem;
      outline: none;
      line-height: 1.6;
      color: #444050;
    }

    .listing-rich-editor__surface:empty::before {
      content: attr(data-placeholder);
      color: #8a8d93;
    }

    .listing-rich-editor__surface h2,
    .listing-rich-editor__surface h3 {
      margin-top: 0.95rem;
      margin-bottom: 0.6rem;
      color: #2f2b3d;
    }

    .listing-rich-editor__surface p,
    .listing-rich-editor__surface ul,
    .listing-rich-editor__surface ol {
      margin-bottom: 0.8rem;
    }

    .listing-rich-editor__surface ul,
    .listing-rich-editor__surface ol {
      padding-left: 1.25rem;
    }

    .listing-rich-editor__hint {
      padding: 0.75rem 1rem 0.95rem;
      border-top: 1px solid rgba(75, 70, 92, 0.08);
      background: rgba(0, 86, 59, 0.03);
      font-size: 0.8rem;
      color: #6d6b77;
    }

    .listing-coverage-toggle {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      padding: 1rem;
      background: #fff;
    }

    .listing-coverage-toggle.is-disabled {
      background: #f8f7fa;
      border-style: dashed;
    }

    .listing-filter-card,
    .payment-plan-card {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
      height: 100%;
    }

    .listing-final-card {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
      height: auto;
    }

    .listing-filter-card {
      padding: 1rem;
    }

    .listing-filter-card__head {
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 0.75rem;
      margin-bottom: 0.35rem;
    }

    .listing-filter-card__body {
      display: grid;
      gap: 0.55rem;
    }

    .listing-filter-card__body .form-check.is-disabled {
      opacity: 0.55;
    }

    .listing-filter-upgrade[hidden] {
      display: none !important;
    }

    .payment-billing-switch {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1rem 1.1rem;
      border: 1px solid rgba(75, 70, 92, 0.1);
      border-radius: 1.2rem;
      background: linear-gradient(180deg, rgba(0, 86, 59, 0.04), rgba(255, 255, 255, 0.98));
      margin-bottom: 1.25rem;
    }

    .payment-billing-switch__copy {
      display: grid;
      gap: 0.25rem;
    }

    .payment-billing-switch__eyebrow {
      font-size: 0.76rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #00563b;
    }

    .payment-billing-switch__title {
      font-size: 1rem;
      font-weight: 700;
      color: #2f2b3d;
    }

    .payment-billing-switch__hint {
      font-size: 0.84rem;
      color: #8a8d93;
    }

    .payment-billing-switch__group {
      display: inline-grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.45rem;
      padding: 0.35rem;
      border-radius: 999px;
      background: rgba(75, 70, 92, 0.06);
      min-width: min(100%, 28rem);
    }

    .payment-billing-switch__button {
      border: 0;
      border-radius: 999px;
      background: transparent;
      padding: 0.7rem 1rem;
      display: grid;
      gap: 0.15rem;
      text-align: center;
      color: #6d6b77;
      transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
    }

    .payment-billing-switch__button strong {
      font-size: 0.96rem;
      line-height: 1.1;
    }

    .payment-billing-switch__button span {
      font-size: 0.76rem;
      font-weight: 600;
      color: inherit;
      opacity: 0.85;
    }

    .payment-billing-switch__button.is-active {
      background: #fff;
      color: #00563b;
      box-shadow: 0 0.65rem 1.4rem -1rem rgba(34, 41, 47, 0.32);
    }

    .payment-plan-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
      gap: 1.1rem;
      align-items: stretch;
    }

    .payment-plan-card {
      position: relative;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1.35rem;
      background: #fff;
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .payment-plan-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 1.2rem 2.4rem -1.6rem rgba(34, 41, 47, 0.28);
      border-color: rgba(0, 86, 59, 0.22);
    }

    .payment-plan-card.is-current {
      border-color: rgba(0, 86, 59, 0.28);
      box-shadow: 0 1.1rem 2rem -1.7rem rgba(0, 86, 59, 0.34);
    }

    .payment-plan-card__body {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      height: 100%;
      padding: 1.35rem;
    }

    .payment-plan-card__head {
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 0.75rem;
    }

    .payment-plan-card__title {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.55rem;
      margin: 0;
      font-size: 1.55rem;
      line-height: 1.1;
    }

    .payment-plan-card__description {
      margin: 0;
      min-height: 3.9rem;
      color: #6d6b77;
      line-height: 1.5;
    }

    .payment-plan-card__pricing {
      display: grid;
      gap: 0.6rem;
      padding: 1rem;
      border-radius: 1rem;
      background: linear-gradient(180deg, rgba(0, 86, 59, 0.06), rgba(255, 255, 255, 0.98));
      border: 1px solid rgba(0, 86, 59, 0.1);
    }

    .payment-plan-card__price {
      margin: 0;
      display: flex;
      align-items: end;
      gap: 0.45rem;
      flex-wrap: wrap;
      font-size: 2rem;
      line-height: 1;
      font-weight: 800;
      color: #2f2b3d;
      letter-spacing: -0.03em;
    }

    .payment-plan-card__price small {
      font-size: 0.84rem;
      font-weight: 700;
      color: #8a8d93;
      letter-spacing: 0;
      padding-bottom: 0.2rem;
    }

    .payment-plan-card__billing {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .payment-plan-card__billing-copy {
      display: grid;
      gap: 0.2rem;
      color: #8a8d93;
      font-size: 0.82rem;
    }

    .payment-plan-card__billing-copy strong {
      color: #444050;
      font-size: 0.92rem;
    }

    .payment-plan-card__savings {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 0.35rem 0.7rem;
      background: rgba(40, 199, 111, 0.14);
      color: #146c43;
      font-size: 0.78rem;
      font-weight: 700;
    }

    .payment-plan-card__savings.is-muted {
      background: rgba(75, 70, 92, 0.06);
      color: #6d6b77;
    }

    .payment-plan-card__features {
      display: grid;
      gap: 0.55rem;
      margin: 0;
      padding: 0.15rem 0 0;
      list-style: none;
    }

    .payment-plan-card__features li {
      display: flex;
      align-items: start;
      gap: 0.6rem;
      color: #6d6b77;
      line-height: 1.4;
    }

    .payment-plan-card__features li::before {
      content: '';
      width: 0.46rem;
      height: 0.46rem;
      border-radius: 999px;
      background: rgba(0, 86, 59, 0.35);
      margin-top: 0.42rem;
      flex-shrink: 0;
    }

    .payment-plan-card__actions {
      margin-top: auto;
      padding-top: 0.3rem;
    }

    .payment-sheet.offcanvas-bottom {
      height: auto;
      max-height: 92vh;
      border-top-left-radius: 1.25rem;
      border-top-right-radius: 1.25rem;
    }

    .payment-sheet-qr {
      width: 100%;
      max-width: 260px;
      border-radius: 1rem;
      border: 1px solid rgba(34, 41, 47, 0.08);
      background: rgba(34, 41, 47, 0.03);
    }

    .payment-sheet-placeholder {
      min-height: 220px;
      border: 1px dashed rgba(34, 41, 47, 0.16);
      border-radius: 1rem;
      color: var(--bs-secondary-color);
    }

    .listing-faq-shell {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
      padding: 1rem;
    }

    .listing-faq-system-list,
    .listing-faq-user-list {
      display: grid;
      gap: 0.85rem;
    }

    .listing-faq-card {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 0.9rem;
      padding: 0.95rem 1rem;
      background: #fff;
    }

    .listing-faq-card--system {
      background: linear-gradient(180deg, rgba(0, 86, 59, 0.05), rgba(255, 255, 255, 0.98));
      border-color: rgba(0, 86, 59, 0.16);
    }

    .listing-faq-card__head {
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 0.75rem;
      margin-bottom: 0.55rem;
    }

    .listing-faq-card__question {
      font-weight: 700;
      color: #444050;
      line-height: 1.3;
      margin: 0;
    }

    .listing-faq-card__answer {
      margin: 0;
      font-size: 0.875rem;
      line-height: 1.5;
      color: #6d6b77;
    }

    .listing-faq-user-card {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 0.9rem;
      background: #fff;
      padding: 0.95rem;
      box-shadow: 0 12px 28px -28px rgba(75, 70, 92, 0.5);
    }

    .listing-faq-user-card__head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      margin-bottom: 0.85rem;
    }

    .listing-faq-user-card__tools {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .listing-faq-handle {
      border: 0;
      background: transparent;
      color: #8a8d93;
      cursor: grab;
      padding: 0.15rem;
      line-height: 1;
    }

    .listing-faq-handle:active {
      cursor: grabbing;
    }

    .listing-faq-user-list .sortable-ghost {
      opacity: 0.5;
    }

    .listing-location-trigger {
      min-width: 7rem;
    }

    .listing-location-status[hidden] {
      display: none !important;
    }

    .listing-map-canvas {
      min-height: 65vh;
      border-radius: 1rem;
      border: 1px solid rgba(75, 70, 92, 0.12);
      background: linear-gradient(180deg, rgba(0, 86, 59, 0.06), rgba(255, 255, 255, 0.96));
      overflow: hidden;
    }

    .listing-map-sidebar {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
      padding: 1.25rem;
      min-height: 100%;
    }

    .listing-map-sidebar dd {
      word-break: break-word;
    }

    @media (max-width: 991.98px) {
      .listing-zone-panel--localities {
        max-height: none;
        overflow: visible;
      }

      .listing-zone-panel__header,
      .listing-zone-panel__search {
        position: static;
      }

      .listing-zone-list {
        min-height: 13rem;
        max-height: none;
      }

      .listing-zone-list--compact {
        min-height: 0;
      }

      .listing-step-actions {
        flex-direction: column-reverse;
      }

      .listing-step-actions .btn,
      .listing-editor-fullscreen .listing-step-actions .btn {
        width: 100%;
      }

      .listing-owner-actions {
        justify-content: flex-start;
      }

      .listing-rich-editor__link-panel {
        position: static;
        width: auto;
        margin: 0 0.85rem 0.85rem;
        box-shadow: none;
      }

      .payment-billing-switch {
        flex-direction: column;
        align-items: stretch;
      }

      .payment-billing-switch__group {
        min-width: 0;
        width: 100%;
      }

      .payment-plan-grid {
        grid-template-columns: 1fr;
      }

      .payment-plan-card__description {
        min-height: 0;
      }
    }
  </style>
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/mariachi-listing-wizard.js'])
  @if($editorMode)
    @vite(['resources/assets/js/mariachi-listing-editor-shell.js'])
  @endif
@endsection

@section('content')
  @php
    $selectedEventTypeIds = $listing->eventTypes->pluck('id')->all();
    $selectedServiceTypeIds = $listing->serviceTypes->pluck('id')->all();
    $selectedGroupSizeIds = $listing->groupSizeOptions->pluck('id')->all();
    $selectedBudgetIds = $listing->budgetRanges->pluck('id')->all();
    $selectedZoneIds = $listing->serviceAreas->pluck('marketplace_zone_id')->filter()->map(fn ($id) => (int) $id)->all();
    $selectedCityId = (int) old('marketplace_city_id', $listing->marketplace_city_id);
    $primaryZoneId = (int) old('primary_marketplace_zone_id', $selectedZoneIds[0] ?? 0);
    $formSelectedZoneIds = collect(old('zone_ids', $selectedZoneIds))
      ->map(fn ($id) => (int) $id)
      ->filter(fn ($id) => $id > 0 && $id !== $primaryZoneId)
      ->values()
      ->all();
    $displayCityName = old('city_name', $listing->city_name);
    $displayZoneName = old('zone_name', $listing->zone_name ?: ($listing->serviceAreas->first()?->city_name ?? ''));
    $googlePayload = old('google_location_payload', $listing->google_location_payload ? json_encode($listing->google_location_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '');
    $decodedGooglePayload = is_string($googlePayload) && $googlePayload !== '' ? json_decode($googlePayload, true) : [];
    $decodedGooglePayload = is_array($decodedGooglePayload) ? $decodedGooglePayload : [];
    $extractPayloadComponent = function (array $payload, array $candidateTypes): string {
        $components = $payload['address_components'] ?? null;

        if (! is_array($components)) {
            return '';
        }

        foreach ($candidateTypes as $candidateType) {
            foreach ($components as $component) {
                if (! is_array($component)) {
                    continue;
                }

                $types = $component['types'] ?? [];
                if (is_array($types) && in_array($candidateType, $types, true)) {
                    return trim((string) ($component['long_name'] ?? ''));
                }
            }
        }

        return '';
    };
    $displayNeighborhood = $extractPayloadComponent($decodedGooglePayload, ['neighborhood'])
      ?: $extractPayloadComponent($decodedGooglePayload, ['sublocality_level_2'])
      ?: $extractPayloadComponent($decodedGooglePayload, ['administrative_area_level_5']);
    $pendingLocalityName = old('suggest_zone', ($selectedCityId > 0 && $displayZoneName !== '' && ! $primaryZoneId ? $displayZoneName : ''));

    $systemFaqRows = $listing->systemFaqRows();
    $faqRows = old('faq_question')
      ? collect(old('faq_question'))->map(function ($question, $index) {
          return ['question' => $question, 'answer' => old('faq_answer')[$index] ?? ''];
        })
      : $listing->faqs->map(fn ($faq) => ['question' => $faq->question, 'answer' => $faq->answer]);

    if ($faqRows->isEmpty()) {
      $faqRows = collect([['question' => '', 'answer' => '']]);
    }

    $basePriceValue = old('base_price', $listing->base_price);
    $basePriceValue = filled($basePriceValue) ? (int) round((float) $basePriceValue) : null;
    $editorDescription = app(\App\Support\ListingDescriptionSanitizer::class)->sanitize(
      old('description', $editorDescription ?? $listing->description)
    ) ?? '';
    $reviewMap = [
      \App\Models\MariachiListing::REVIEW_DRAFT => ['label' => 'Borrador de revision', 'class' => 'secondary'],
      \App\Models\MariachiListing::REVIEW_PENDING => ['label' => 'En revision', 'class' => 'warning'],
      \App\Models\MariachiListing::REVIEW_APPROVED => ['label' => 'Aprobado', 'class' => 'success'],
      \App\Models\MariachiListing::REVIEW_REJECTED => ['label' => 'Rechazado', 'class' => 'danger'],
    ];
    $paymentMap = [
      \App\Models\MariachiListing::PAYMENT_NONE => ['label' => 'Sin pago', 'class' => 'secondary'],
      \App\Models\MariachiListing::PAYMENT_PENDING => ['label' => 'Pago pendiente', 'class' => 'warning'],
      \App\Models\MariachiListing::PAYMENT_APPROVED => ['label' => 'Pago aprobado', 'class' => 'success'],
      \App\Models\MariachiListing::PAYMENT_REJECTED => ['label' => 'Pago rechazado', 'class' => 'danger'],
    ];
    $reviewMeta = $reviewMap[$listing->review_status] ?? ['label' => $listing->reviewStatusLabel(), 'class' => 'secondary'];
    $paymentMeta = $paymentMap[$listing->payment_status] ?? ['label' => $listing->paymentStatusLabel(), 'class' => 'secondary'];
    $selectedPlan = $listing->selected_plan_code ? ($plans[$listing->selected_plan_code] ?? null) : null;
    $defaultPlan = $selectedPlan ?: (count($plans) ? reset($plans) : null);
    $planDurationOptions = $defaultPlan ? array_values($defaultPlan['terms'] ?? []) : [];
    $selectedTermMonths = (int) old('term_months', $listing->plan_duration_months ?: ($defaultPlan['default_term_months'] ?? ($planDurationOptions[0]['months'] ?? 1)));
    $defaultPlanTerm = $defaultPlan && isset($defaultPlan['terms'][$selectedTermMonths])
      ? $defaultPlan['terms'][$selectedTermMonths]
      : ($defaultPlan ? reset($defaultPlan['terms']) : null);
    $latestPayment = $listing->latestPayment;
    $canSubmitForReview = $listing->canBeSubmittedForReview();
    $submitForReviewLabel = $listing->review_status === \App\Models\MariachiListing::REVIEW_REJECTED ? 'Reenviar a revisión' : 'Enviar a revisión';
    $maxPhotos = (int) ($capabilities['max_photos_per_listing'] ?? 0);
    $maxVideos = (int) ($capabilities['max_videos_per_listing'] ?? 0);
    $maxZones = (int) ($capabilities['max_zones_covered'] ?? 0);
    $maxEventTypes = (int) ($capabilities['max_event_types'] ?? 0);
    $maxServiceTypes = (int) ($capabilities['max_service_types'] ?? 0);
    $maxGroupSizes = (int) ($capabilities['max_group_sizes'] ?? 0);
    $maxBudgetRanges = (int) ($capabilities['max_budget_ranges'] ?? 0);
    $photoCount = $listing->photos->count();
    $videoCount = $listing->videos->count();
    $canAddMorePhotos = $photoCount < $maxPhotos;
    $canAddMoreVideos = $videoCount < $maxVideos;
    $canAddCoverageExtras = $maxZones > 1;
    $canPauseListing = $listing->canOwnerPause();
    $canResumeListing = $listing->canOwnerResume();
    $usesDraftPlaceholders = trim((string) $listing->title) === 'Nuevo anuncio'
      || trim((string) $listing->short_description) === 'Completa la informacion del anuncio'
      || $basePriceValue === null;
    $forcedInitialStep = session('force_listing_step');
    $initialWizardStep = is_string($forcedInitialStep) && $forcedInitialStep !== ''
      ? $forcedInitialStep
      : ($listing->isPaymentPending() ? 'final' : '');
  @endphp

  @if($editorMode)
    <div class="listing-editor-fullscreen" data-listing-editor-shell data-editor-index-url="{{ route('mariachi.listings.index') }}">
      <div class="listing-editor-fullscreen__toolbar">
        <div class="listing-editor-fullscreen__toolbar-group">
          <button type="button" class="btn btn-icon btn-text-secondary" data-editor-close aria-label="Cerrar editor">
            <i class="icon-base ti tabler-x"></i>
          </button>
          <button type="button" class="btn btn-icon btn-text-secondary" data-editor-nav-toggle aria-label="Mostrar u ocultar estructura del anuncio">
            <i class="icon-base ti tabler-layout-sidebar-left-collapse"></i>
          </button>
          <div class="listing-editor-fullscreen__title">
            <span class="small text-muted">Editor de anuncio</span>
            <strong>{{ $listing->title }}</strong>
          </div>
        </div>
        <div class="listing-editor-fullscreen__toolbar-group listing-editor-fullscreen__toolbar-group--actions">
          <span class="badge bg-label-secondary" data-autosave-status>Autoguardado listo</span>
          <small class="text-muted" data-autosave-time></small>
          @if($listing->isApprovedForMarketplace() && $listing->slug)
            <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
              <i class="icon-base ti tabler-external-link me-1"></i>Ver público
            </a>
          @endif
          <span class="badge bg-label-{{ $reviewMeta['class'] }}">{{ $reviewMeta['label'] }}</span>
          <span class="badge bg-label-{{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span>
        </div>
      </div>
      <div class="listing-editor-fullscreen__viewport">
  @endif

  @unless($editorMode)
    @if(session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <strong>Hay errores de validación.</strong>
        <ul class="mb-0 mt-2">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if($listing->review_status === \App\Models\MariachiListing::REVIEW_REJECTED && $listing->rejection_reason)
      <div class="alert alert-danger">
        <strong>El anuncio fue rechazado.</strong> Corrige lo siguiente y luego vuelve a enviarlo a revisión.
        <div class="mt-2">{{ $listing->rejection_reason }}</div>
      </div>
    @elseif($listing->isPaymentPending())
      <div class="alert alert-warning">
        <strong>Pago enviado.</strong> El anuncio está bloqueado mientras el equipo valida tu comprobante.
      </div>
    @elseif($listing->isPaymentRejected())
      <div class="alert alert-danger">
        <strong>Pago rechazado.</strong>
        {{ $listing->latestPayment?->rejection_reason ?: 'Revisa el comprobante y vuelve a intentar.' }}
      </div>
    @elseif($listing->review_status === \App\Models\MariachiListing::REVIEW_APPROVED)
      <div class="alert alert-info">
        <strong>Este anuncio ya fue aprobado.</strong> Si cambias contenido, fotos, videos o filtros, saldrá de publicación y volverá a borrador de revisión.
      </div>
    @endif

    @if($planIssues !== [])
      <div class="alert alert-warning">
        <strong>Tu plan actual requiere ajuste.</strong>
        <ul class="mb-0 mt-2">
          @foreach($planIssues as $issue)
            <li>{{ $issue }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if($listingIssues !== [])
      <div class="alert alert-warning">
        <strong>Este anuncio requiere ajuste para volver a publicarse.</strong>
        <ul class="mb-0 mt-2">
          @foreach($listingIssues as $issue)
            <li>{{ $issue }}</li>
          @endforeach
        </ul>
      </div>
    @endif
  @endunless

  @unless($editorMode)
    <div class="card mb-6">
      <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h5 class="mb-1">{{ $listing->title }}</h5>
          <p class="mb-1">
            Estado:
            <span class="badge bg-label-{{ $listing->is_active ? 'success' : 'warning' }}">{{ $listing->status }}</span>
            · Revisión:
            <span class="badge bg-label-{{ $reviewMeta['class'] }}">{{ $reviewMeta['label'] }}</span>
            · Pago:
            <span class="badge bg-label-{{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span>
            · Plan activo: <strong>{{ $planSummary['name'] ?? ($listing->effectivePlanCode() ?: 'sin plan') }}</strong>
            @if(! empty($planSummary['badge_text']))
              <span class="badge bg-label-primary ms-1">{{ $planSummary['badge_text'] }}</span>
            @endif
          </p>
          <small class="text-muted">Completitud: <strong data-completion-text>{{ $listing->listing_completion }}%</strong> · Fotos {{ $capabilities['max_photos_per_listing'] }} · Videos {{ $capabilities['max_videos_per_listing'] }} · Localidades {{ $capabilities['max_zones_covered'] ?? 0 }}.</small>
          @if($listing->submitted_for_review_at)
            <div class="small text-muted mt-1">Último envío a revisión: {{ $listing->submitted_for_review_at->format('Y-m-d H:i') }}</div>
          @endif
        </div>
        <div class="listing-owner-actions">
          <span class="badge bg-label-secondary" data-autosave-status>Autoguardado listo</span>
          <small class="text-muted" data-autosave-time></small>
          @if($canPauseListing)
            <form method="POST" action="{{ route('mariachi.listings.pause', ['listing' => $listing->id]) }}" class="m-0">
              @csrf
              <button type="submit" class="btn btn-outline-warning">Pausar anuncio</button>
            </form>
          @elseif($canResumeListing)
            <form method="POST" action="{{ route('mariachi.listings.resume', ['listing' => $listing->id]) }}" class="m-0">
              @csrf
              <button type="submit" class="btn btn-outline-success">Reanudar anuncio</button>
            </form>
          @endif
          <a href="{{ route('mariachi.listings.index') }}" class="btn btn-outline-secondary">Volver</a>
          @if($canPauseListing || $canResumeListing)
            <span class="listing-inline-note">Pausar solo oculta el anuncio; el tiempo del plan sigue corriendo.</span>
          @endif
        </div>
      </div>
    </div>
  @endunless

  <div id="listing-wizard" class="bs-stepper vertical mb-6" data-listing-wizard data-listing-id="{{ $listing->id }}" data-initial-step="{{ $initialWizardStep }}">
    <div class="bs-stepper-header border-end">
      <div class="step" data-target="#step-basic" data-step-key="basic">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-edit-circle icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Básicos</span>
            <span class="bs-stepper-subtitle">Título, resumen, precio</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-location" data-step-key="location">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-map-pin icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Ubicación</span>
            <span class="bs-stepper-subtitle">Ciudad y cobertura</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-filters" data-step-key="filters">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-adjustments-horizontal icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Filtros</span>
            <span class="bs-stepper-subtitle">Servicios y filtros</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-faqs" data-step-key="faqs">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-help-circle icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">FAQs</span>
            <span class="bs-stepper-subtitle">Sistema + personalizadas</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-photos" data-step-key="photos">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-photo icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Fotos</span>
            <span class="bs-stepper-subtitle">Portada y galeria</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-videos" data-step-key="videos">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-video icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Videos</span>
            <span class="bs-stepper-subtitle">URLs y soporte visual</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-review" data-step-key="final">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-credit-card icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Final</span>
            <span class="bs-stepper-subtitle">Planes y pago</span>
          </span>
        </button>
      </div>
    </div>

    <div class="bs-stepper-content">
      <form
        id="listing-main-form"
        class="{{ $editorMode ? 'listing-editor-form-shell' : '' }}"
        method="POST"
        action="{{ route('mariachi.listings.update', ['listing' => $listing->id]) }}"
        data-autosave="true"
        data-autosave-url="{{ route('mariachi.listings.autosave', ['listing' => $listing->id]) }}"
        data-autosave-sync="true"
        data-google-maps-enabled="{{ $googleMaps['enabled'] ? 'true' : 'false' }}"
        data-google-maps-key="{{ $googleMaps['browser_api_key'] }}"
        data-google-country="{{ $googleMaps['places_country_restriction'] }}"
        data-default-country="{{ $googleMaps['default_country_name'] }}"
        data-location-cities='@json($cities->map(fn ($city) => ['id' => (int) $city->id, 'name' => $city->name])->values())'
        data-location-zones='@json($zones->map(fn ($zone) => ['id' => (int) $zone->id, 'city_id' => (int) $zone->marketplace_city_id, 'name' => $zone->name])->values())'
      >
        @csrf
        @method('PATCH')
        <input type="hidden" name="country" id="listing-country-input" value="{{ $googleMaps['default_country_name'] }}" />
        <input type="hidden" name="marketplace_city_id" id="listing-city-id" value="{{ $selectedCityId ?: '' }}" />
        <input type="hidden" name="primary_marketplace_zone_id" id="listing-primary-zone-id" value="{{ $primaryZoneId ?: '' }}" />
        <input type="hidden" name="suggest_zone" id="listing-suggest-zone" value="{{ old('suggest_zone', '') }}" />
        <input type="hidden" name="latitude" id="listing-latitude-input" value="{{ old('latitude', $listing->latitude) }}" />
        <input type="hidden" name="longitude" id="listing-longitude-input" value="{{ old('longitude', $listing->longitude) }}" />
        <input type="hidden" name="postal_code" id="listing-postal-code-input" value="{{ old('postal_code', $listing->postal_code) }}" />
        <input type="hidden" name="google_place_id" id="listing-place-id-input" value="{{ old('google_place_id', $listing->google_place_id) }}" />
        <input type="hidden" name="google_location_payload" id="listing-google-payload-input" value="{{ $googlePayload }}" />

        @if($editorMode)
          <div class="listing-editor-form-shell__body">
        @endif

        <div id="step-basic" class="content" data-step-key="basic">
          <div class="row g-4">
            @if($usesDraftPlaceholders)
              <div class="col-12">
                <div class="alert alert-info mb-0">
                  <strong>Empieza por aquí.</strong> Cambia el título, la descripción corta y define un precio base para continuar con el anuncio.
                </div>
              </div>
            @endif
            <div class="col-md-8">
              <label class="form-label">Título del anuncio</label>
              <input class="form-control" name="title" value="{{ old('title', $listing->title) }}" required maxlength="180" placeholder="Ej: Mariachi para bodas y serenatas" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Precio base</label>
              <div class="listing-base-price-card">
                <div class="listing-base-price-card__value">
                  <div>
                    <div class="small text-muted">Desde</div>
                    <div class="listing-base-price-card__amount" data-base-price-display>{{ $basePriceValue !== null ? '$'.number_format($basePriceValue, 0, ',', '.') : '$—' }}</div>
                  </div>
                  <span class="badge bg-label-success">COP</span>
                </div>
                <input
                  type="range"
                  id="listing-base-price-range"
                  class="listing-base-price-card__range"
                  min="0"
                  max="4000000"
                  step="5000"
                  value="{{ $basePriceValue ?? 0 }}"
                  data-base-price-range
                />
                <input
                  type="hidden"
                  name="base_price"
                  id="listing-base-price-hidden"
                  value="{{ $basePriceValue !== null ? $basePriceValue : '' }}"
                />
                <div class="listing-base-price-card__scale">
                  <span>$0</span>
                  <span>$4.000.000</span>
                </div>
                <div class="listing-inline-note">Muévelo para definir un precio base sin decimales. Luego puedes cerrar por encima según fecha, ciudad y formato.</div>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción corta</label>
              <textarea class="form-control" name="short_description" rows="2" maxlength="280" required>{{ old('short_description', $listing->short_description) }}</textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción completa</label>
              <div class="listing-rich-editor" data-rich-editor>
                <div class="listing-rich-editor__toolbar">
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-rich-command="bold" title="Negrita"><i class="icon-base ti tabler-bold icon-sm"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-rich-command="italic" title="Cursiva"><i class="icon-base ti tabler-italic icon-sm"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-rich-command="formatBlock" data-rich-value="H2" title="Título H2">H2</button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-rich-command="formatBlock" data-rich-value="H3" title="Título H3">H3</button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-rich-command="insertUnorderedList" title="Lista"><i class="icon-base ti tabler-list icon-sm"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-rich-command="insertOrderedList" title="Lista numerada"><i class="icon-base ti tabler-list-numbers icon-sm"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-rich-command="createLink" title="Enlace"><i class="icon-base ti tabler-link icon-sm"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-rich-command="removeFormat" title="Limpiar formato"><i class="icon-base ti tabler-clear-formatting icon-sm"></i></button>
                </div>
                <div class="listing-rich-editor__link-panel" data-rich-link-panel hidden>
                  <div class="listing-rich-editor__link-head">
                    <div>
                      <div class="fw-semibold text-heading">Agregar enlace</div>
                      <div class="small text-muted">Pega una URL, un correo <code>mailto:</code> o un teléfono <code>tel:</code>.</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-text-secondary" data-rich-link-cancel aria-label="Cerrar">
                      <i class="icon-base ti tabler-x icon-sm"></i>
                    </button>
                  </div>
                  <div>
                    <input
                      type="text"
                      class="form-control"
                      placeholder="https://tu-enlace.com"
                      data-rich-link-input
                    />
                  </div>
                  <div class="small text-danger listing-rich-editor__link-error" data-rich-link-error></div>
                  <div class="listing-rich-editor__link-actions">
                    <button type="button" class="btn btn-sm btn-label-secondary" data-rich-link-cancel>Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" data-rich-link-apply>Aplicar enlace</button>
                  </div>
                </div>
                <div
                  class="listing-rich-editor__surface"
                  contenteditable="true"
                  role="textbox"
                  aria-multiline="true"
                  data-rich-surface
                  data-placeholder="Describe repertorio, tiempos, formato del show y lo que hace diferente a tu grupo."
                >{!! $editorDescription !!}</div>
                <textarea class="d-none" name="description" data-rich-input>{{ old('description', $listing->description) }}</textarea>
                <div class="listing-rich-editor__hint">
                  Usa negrita, cursiva, listas, enlaces y títulos. No se permiten imágenes ni embeds.
                </div>
              </div>
            </div>

            <div class="col-12 listing-step-actions">
              <button type="button" class="btn btn-label-secondary" disabled>
                <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
              </button>
              <button type="button" class="btn btn-primary" data-step-next>
                Siguiente <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
              </button>
            </div>
          </div>
        </div>

        <div id="step-location" class="content" data-step-key="location">
          <div class="row g-4">
            <div class="col-12">
              <div class="alert alert-info mb-0">
                Escribe la dirección real del anuncio o usa el pin del mapa. El sistema detectará ciudad, localidad, barrio informativo, coordenadas y dejará el país fijo en <strong>{{ $googleMaps['default_country_name'] }}</strong>.
              </div>
            </div>

            @if(! $googleMaps['enabled'])
              <div class="col-12">
                <div class="alert alert-warning mb-0">
                  Google Maps no está configurado. Desde admin debes registrar la API key para activar el autocompletado.
                </div>
              </div>
            @endif

            <div class="col-12">
              <label class="form-label">Dirección</label>
              <div class="input-group">
                <input
                  class="form-control"
                  id="listing-address-input"
                  name="address"
                  value="{{ old('address', $listing->address) }}"
                  maxlength="255"
                  autocomplete="off"
                  placeholder="Escribe la calle o usa el mapa para fijar el pin"
                />
                <button
                  type="button"
                  class="btn btn-outline-primary listing-location-trigger"
                  id="listing-map-picker-open"
                  @disabled(! $googleMaps['enabled'])
                >
                  <i class="icon-base ti tabler-map-pin icon-sm"></i>
                  <span class="ms-1">Mapa</span>
                </button>
              </div>
              <small class="text-muted d-block mt-2">Busca la dirección o usa el mapa. Aquí dejamos calle y número para una lectura más limpia; el detalle completo queda en Google Maps.</small>
            </div>

            <div class="col-md-4">
              <label class="form-label">Ciudad detectada</label>
              <input class="form-control" id="listing-city-name-input" name="city_name" value="{{ $displayCityName }}" maxlength="120" placeholder="Se completa con Google Maps" readonly />
            </div>
            <div class="col-md-4">
              <label class="form-label">Localidad detectada</label>
              <input class="form-control" id="listing-zone-name-input" name="zone_name" value="{{ $displayZoneName }}" maxlength="120" placeholder="Se completa con Google Maps" readonly />
            </div>
            <div class="col-md-4">
              <label class="form-label">Barrio detectado (informativo)</label>
              <input class="form-control" id="listing-neighborhood-input" value="{{ $displayNeighborhood }}" maxlength="120" placeholder="Se mostrará si Google lo devuelve" readonly />
            </div>

            <div class="col-12">
              <div
                class="small mt-1 listing-location-status {{ $pendingLocalityName ? 'text-warning' : 'text-muted' }}"
                data-locality-status
                @if(! $pendingLocalityName) hidden @endif
              >
                @if($pendingLocalityName)
                  Localidad detectada pendiente de catálogo: {{ $pendingLocalityName }}. La enviaremos como sugerencia para aprobación admin.
                @endif
              </div>
              <small class="text-muted d-block mt-2">Ciudad, localidad y departamento se detectan desde Google Maps y no se editan manualmente. Las coordenadas siguen siendo internas y el país permanece fijo en {{ $googleMaps['default_country_name'] }}.</small>
            </div>

            <div class="col-12">
              <label class="form-label">Departamento / región</label>
              <input class="form-control" id="listing-state-input" name="state" value="{{ old('state', $listing->state) }}" maxlength="120" readonly />
            </div>

            <div class="col-12 pt-2">
              <h6 class="mb-2">Cobertura adicional</h6>
              <small class="text-muted d-block mb-3">La localidad principal se detecta automáticamente. Activa esta opción solo si también cubres otras localidades dentro de la misma ciudad.</small>
              <div class="listing-coverage-toggle {{ $canAddCoverageExtras ? '' : 'is-disabled' }}">
                <div class="form-check form-switch mb-2">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    value="1"
                    id="travels"
                    name="travels_to_other_cities"
                    {{ $canAddCoverageExtras && old('travels_to_other_cities', $listing->travels_to_other_cities) ? 'checked' : '' }}
                    @disabled(! $canAddCoverageExtras)
                  >
                  <label class="form-check-label" for="travels">Cubro más localidades dentro de esta ciudad</label>
                </div>
                <small class="text-muted d-block">No agrega otras ciudades ni cambia tu localidad principal. Solo amplía la cobertura dentro del mismo catálogo de ciudad.</small>
                @unless($canAddCoverageExtras)
                  <div class="alert alert-warning mt-3 mb-0">
                    Tu plan no permite cobertura adicional. Si quieres sumar más localidades dentro de esta ciudad,
                    <button type="button" class="btn btn-sm btn-warning ms-1" data-upgrade-to-final="true">mejora tu plan</button>.
                  </div>
                @endunless
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">Localidades adicionales de cobertura</label>
              <small class="d-block text-muted mb-3">Tu plan permite hasta {{ $maxZones }} localidad(es) por anuncio, contando la localidad principal detectada. Aquí solo puedes sumar localidades adicionales de la misma ciudad.</small>
              <div
                class="listing-zone-shell p-3"
                data-zone-picker
                data-max-zones="{{ $maxZones }}"
                data-has-extra-coverage="{{ $canAddCoverageExtras ? 'true' : 'false' }}"
              >
                <div class="row g-3">
                  <div class="col-lg-6">
                    <div class="listing-zone-panel listing-zone-panel--localities h-100">
                      <div class="listing-zone-panel__header">
                        <div class="listing-zone-panel__header-main">
                          <div>
                            <h6 class="mb-1">Localidades disponibles</h6>
                            <small class="text-muted">Solo del catálogo oficial de la ciudad principal.</small>
                          </div>
                        </div>
                        <div class="listing-zone-panel__search">
                          <input type="search" class="form-control form-control-sm" placeholder="Buscar localidad" data-zone-search>
                        </div>
                      </div>
                      <div class="listing-zone-list listing-zone-list--compact" data-zone-available></div>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="listing-zone-panel listing-zone-panel--localities h-100">
                      <div class="listing-zone-panel__header">
                        <div class="listing-zone-panel__header-main">
                          <div>
                            <h6 class="mb-1" data-zone-selected-title>Localidades seleccionadas (<span data-zone-count>0</span> / {{ $maxZones }})</h6>
                            <small class="text-muted listing-zone-panel__hint" data-zone-selected-copy>La localidad principal se detecta automáticamente y cuenta dentro del límite.</small>
                          </div>
                          <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                            <span class="badge bg-label-primary" data-zone-limit-badge>Máx {{ $maxZones }}</span>
                            <button
                              type="button"
                              class="btn btn-sm btn-outline-warning listing-zone-panel__upgrade-link"
                              data-zone-upgrade
                              data-upgrade-to-final="true"
                              hidden
                            >
                              Ver Plan Pro
                            </button>
                          </div>
                        </div>
                      </div>
                      <div class="listing-zone-list listing-zone-list--compact" data-zone-selected></div>
                      <div data-zone-hidden-inputs>
                        @foreach($formSelectedZoneIds as $zoneId)
                          <input type="hidden" name="zone_ids[]" value="{{ $zoneId }}">
                        @endforeach
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              @error('zone_ids')
                <div class="text-danger small mt-2">{{ $message }}</div>
              @enderror
              <div class="text-danger small mt-2" data-zone-feedback></div>
            </div>

            <div class="col-12 listing-step-actions">
              <button type="button" class="btn btn-label-secondary" data-step-prev>
                <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
              </button>
              <button type="button" class="btn btn-primary" data-step-next>
                Siguiente <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
              </button>
            </div>
          </div>
        </div>

        <div id="step-filters" class="content" data-step-key="filters">
          <div class="row g-4">
            <div class="col-md-6">
              <div class="listing-filter-card" data-filter-group data-filter-name="tipos de evento" data-limit="{{ $maxEventTypes }}">
                <div class="listing-filter-card__head">
                  <div>
                    <h6 class="mb-1">Tipos de evento</h6>
                    <p class="listing-step-note mb-0">Selecciona hasta {{ $maxEventTypes }} opciones para este anuncio.</p>
                  </div>
                  <span class="badge bg-label-primary"><span data-filter-count>0</span> / {{ $maxEventTypes }}</span>
                </div>
                <div class="listing-filter-card__body">
                  @foreach($eventTypes as $eventType)
                    <div class="form-check mb-0">
                      <input class="form-check-input" type="checkbox" name="event_type_ids[]" value="{{ $eventType->id }}" id="event-{{ $eventType->id }}" {{ in_array($eventType->id, old('event_type_ids', $selectedEventTypeIds)) ? 'checked' : '' }}>
                      <label class="form-check-label d-inline-flex align-items-center gap-1" for="event-{{ $eventType->id }}"><x-catalog-icon :name="$eventType->icon" class="h-4 w-4" />{{ $eventType->name }}</label>
                    </div>
                  @endforeach
                </div>
                <div class="alert alert-warning mt-3 mb-0 listing-filter-upgrade" data-filter-upgrade hidden>
                  Llegaste al límite de tu plan. <button type="button" class="btn btn-sm btn-warning ms-1" data-upgrade-to-final="true">Mejorar plan</button>
                </div>
                <label class="form-label mt-3">Sugerir tipo de evento (opcional)</label>
                <input class="form-control" name="suggest_event_type" value="{{ old('suggest_event_type') }}" placeholder="Ej: Pedida de mano" maxlength="120">
                @error('event_type_ids')
                  <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-md-6">
              <div class="listing-filter-card" data-filter-group data-filter-name="tipos de servicio" data-limit="{{ $maxServiceTypes }}">
                <div class="listing-filter-card__head">
                  <div>
                    <h6 class="mb-1">Tipos de servicio</h6>
                    <p class="listing-step-note mb-0">Selecciona hasta {{ $maxServiceTypes }} opciones para definir mejor el formato.</p>
                  </div>
                  <span class="badge bg-label-primary"><span data-filter-count>0</span> / {{ $maxServiceTypes }}</span>
                </div>
                <div class="listing-filter-card__body">
                  @foreach($serviceTypes as $serviceType)
                    <div class="form-check mb-0">
                      <input class="form-check-input" type="checkbox" name="service_type_ids[]" value="{{ $serviceType->id }}" id="service-{{ $serviceType->id }}" {{ in_array($serviceType->id, old('service_type_ids', $selectedServiceTypeIds)) ? 'checked' : '' }}>
                      <label class="form-check-label d-inline-flex align-items-center gap-1" for="service-{{ $serviceType->id }}"><x-catalog-icon :name="$serviceType->icon" class="h-4 w-4" />{{ $serviceType->name }}</label>
                    </div>
                  @endforeach
                </div>
                <div class="alert alert-warning mt-3 mb-0 listing-filter-upgrade" data-filter-upgrade hidden>
                  Llegaste al límite de tu plan. <button type="button" class="btn btn-sm btn-warning ms-1" data-upgrade-to-final="true">Mejorar plan</button>
                </div>
                <label class="form-label mt-3">Sugerir tipo de servicio (opcional)</label>
                <input class="form-control" name="suggest_service_type" value="{{ old('suggest_service_type') }}" placeholder="Ej: Show con trompeta solista" maxlength="120">
                @error('service_type_ids')
                  <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-md-6">
              <div class="listing-filter-card" data-filter-group data-filter-name="tamanos de grupo" data-limit="{{ $maxGroupSizes }}">
                <div class="listing-filter-card__head">
                  <div>
                    <h6 class="mb-1">Tamaño del grupo</h6>
                    <p class="listing-step-note mb-0">Selecciona hasta {{ $maxGroupSizes }} opciones para indicar el formato disponible.</p>
                  </div>
                  <span class="badge bg-label-primary"><span data-filter-count>0</span> / {{ $maxGroupSizes }}</span>
                </div>
                <div class="listing-filter-card__body">
                  @foreach($groupSizeOptions as $option)
                    <div class="form-check mb-0">
                      <input class="form-check-input" type="checkbox" name="group_size_option_ids[]" value="{{ $option->id }}" id="group-{{ $option->id }}" {{ in_array($option->id, old('group_size_option_ids', $selectedGroupSizeIds)) ? 'checked' : '' }}>
                      <label class="form-check-label d-inline-flex align-items-center gap-1" for="group-{{ $option->id }}"><x-catalog-icon :name="$option->icon" class="h-4 w-4" />{{ $option->name }}</label>
                    </div>
                  @endforeach
                </div>
                <div class="alert alert-warning mt-3 mb-0 listing-filter-upgrade" data-filter-upgrade hidden>
                  Llegaste al límite de tu plan. <button type="button" class="btn btn-sm btn-warning ms-1" data-upgrade-to-final="true">Mejorar plan</button>
                </div>
                @error('group_size_option_ids')
                  <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-md-6">
              <div class="listing-filter-card" data-filter-group data-filter-name="rangos de presupuesto" data-limit="{{ $maxBudgetRanges }}">
                <div class="listing-filter-card__head">
                  <div>
                    <h6 class="mb-1">Presupuesto</h6>
                    <p class="listing-step-note mb-0">Selecciona hasta {{ $maxBudgetRanges }} rangos para cubrir varias búsquedas del marketplace.</p>
                  </div>
                  <span class="badge bg-label-primary"><span data-filter-count>0</span> / {{ $maxBudgetRanges }}</span>
                </div>
                <div class="listing-filter-card__body">
                  @foreach($budgetRanges as $range)
                    <div class="form-check mb-0">
                      <input class="form-check-input" type="checkbox" name="budget_range_ids[]" value="{{ $range->id }}" id="budget-{{ $range->id }}" {{ in_array($range->id, old('budget_range_ids', $selectedBudgetIds)) ? 'checked' : '' }}>
                      <label class="form-check-label d-inline-flex align-items-center gap-1" for="budget-{{ $range->id }}"><x-catalog-icon :name="$range->icon" class="h-4 w-4" />{{ $range->name }}</label>
                    </div>
                  @endforeach
                </div>
                <div class="alert alert-warning mt-3 mb-0 listing-filter-upgrade" data-filter-upgrade hidden>
                  Llegaste al límite de tu plan. <button type="button" class="btn btn-sm btn-warning ms-1" data-upgrade-to-final="true">Mejorar plan</button>
                </div>
                @error('budget_range_ids')
                  <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-12 listing-step-actions">
              <button type="button" class="btn btn-label-secondary" data-step-prev>
                <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
              </button>
              <button type="button" class="btn btn-primary" data-step-next>
                Ir a FAQs <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
              </button>
            </div>
          </div>
        </div>

        <div id="step-faqs" class="content" data-step-key="faqs">
          <div class="row g-4">
            <div class="col-12">
              <div class="listing-faq-shell">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                  <div>
                    <h5 class="mb-1">FAQs del sistema</h5>
                    <p class="listing-step-note mb-0">Estas 3 preguntas se generan automáticamente con la información del anuncio y siempre se muestran primero.</p>
                  </div>
                  <span class="badge bg-label-success">Fijas</span>
                </div>
                <div class="listing-faq-system-list">
                  @foreach($systemFaqRows as $faq)
                    <article class="listing-faq-card listing-faq-card--system">
                      <div class="listing-faq-card__head">
                        <p class="listing-faq-card__question">{{ $faq['question'] }}</p>
                        <span class="badge bg-label-success">Sistema</span>
                      </div>
                      <p class="listing-faq-card__answer">{{ $faq['answer'] }}</p>
                    </article>
                  @endforeach
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="listing-faq-shell">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                  <div>
                    <h5 class="mb-1">FAQs personalizadas</h5>
                    <p class="listing-step-note mb-0">Agrega respuestas propias para tu anuncio. Puedes reordenarlas entre sí sin mover las 3 del sistema.</p>
                  </div>
                  <span class="badge bg-label-primary"><span data-faq-count>{{ $faqRows->count() }}</span> / 10</span>
                </div>

                <div class="listing-faq-user-list" data-faq-list data-faq-max="10">
                  @foreach($faqRows as $faq)
                    <article class="listing-faq-user-card" data-faq-item>
                      <div class="listing-faq-user-card__head">
                        <div>
                          <h6 class="mb-1">FAQ adicional</h6>
                          <small class="text-muted">Visible después de las 3 automáticas.</small>
                        </div>
                        <div class="listing-faq-user-card__tools">
                          <button type="button" class="listing-faq-handle" data-faq-handle aria-label="Reordenar pregunta">
                            <i class="icon-base ti tabler-grip-vertical icon-sm"></i>
                          </button>
                          <button type="button" class="btn btn-sm btn-outline-danger" data-faq-remove>Quitar</button>
                        </div>
                      </div>
                      <div class="row g-3">
                        <div class="col-md-5">
                          <label class="form-label">Pregunta</label>
                          <input class="form-control" name="faq_question[]" value="{{ $faq['question'] }}" placeholder="Ej: ¿Cuánto dura la serenata?" maxlength="240">
                        </div>
                        <div class="col-md-7">
                          <label class="form-label">Respuesta</label>
                          <textarea class="form-control" name="faq_answer[]" rows="2" placeholder="Responde con información clara para el cliente." maxlength="2000">{{ $faq['answer'] }}</textarea>
                        </div>
                      </div>
                    </article>
                  @endforeach
                </div>

                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mt-3">
                  <small class="text-muted">Máximo 10 FAQs personalizadas. Si dejas una vacía, no se guardará.</small>
                  <button type="button" class="btn btn-outline-primary" data-faq-add>
                    <i class="icon-base ti tabler-plus icon-xs me-1"></i>Agregar otra pregunta
                  </button>
                </div>

                @error('faq_question')
                  <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
                @error('faq_answer')
                  <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-12 listing-step-actions">
              <button type="button" class="btn btn-label-secondary" data-step-prev>
                <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
              </button>
              <button type="button" class="btn btn-primary" data-step-next>
                Ir a fotos <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
              </button>
            </div>
          </div>
        </div>
      </form>

      <template id="listing-faq-item-template">
        <article class="listing-faq-user-card" data-faq-item>
          <div class="listing-faq-user-card__head">
            <div>
              <h6 class="mb-1">FAQ adicional</h6>
              <small class="text-muted">Visible después de las 3 automáticas.</small>
            </div>
            <div class="listing-faq-user-card__tools">
              <button type="button" class="listing-faq-handle" data-faq-handle aria-label="Reordenar pregunta">
                <i class="icon-base ti tabler-grip-vertical icon-sm"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger" data-faq-remove>Quitar</button>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label">Pregunta</label>
              <input class="form-control" name="faq_question[]" value="" placeholder="Ej: ¿Cuánto dura la serenata?" maxlength="240">
            </div>
            <div class="col-md-7">
              <label class="form-label">Respuesta</label>
              <textarea class="form-control" name="faq_answer[]" rows="2" placeholder="Responde con información clara para el cliente." maxlength="2000"></textarea>
            </div>
          </div>
        </article>
      </template>

      <div id="step-photos" class="content" data-step-key="photos">
        <div class="row g-6">
          <div class="col-12">
            <div class="listing-media-shell p-4">
              <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                <div>
                  <h5 class="mb-1">Fotos del anuncio</h5>
                  <p class="listing-step-note mb-0">Tu plan permite hasta {{ $maxPhotos }} fotos. La primera destacada se usa como portada principal del anuncio.</p>
                </div>
                <span class="badge bg-label-primary">{{ $photoCount }} / {{ $maxPhotos }}</span>
              </div>

              @error('photo')
                <div class="alert alert-danger">{{ $message }}</div>
              @enderror

              <form method="POST" action="{{ route('mariachi.listings.photos.store', ['listing' => $listing->id]) }}" enctype="multipart/form-data" data-preserve-step="photos" class="d-none">
                @csrf
                <input type="hidden" name="return_step" value="photos">
                <input type="file" name="photo" accept="image/*" data-photo-input>
              </form>

              <div class="listing-photo-grid">
                @if($maxPhotos > 0 && $canAddMorePhotos)
                  <button type="button" class="listing-photo-add-tile" data-photo-trigger>
                    <span class="avatar avatar-xl bg-label-primary mb-3">
                      <span class="avatar-initial rounded"><i class="icon-base ti tabler-plus icon-lg"></i></span>
                    </span>
                    <strong class="text-heading">Agregar foto</strong>
                    <span class="text-muted small mt-1">Sube JPG, PNG o WebP de hasta 5 MB.</span>
                  </button>
                @endif

                @foreach($listing->photos as $photo)
                  <div class="listing-photo-card">
                    <div class="listing-photo-card__media">
                      <img src="{{ asset('storage/'.$photo->path) }}" alt="foto del anuncio">
                      <div class="listing-photo-card__badges">
                        @if($photo->is_featured)
                          <span class="badge bg-success">Destacada</span>
                        @else
                          <span class="badge bg-label-secondary">Foto {{ $loop->iteration }}</span>
                        @endif
                        <span class="badge bg-dark">{{ $loop->iteration }}</span>
                      </div>
                    </div>
                    <div class="listing-photo-card__body">
                      <div class="listing-photo-card__actions">
                        @unless($photo->is_featured)
                          <form method="POST" action="{{ route('mariachi.listings.photos.featured', ['listing' => $listing->id, 'photo' => $photo->id]) }}" data-preserve-step="photos">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="return_step" value="photos">
                            <button class="btn btn-sm btn-outline-primary" type="submit">Destacar</button>
                          </form>
                        @endunless
                        @if($listing->photos->count() > 1)
                          <form method="POST" action="{{ route('mariachi.listings.photos.move', ['listing' => $listing->id, 'photo' => $photo->id, 'direction' => 'up']) }}" data-preserve-step="photos">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="return_step" value="photos">
                            <button class="btn btn-sm btn-outline-secondary" type="submit" aria-label="Mover arriba">↑</button>
                          </form>
                          <form method="POST" action="{{ route('mariachi.listings.photos.move', ['listing' => $listing->id, 'photo' => $photo->id, 'direction' => 'down']) }}" data-preserve-step="photos">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="return_step" value="photos">
                            <button class="btn btn-sm btn-outline-secondary" type="submit" aria-label="Mover abajo">↓</button>
                          </form>
                        @endif
                        <form method="POST" action="{{ route('mariachi.listings.photos.delete', ['listing' => $listing->id, 'photo' => $photo->id]) }}" data-preserve-step="photos">
                          @csrf
                          @method('DELETE')
                          <input type="hidden" name="return_step" value="photos">
                          <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                        </form>
                      </div>
                    </div>
                  </div>
                @endforeach

                @if($maxPhotos <= 0 || ! $canAddMorePhotos)
                  <button type="button" class="listing-upgrade-tile text-body border-0 w-100" data-upgrade-to-final="true">
                    <span class="avatar avatar-xl bg-label-warning mb-3">
                      <span class="avatar-initial rounded"><i class="icon-base ti tabler-crown icon-lg"></i></span>
                    </span>
                    <strong>Agrega más fotos con Plan Pro</strong>
                    <span class="text-muted small mt-1">Tu plan actual permite hasta {{ $maxPhotos }} foto(s) por anuncio.</span>
                  </button>
                @endif
              </div>
            </div>
          </div>

          <div class="col-12 listing-step-actions">
            <button type="button" class="btn btn-label-secondary" data-step-prev>
              <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
            </button>
            <button type="button" class="btn btn-primary" data-step-next>
              Ir a videos <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
            </button>
          </div>
        </div>
      </div>

      <div id="step-videos" class="content" data-step-key="videos">
        <div class="row g-6">
          <div class="col-12">
            <div class="listing-media-shell p-4">
              <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                <div>
                  <h5 class="mb-1">Videos del anuncio</h5>
                  <p class="listing-step-note mb-0">Agrega enlaces de YouTube o videos externos para reforzar confianza y mostrar el repertorio.</p>
                </div>
                <span class="badge bg-label-primary">{{ $videoCount }} / {{ $maxVideos }}</span>
              </div>

              @error('url')
                <div class="alert alert-danger">{{ $message }}</div>
              @enderror

              @if($maxVideos <= 0)
                <div class="listing-video-upgrade mb-4">
                  <span class="avatar avatar-xl bg-label-warning mb-3">
                    <span class="avatar-initial rounded"><i class="icon-base ti tabler-crown icon-lg"></i></span>
                  </span>
                  <h6 class="mb-2">Tu plan actual no incluye videos</h6>
                  <p class="text-muted mb-3">Mejora tu plan para añadir videos al anuncio y aumentar la conversión.</p>
                  <button type="button" class="btn btn-warning" data-upgrade-to-final="true">Mejorar plan</button>
                </div>
              @else
                <form method="POST" action="{{ route('mariachi.listings.videos.store', ['listing' => $listing->id]) }}" class="mb-4" data-preserve-step="videos">
                  @csrf
                  <input type="hidden" name="return_step" value="videos">
                  <div class="row g-2">
                    <div class="col-lg-9">
                      <input type="url" class="form-control" name="url" placeholder="https://youtube.com/watch?v=..." required @disabled(! $canAddMoreVideos)>
                    </div>
                    <div class="col-lg-3">
                      <button class="btn btn-primary w-100" type="submit" @disabled(! $canAddMoreVideos)>Agregar video</button>
                    </div>
                  </div>
                </form>
              @endif

              <div class="listing-video-list">
                @forelse($listing->videos as $video)
                  <div class="listing-video-card">
                    <div class="listing-video-card__meta">
                      <div class="listing-video-card__url">{{ $video->platform === 'youtube' ? 'Video de YouTube' : 'Video externo' }}</div>
                      <a href="{{ $video->url }}" target="_blank" rel="noopener" class="small text-muted">{{ $video->url }}</a>
                    </div>
                    <div class="listing-video-card__actions">
                      <form method="POST" action="{{ route('mariachi.listings.videos.delete', ['listing' => $listing->id, 'video' => $video->id]) }}" data-preserve-step="videos">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="return_step" value="videos">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                      </form>
                    </div>
                  </div>
                @empty
                  <div class="listing-zone-empty">
                    <span class="avatar avatar-xl bg-label-secondary mb-3">
                      <span class="avatar-initial rounded"><i class="icon-base ti tabler-video-off icon-lg"></i></span>
                    </span>
                    <strong class="text-heading">Todavía no has agregado videos</strong>
                    <span class="text-muted small mt-1">Cuando añadas uno, aparecerá aquí con acceso rápido para eliminarlo.</span>
                  </div>
                @endforelse
              </div>

              @if($maxVideos > 0 && ! $canAddMoreVideos)
                <button type="button" class="listing-upgrade-tile text-body border-0 w-100 mt-4" data-upgrade-to-final="true">
                  <span class="avatar avatar-xl bg-label-warning mb-3">
                    <span class="avatar-initial rounded"><i class="icon-base ti tabler-crown icon-lg"></i></span>
                  </span>
                  <strong>Agrega más videos con Plan Pro</strong>
                  <span class="text-muted small mt-1">Ya alcanzaste el tope de {{ $maxVideos }} video(s) para tu plan actual.</span>
                </button>
              @endif
            </div>
          </div>

          <div class="col-12 listing-step-actions">
            <button type="button" class="btn btn-label-secondary" data-step-prev>
              <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
            </button>
            <button type="button" class="btn btn-primary" data-step-next>
              Ir al final <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
            </button>
          </div>
        </div>
      </div>

      <div id="step-review" class="content" data-step-key="final">
        <div class="row g-6">
          <div class="col-12">
            <div class="listing-final-card p-4">
              <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
                <div>
                  <h5 class="mb-1">Planes disponibles</h5>
                  <p class="listing-step-note mb-0">El anuncio se autoguarda. Aquí eliges plan y continúas a Wompi para completar el cobro.</p>
                </div>
                <span class="badge bg-label-secondary">Completitud <span data-completion-text>{{ $listing->listing_completion }}%</span></span>
              </div>

              <div class="alert alert-danger d-none mb-4" data-plan-selection-error></div>

              @if(! $listing->listing_completed)
                <div class="alert alert-warning mb-4">
                  Aún faltan bloques para completar el anuncio. Termina datos, ubicación, filtros y fotos antes de pagar.
                </div>
              @endif

              @if(! $wompi['is_configured'])
                <div class="alert alert-danger mb-4">
                  Wompi no está configurado en este momento. No podrás continuar al checkout hasta completar las llaves en el entorno.
                </div>
              @endif

              @if($listing->isPaymentPending())
                <div class="alert alert-warning mb-4">
                  Ya existe un checkout Wompi pendiente para este anuncio. Puedes retomarlo con el plan seleccionado.
                </div>
              @elseif($listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED)
                <div class="alert alert-success mb-4">
                  Este anuncio ya tiene un pago aprobado. Puedes dejarlo como está, renovarlo o subirlo de plan sin bajarlo del aire.
                </div>
              @elseif($listing->isPaymentRejected())
                <div class="alert alert-danger mb-4">
                  Pago rechazado. {{ $latestPayment?->rejection_reason ?: 'Wompi no aprobó la transacción. Revisa el cobro y vuelve a intentar.' }}
                </div>
              @endif

              @if($plans)
                @if($planDurationOptions)
                  <div class="payment-billing-switch" data-billing-term-picker data-active-term-months="{{ $selectedTermMonths }}">
                    <div class="payment-billing-switch__copy">
                      <span class="payment-billing-switch__eyebrow">Duración del anuncio</span>
                      <div class="payment-billing-switch__title">Elige cuánto tiempo quieres publicar este anuncio</div>
                      <div class="payment-billing-switch__hint">El plazo cambia el total a pagar y aplica descuento automático en 3 y 12 meses.</div>
                    </div>

                    <div class="payment-billing-switch__group" role="tablist" aria-label="Duracion del anuncio">
                      @foreach($planDurationOptions as $termOption)
                        <button
                          type="button"
                          class="payment-billing-switch__button {{ (int) $termOption['months'] === $selectedTermMonths ? 'is-active' : '' }}"
                          data-billing-term-button
                          data-term-months="{{ $termOption['months'] }}">
                          <strong>{{ $termOption['label'] }}</strong>
                          <span>{{ $termOption['highlight'] }}</span>
                        </button>
                      @endforeach
                    </div>
                  </div>
                @endif

                <div class="payment-plan-grid">
                  @foreach($plans as $code => $plan)
                    @php
                      $activeTerm = $plan['terms'][$selectedTermMonths] ?? reset($plan['terms']);
                      $isCurrentSelection = $listing->selected_plan_code === $code
                        && (int) ($listing->plan_duration_months ?: 1) === (int) ($activeTerm['months'] ?? 1);
                      $canRenewCurrentPlan = $listing->status === \App\Models\MariachiListing::STATUS_ACTIVE
                        && $listing->review_status === \App\Models\MariachiListing::REVIEW_APPROVED
                        && $listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED
                        && $isCurrentSelection;
                      $buttonLabel = 'Pagar con Wompi';
                      if ($canRenewCurrentPlan) {
                        $buttonLabel = 'Renovar con Wompi';
                      } elseif ($listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED && $isCurrentSelection) {
                        $buttonLabel = 'Plan aprobado';
                      } elseif ($listing->isPaymentPending()) {
                        $buttonLabel = $isCurrentSelection ? 'Continuar pago en Wompi' : 'Pago pendiente en otro plan';
                      } elseif ($listing->isPaymentRejected() && $isCurrentSelection) {
                        $buttonLabel = 'Reintentar con Wompi';
                      } elseif ($isCurrentSelection) {
                        $buttonLabel = 'Continuar con este plan';
                      }

                      $isDisabled = ! $listing->listing_completed
                        || (! $isCurrentSelection && $listing->isPaymentPending())
                        || ! $wompi['is_configured']
                        || ($listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED && $isCurrentSelection && ! $canRenewCurrentPlan);
                    @endphp

                    <div
                      class="payment-plan-card {{ $isCurrentSelection ? 'is-current' : '' }}"
                      data-plan-card
                      data-plan-terms='@json(array_values($plan["terms"]))'>
                      <div class="payment-plan-card__body">
                        <div class="payment-plan-card__head">
                          <h5 class="payment-plan-card__title">
                            <span>{{ $plan['name'] }}</span>
                            @if($plan['badge_text'])
                              <span class="badge bg-label-primary">{{ $plan['badge_text'] }}</span>
                            @endif
                          </h5>
                          @if($isCurrentSelection)
                            <span class="badge bg-label-info">Seleccionado</span>
                          @endif
                        </div>

                        <p class="payment-plan-card__description">{{ $plan['description'] }}</p>

                        <div class="payment-plan-card__pricing">
                          <p class="payment-plan-card__price">
                            <span data-plan-total>${{ number_format((int) ($activeTerm['total_price_cop'] ?? 0), 0, ',', '.') }}</span>
                            <small>COP</small>
                          </p>

                          <div class="payment-plan-card__billing">
                            <div class="payment-plan-card__billing-copy">
                              <span data-plan-period-label>Total {{ $activeTerm['label'] ?? '1 mes' }}</span>
                              <strong data-plan-monthly-equivalent>${{ number_format((int) ($activeTerm['monthly_equivalent_cop'] ?? 0), 0, ',', '.') }} / mes equivalente</strong>
                            </div>
                            <span class="payment-plan-card__savings {{ (int) ($activeTerm['discount_percent'] ?? 0) === 0 ? 'is-muted' : '' }}" data-plan-savings>
                              {{ $activeTerm['savings_copy'] ?? 'Precio regular' }}
                            </span>
                          </div>
                        </div>

                        <ul class="payment-plan-card__features">
                          <li>{{ $plan['max_zones_covered'] }} localidad(es) por anuncio</li>
                          <li>{{ $plan['max_photos_per_listing'] }} foto(s) por anuncio</li>
                          <li>{{ $plan['can_add_video'] ? $plan['max_videos_per_listing'].' video(s) por anuncio' : 'Sin videos incluidos' }}</li>
                          <li>{{ $plan['max_event_types'] }} tipo(s) de evento</li>
                          <li>{{ $plan['max_service_types'] }} tipo(s) de servicio</li>
                          <li>{{ $plan['max_group_sizes'] }} tamaño(s) de grupo</li>
                          <li>{{ $plan['max_budget_ranges'] }} rango(s) de presupuesto</li>
                        </ul>

                        <div class="payment-plan-card__actions">
                          <button
                            type="button"
                            class="btn btn-primary w-100"
                            data-open-payment-sheet
                            data-select-url="{{ route('mariachi.listings.plans.select', ['listing' => $listing->id]) }}"
                            data-plan-code="{{ $code }}"
                            data-plan-name="{{ $plan['name'] }}"
                            data-plan-terms='@json(array_values($plan["terms"]))'
                            data-plan-price="{{ (int) ($activeTerm['total_price_cop'] ?? 0) }}"
                            data-plan-term-months="{{ (int) ($activeTerm['months'] ?? 1) }}"
                            data-plan-term-label="{{ $activeTerm['label'] ?? '1 mes' }}"
                            @disabled($isDisabled)>
                            {{ $buttonLabel }}
                          </button>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              @else
                <div class="alert alert-secondary mb-0">
                  No hay planes configurados todavía para este anuncio.
                </div>
              @endif

              @if($canSubmitForReview && $listingIssues === [] && $planIssues === [])
                <div class="alert alert-success mt-4 mb-0 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                  <div>Pago aprobado. Último paso: enviar el anuncio a revisión del equipo admin.</div>
                  <form method="POST" action="{{ route('mariachi.listings.submit-review', ['listing' => $listing->id]) }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-success">{{ $submitForReviewLabel }}</button>
                  </form>
                </div>
              @endif
            </div>
          </div>

          <div class="col-12 listing-step-actions listing-step-actions--single">
            <button type="button" class="btn btn-label-secondary" data-step-prev>
              <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
            </button>
          </div>
        </div>
      </div>

      @if($editorMode)
          </div>
      @endif

      @if($editorMode)
        <div class="listing-editor-global-footer" data-editor-global-footer>
          <div class="listing-editor-global-footer__actions">
            <button type="button" class="btn btn-label-secondary" data-editor-footer-prev hidden>
              <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
            </button>
            <button type="button" class="btn btn-primary" data-editor-footer-next hidden>
              Siguiente <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
            </button>
          </div>
        </div>
      @endif
    </div>
  </div>

  <form id="wompiCheckoutForm" method="POST" action="{{ route('mariachi.listings.payments.wompi.checkout', ['listing' => $listing->id]) }}" class="d-none">
    @csrf
    <input type="hidden" name="listing_id" value="{{ $listing->id }}" />
    <input type="hidden" name="plan_code" value="{{ $defaultPlan['code'] ?? array_key_first($plans) }}" data-payment-plan-code />
    <input type="hidden" name="term_months" value="{{ (int) ($defaultPlanTerm['months'] ?? 1) }}" data-payment-plan-term-months />
    <input type="hidden" name="amount_cop" value="{{ (int) ($defaultPlanTerm['total_price_cop'] ?? 0) }}" data-payment-plan-price />
  </form>

  <div class="modal fade" id="listing-map-picker-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-1">Elegir ubicación en el mapa</h5>
            <p class="text-muted mb-0">Mueve el pin hasta la entrada real del anuncio. Al confirmar, detectaremos dirección, ciudad, localidad y barrio.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4">
            <div class="col-lg-8">
              <div id="listing-map-picker-canvas" class="listing-map-canvas"></div>
            </div>
            <div class="col-lg-4">
              <div class="listing-map-sidebar">
                <h6 class="mb-2">Ubicación seleccionada</h6>
                <p class="text-muted small mb-3">Puedes arrastrar el pin o hacer clic en cualquier punto del mapa para afinar la posición.</p>
                <div class="alert alert-info small mb-3">
                  El mapa actualiza la localidad a partir del reverse geocode. Si esa localidad aún no existe en catálogo, la enviaremos como sugerencia admin.
                </div>
                <dl class="row mb-0 small">
                  <dt class="col-sm-4">Dirección</dt>
                  <dd class="col-sm-8" id="listing-map-picker-address">Mueve el pin para resolver la dirección exacta.</dd>
                  <dt class="col-sm-4">Coordenadas</dt>
                  <dd class="col-sm-8" id="listing-map-picker-coordinates">Sin coordenadas todavía.</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="listing-map-picker-confirm">Usar esta ubicación</button>
        </div>
      </div>
    </div>
  </div>

  @if($editorMode)
      </div>
    </div>
  @endif
@endsection
