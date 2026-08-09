<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;

class ContactController extends Controller
{
    // お問い合わせフォーム入力ページ
    public function index()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    // お問い合わせフォーム確認ページ
    public function confirm(StoreContactRequest $request)
    {
        $validated = $request->validated();

        // 「修正」で入力画面に戻った際に入力内容を復元するため、入力値を一時保存
        $request->flash();

        $category = Category::find($validated['category_id']);
        $tags = Tag::findMany($validated['tag_ids'] ?? []);

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    public function store(StoreContactRequest $request)
    {
        // 次のIssueで実際の登録処理を実装
    }
}
