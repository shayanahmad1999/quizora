<div class="page-heading"><div><span class="eyebrow">FOLLOW YOUR CURIOSITY</span><h1>Find your next challenge.</h1><p>Explore available quizzes and take your learning a little further.</p></div></div>
<form class="filter-bar" method="get" action="{{ route('quizzes.index') }}" data-ajax data-filter>
    <x-field name="q" label="Search quizzes" :value="request('q')" placeholder="Search a title or topic..."/>
    <x-field name="category_id" label="Subject" type="select" :value="request('category_id')" :options="[''=>'All subjects'] + $categories->pluck('name','id')->all()"/>
    @include('shared.per-page')<button type="submit" class="btn btn-primary">Apply</button><a data-nav href="{{ route('quizzes.index') }}" class="btn btn-light">Reset</a>
</form>
<div class="quiz-grid">@forelse($quizzes as $quiz)@include('quizzes.card')@empty<x-empty title="No quizzes found." text="Try a different search or check back when your administrator publishes more quizzes."/>@endforelse</div>
<x-pagination :paginator="$quizzes"/>
