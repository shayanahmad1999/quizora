<div class="form-errors" data-errors role="alert" @if(!$errors->any()) hidden @endif>
@if($errors->any())<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
</div>
