<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'tel',
        'address',
        'building',
        'detail',
    ];

    public function scopeFilter(
        Builder $query,
        array $conditions
    ): Builder {
        // 名前（部分一致）・メール（部分一致）で絞り込み
        if (! empty($conditions['keyword'])) {
            $keyword = $conditions['keyword'];

            $query->where(function (Builder $keywordQuery) use ($keyword) {
                $keywordQuery
                    ->where('first_name', 'like', '%'.$keyword.'%')
                    ->orWhere('last_name', 'like', '%'.$keyword.'%')
                    ->orWhere('email', 'like', '%'.$keyword.'%');
            });
        }

        // 性別で絞り込み
        if (! empty($conditions['gender'])) {
            $query->where('gender', $conditions['gender']);
        }

        // カテゴリーで絞り込み
        if (! empty($conditions['category_id'])) {
            $query->where(
                'category_id',
                $conditions['category_id']
            );
        }

        // 日付で絞り込み
        if (! empty($conditions['date'])) {
            $query->whereDate(
                'created_at',
                $conditions['date']
            );
        }

        return $query;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->withTimestamps();
    }
}
