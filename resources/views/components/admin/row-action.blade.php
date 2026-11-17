{{--
    One icon button in an admin list's action column. The colour follows the
    action so every screen reads alike: view and restore green, edit orange,
    delete red, anything else grey. Wrap the buttons of one row in .row-actions.

    @param string $action  view | edit | delete | restore | more
    @param string $title   shown on hover and read by screen readers
    @param string $icon    Font Awesome classes; required for "more"
    @param string $href    makes it a link, otherwise it is a <button>
--}}
@props(['action', 'title', 'icon' => null, 'href' => null])
@php
    $icon ??= match ($action) {
        'view' => 'fas fa-eye',
        'edit' => 'fas fa-edit',
        'delete' => 'fas fa-trash',
        'restore' => 'fas fa-trash-restore',
    };
    $classes = ['row-action', 'row-action-' . ($action === 'restore' ? 'view' : $action)];
@endphp
@if ($href)
    <a href="{{ $href }}" title="{{ $title }}" aria-label="{{ $title }}" {{ $attributes->class($classes) }}><i class="{{ $icon }}"></i></a>
@else
    <button title="{{ $title }}" aria-label="{{ $title }}" {{ $attributes->merge(['type' => 'button'])->class($classes) }}><i class="{{ $icon }}"></i></button>
@endif
