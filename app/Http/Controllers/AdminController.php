<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;

class AdminController extends Controller
{
    public function index(IndexContactRequest $request)
    {
        $validated = $request->validated();

        // 共通の検索条件を適用し、7件ずつ表示
        $contacts = Contact::with(['category', 'tags'])
            ->filter($validated)
            ->paginate(7);

        $categories = Category::all();
        $tags = Tag::all();

        return view(
            'admin.index',
            compact('contacts', 'categories', 'tags')
        );
    }

    // お問い合わせ詳細ページ表示
    public function show(Contact $contact)
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    // お問い合わせ削除
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect('/admin');
    }
}
