<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
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
    public function export(ExportContactRequest $request)
    {
        $validated = $request->validated();

        $contacts = Contact::with('category');

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

        // カテゴリーで絞り込み
        if (! empty($validated['category_id'])) {
            $contacts->where('category_id', $validated['category_id']);
        }

        // 日付で絞り込み
        if (! empty($validated['date'])) {
            $contacts->whereDate('created_at', $validated['date']);
        }

        // 新着順で全件取得、同一日時ではIDの降順
        $contacts = $contacts
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return response()->streamDownload(function () use ($contacts) {
            $stream = fopen('php://output', 'w');

            // UTF-8のBOMを出力
            fwrite($stream, "\xEF\xBB\xBF");

            // ヘッダー行
            fputcsv(
                $stream,
                ['ID', '氏名', '性別', 'メール', '電話', '住所', '建物', 'カテゴリ', '内容', '作成日時'],
                ',',
                '"',
                ''
            );

            $genderLabels = [
                1 => '男性',
                2 => '女性',
                3 => 'その他',
            ];

            foreach ($contacts as $contact) {
                fputcsv(
                    $stream,
                    [
                        $contact->id,
                        $contact->first_name.' '.$contact->last_name,
                        $genderLabels[$contact->gender] ?? '',
                        $contact->email,
                        $contact->tel,
                        $contact->address,
                        $contact->building,
                        $contact->category->content ?? '',
                        $contact->detail,
                        $contact->created_at,
                    ],
                    ',',
                    '"',
                    ''
                );
            }

            fclose($stream);
        }, 'contacts.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // サンクスページ表示
    public function thanks()
    {
        return view('contact.thanks');
    }
}
