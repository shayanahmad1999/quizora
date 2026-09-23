@php $definition=config('resources.'.$resource); @endphp
<div class="page-heading"><div><span class="eyebrow">{{ strtoupper($definition['label']) }}</span><h1>{{ $record->exists ? 'Edit' : 'New' }} {{ $definition['singular'] }}</h1><p>Keep the details clear. The rest takes care of itself.</p></div><a data-nav class="btn btn-light" href="{{ route('admin.'.$resource.'.index') }}"><x-icon name="back"/> Back to list</a></div>
<section class="panel form-panel"><form method="post" action="{{ $record->exists ? route('admin.'.$resource.'.update',$record) : route('admin.'.$resource.'.store') }}" data-ajax>
@csrf @if($record->exists) @method('PATCH') @endif <x-errors/>
@include('admin.forms.'.$resource)
<div class="form-actions"><a data-nav class="btn btn-light" href="{{ route('admin.'.$resource.'.index') }}">Cancel</a><button class="btn btn-primary" type="submit">{{ $record->exists ? 'Save changes' : 'Create '.$definition['singular'] }} <x-icon name="check"/></button></div>
</form></section>
