<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;

class AdminController extends Controller
{
    public function index(IndexContactRequest $request)
    {
        $validated = $request->validated();

        $contacts = Contact::with(['category', 'tags']);

        // 名前（部分一致）・メール（部分一致）で絞り込み
        if (! empty($validated['keyword'])) {
            $contacts->where(function ($query) use ($validated) {
                $query->where('first_name', 'like', '%'.$validated['keyword'].'%')
                    ->orWhere('last_name', 'like', '%'.$validated['keyword'].'%')
                    ->orWhere('email', 'like', '%'.$validated['keyword'].'%');
            });
        }

        // 性別で絞り込み
        if (! empty($validated['gender'])) {
            $contacts->where('gender', $validated['gender']);
        }

        // カテゴリで絞り込み
        if (! empty($validated['category_id'])) {
            $contacts->where('category_id', $validated['category_id']);
        }

        // 日付で絞り込み
        if (! empty($validated['date'])) {
            $contacts->whereDate('created_at', $validated['date']);
        }

        // 7件ずつ表示
        $contacts = $contacts->paginate(7);
        $categories = Category::all();

        return view('admin.index', compact('contacts', 'categories'));
    }
}
