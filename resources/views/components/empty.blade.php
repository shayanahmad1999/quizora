@props(['title'=>'Nothing here yet.','text'=>'Add your first record to get started.'])
<div class="empty-state"><span class="empty-icon"><x-icon name="layers"/></span><h3>{{ $title }}</h3><p>{{ $text }}</p>{{ $slot }}</div>
