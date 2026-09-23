@php $definition=config('resources.'.$resource); @endphp
<div class="page-heading"><div><span class="eyebrow">WORKSPACE MANAGEMENT</span><h1>{{ $definition['label'] }}</h1><p>{{ $definition['description'] }}</p></div><a class="btn btn-primary" data-nav href="{{ route('admin.'.$resource.'.create') }}"><x-icon name="plus"/> New {{ $definition['singular'] }}</a></div>
<form class="filter-bar" method="get" action="{{ route('admin.'.$resource.'.index') }}" data-ajax data-filter>
<x-field name="q" label="Search" :value="request('q')" placeholder="Find a record..."/>
@if(isset($categories))<x-field name="category_id" label="Subject" type="select" :value="request('category_id')" :options="[''=>'All subjects']+$categories->pluck('name','id')->all()"/>@endif
<x-field name="status" label="Status" type="select" :value="request('status')" :options="$resource==='quizzes' ? [''=>'All statuses','published'=>'Published','draft'=>'Draft'] : [''=>'All statuses','active'=>'Active','inactive'=>'Inactive']"/>
@if($resource==='users')<x-field name="role" label="Role" type="select" :value="request('role')" :options="[''=>'All roles','learner'=>'Learner','super_admin'=>'Super admin']"/>@endif
@if($resource==='questions')<x-field name="difficulty" label="Difficulty" type="select" :value="request('difficulty')" :options="[''=>'All levels','easy'=>'Easy','medium'=>'Medium','hard'=>'Hard']"/>@endif
@include('shared.per-page')<button class="btn btn-primary" type="submit">Apply</button><a class="btn btn-light" data-nav href="{{ route('admin.'.$resource.'.index') }}">Reset</a>
</form>
<section class="panel table-panel">@if($records->isEmpty())<x-empty title="No records found." text="Try a different filter, or create your first record."/>
@else<div class="table-wrap"><table class="data-table"><thead><tr>@foreach($definition['columns'] as $label)<th>{{ $label }}</th>@endforeach<th>Actions</th></tr></thead><tbody>
@foreach($records as $record)<tr>@foreach($definition['columns'] as $field=>$label)<td>
@if(in_array($field,['is_active','is_dark','is_published']))<span class="status-badge {{ data_get($record,$field) ? 'positive' : 'neutral' }}">{{ data_get($record,$field) ? 'Yes' : 'No' }}</span>
@elseif($field==='accent')<span class="color-sample" style="background:{{ $record->accent }}"></span><code>{{ $record->accent }}</code>
@elseif(in_array($field,['role','theme_mode','difficulty','layout']))<span class="soft-badge">{{ ucfirst(str_replace('_',' ',data_get($record,$field))) }}</span>
@else{{ Illuminate\Support\Str::limit((string)data_get($record,$field),$field==='prompt' ? 90 : 65) }}@endif
</td>@endforeach<td><div class="row-actions"><a class="btn btn-light btn-sm" data-nav href="{{ route('admin.'.$resource.'.edit',$record) }}">Edit</a><form action="{{ route('admin.'.$resource.'.destroy',$record) }}" method="post" data-ajax data-confirm="Delete this {{ $definition['singular'] }}? Historical quiz results are retained.">@csrf @method('DELETE')<button class="btn btn-danger-soft btn-sm" type="submit">Delete</button></form></div></td></tr>@endforeach
</tbody></table></div>@endif<x-pagination :paginator="$records"/></section>
