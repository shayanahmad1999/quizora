@props(['name','label','value'=>false,'help'=>null])
<label class="toggle-field"><input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" name="{{ $name }}" value="1" @checked(old($name,$value))><span><strong>{{ $label }}</strong>@if($help)<small>{{ $help }}</small>@endif</span></label>
