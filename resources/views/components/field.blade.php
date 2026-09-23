@props(['name','label','type'=>'text','value'=>null,'options'=>[],'help'=>null,'required'=>false,'multiple'=>false])
<label class="field">
    <span class="field-label">{{ $label }} @if($required)<span class="required-mark">*</span>@endif</span>
    @if($type === 'textarea')
        <textarea name="{{ $name }}" {{ $attributes->class(['form-control']) }} @required($required)>{{ old($name,$value) }}</textarea>
    @elseif($type === 'select')
        <select name="{{ $name }}" {{ $attributes->class(['form-select']) }} @required($required) @if($multiple) multiple @endif>
            @foreach($options as $key=>$text)<option value="{{ $key }}" @selected($multiple ? in_array((string)$key,array_map('strval',(array)old(str_replace('[]','',$name),$value ?? [])),true) : (string)old($name,$value)===(string)$key)>{{ $text }}</option>@endforeach
        </select>
    @else
        <input type="{{ $type }}" name="{{ $name }}" value="{{ $type==='password' ? '' : old($name,$value) }}" {{ $attributes->class(['form-control']) }} @required($required)>
    @endif
    @if($help)<small class="field-help">{{ $help }}</small>@endif
</label>
