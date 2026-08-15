<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Services\ContactCsvExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    // お問い合わせ送信
    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();

        // contactsテーブルに存在しないタグIDを登録データから分離
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $contact = Contact::create($validated);

        // 選択したタグを中間テーブルに関連付け
        $contact->tags()->sync($tagIds);

        return redirect()->route('contact.thanks');
    }

    // CSV出力処理
    public function export(
        ExportContactRequest $request,
        ContactCsvExportService $contactCsvExportService
    ): StreamedResponse {

        // バリデーション済みの検索条件をサービスへ渡し、CSVを出力
        return $contactCsvExportService->download(
            $request->validated()
        );
    }

    // サンクスページ表示
    public function thanks()
    {
        return view('contact.thanks');
    }
}
