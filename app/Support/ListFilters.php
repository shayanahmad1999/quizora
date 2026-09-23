<?php
declare(strict_types=1);
namespace App\Support;
use Illuminate\Http\Request;
final class ListFilters
{
    public static function read(Request $request): array
    {
        return $request->validate([
            'q'=>['nullable','string','max:150'], 'page'=>['nullable','integer','min:1','max:100000'],
            'per_page'=>['nullable','integer','in:10,20,50'], 'category_id'=>['nullable','integer','min:1'],
            'quiz_id'=>['nullable','integer','min:1'], 'user_id'=>['nullable','integer','min:1'],
            'status'=>['nullable','in:active,inactive,published,draft,in_progress,submitted,expired'],
            'difficulty'=>['nullable','in:easy,medium,hard'], 'role'=>['nullable','in:learner,super_admin'],
        ]);
    }
    public static function search($query, ?string $term, array $columns): void
    {
        if ($term === null || $term === '') { return; }
        // Bound parameters and portable case-insensitive search (no raw user SQL).
        $term = '%'.mb_strtolower($term).'%';
        $query->where(function ($builder) use ($term, $columns) {
            foreach ($columns as $column) { $builder->orWhereRaw('LOWER('.$column.') LIKE ?', [$term]); }
        });
    }
}
