<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    /**
     * お問い合わせ一覧取得
     */
    public function index(IndexContactRequest $request): AnonymousResourceCollection
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

        // 最新のレコードから20件ずつ表示
        $contacts = $contacts
            ->latest()
            ->paginate($validated['per_page'] ?? 20);

        return ContactResource::collection($contacts);
    }

    /**
     * お問い合わせ新規作成
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // tag_idsはcontactsテーブルの項目ではないため、登録データから分離
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        // お問い合わせを作成し、タグとの関連を中間テーブルに登録
        $contact = Contact::create($validated);
        $contact->tags()->attach($tagIds);

        // レスポンス用に関連データを読み込み、201 Createdで返却
        $contact->load(['category', 'tags']);

        return (new ContactResource($contact))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * お問い合わせ詳細取得
     */
    public function show(Contact $contact): ContactResource
    {
        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }

    /**
     * お問い合わせ更新
     */
    public function update(
        UpdateContactRequest $request,
        Contact $contact
    ): ContactResource {
        $validated = $request->validated();

        // tag_idsはcontactsテーブルの項目ではないため、更新データから分離
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        // お問い合わせを更新し、タグとの関連を同期
        $contact->update($validated);
        $contact->tags()->sync($tagIds);

        // レスポンス用に更新後の関連データを読み込み
        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }

    /**
     * お問い合わせ削除
     */
    public function destroy(Contact $contact): Response
    {
        $contact->delete();

        return response()->noContent();
    }
}
