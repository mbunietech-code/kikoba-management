@props(['status', 'label' => null])
<x-badge :tone="status_tone($status)" dot>{{ $label ?? str($status)->replace('_', ' ')->title() }}</x-badge>
