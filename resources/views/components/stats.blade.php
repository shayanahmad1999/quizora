@props(['stats'])
<div class="stats-grid">
    @foreach($stats as $label=>$value)<div class="stat-card"><span>{{ $label }}</span><strong>{{ $value }}</strong><div class="stat-foot"><span class="tiny-dot"></span>Workspace insights</div></div>@endforeach
</div>
