<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContactApiTest extends TestCase
{
    use RefreshDatabase;

    // 以下、お問い合わせ一覧API
    // お問い合わせ一覧が関連情報を含むJSON形式で返る
    public function test_contacts_index_returns_json(): void
    {
        // Arrange
        $category = Category::factory()->create([
            'content' => '商品について',
        ]);

        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'yamada@example.com',
        ]);

        $contact->tags()->attach($tag->id);

        // Act
        $response = $this->getJson('/api/v1/contacts');

        // Assert
        $response->assertStatus(200);

        // 作成したお問い合わせと関連データが、正しい値で返ることを確認
        $response->assertJsonPath('data.0.id', $contact->id);
        $response->assertJsonPath('data.0.category.id', $category->id);
        $response->assertJsonPath('data.0.category.content', '商品について');
        $response->assertJsonPath('data.0.email', 'yamada@example.com');
        $response->assertJsonPath('data.0.tags.0.id', $tag->id);
        $response->assertJsonPath('data.0.tags.0.name', '質問');

        // 仕様どおりのJSON構造で返ることを確認
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'category' => [
                        'id',
                        'content',
                    ],
                    'first_name',
                    'last_name',
                    'gender',
                    'email',
                    'tel',
                    'address',
                    'building',
                    'detail',
                    'tags' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }

    // キーワードに一致するお問い合わせだけが返る
    public function test_contacts_can_be_filtered_by_keyword(): void
    {
        // Arrange
        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'yamada@example.com',
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '佐藤',
            'last_name' => '花子',
            'email' => 'sato@example.com',
        ]);

        // Act
        // キーワードを「山田」で指定して確認
        $response = $this->getJson('/api/v1/contacts?keyword=山田');

        // Assert
        $response->assertStatus(200);

        // 検索結果が1件だけ返ることを確認
        $response->assertJsonCount(1, 'data');

        // 検索した「山田」のデータが、正しい値で返ることを確認
        $response->assertJsonPath('data.0.first_name', '山田');
        $response->assertJsonPath('data.0.email', 'yamada@example.com');

        // Act
        // last_nameの部分一致検索を確認
        $lastNameResponse = $this->getJson(
            '/api/v1/contacts?keyword=花子'
        );

        // Assert
        $lastNameResponse->assertStatus(200);

        // 検索結果が1件だけ返ることを確認
        $lastNameResponse->assertJsonCount(1, 'data');
        $lastNameResponse->assertJsonPath(
            'data.0.email',
            'sato@example.com'
        );

        // Act
        // emailの部分一致検索を確認
        $emailResponse = $this->getJson(
            '/api/v1/contacts?keyword=sato'
        );

        // Assert
        $emailResponse->assertStatus(200);

        // 検索結果が1件だけ返ることを確認
        $emailResponse->assertJsonCount(1, 'data');
        $emailResponse->assertJsonPath(
            'data.0.email',
            'sato@example.com'
        );
    }

    // 性別に一致するお問い合わせだけが返る
    public function test_contacts_can_be_filtered_by_gender(): void
    {
        // Arrange
        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 1,
            'email' => 'male@example.com',
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 2,
            'email' => 'female@example.com',
        ]);

        // Act
        // 性別を「2」で指定して確認
        $response = $this->getJson('/api/v1/contacts?gender=2');

        // Assert
        $response->assertStatus(200);

        // 検索結果が1件だけ返ることを確認
        $response->assertJsonCount(1, 'data');

        // 性別が「2」のデータが、正しい値で返ることを確認
        $response->assertJsonPath('data.0.gender', 2);
        $response->assertJsonPath('data.0.email', 'female@example.com');
    }

    // カテゴリーに一致するお問い合わせだけが返る
    public function test_contacts_can_be_filtered_by_category(): void
    {
        // Arrange
        $productCategory = Category::factory()->create([
            'content' => '商品について',
        ]);

        $serviceCategory = Category::factory()->create([
            'content' => 'サービスについて',
        ]);

        Contact::factory()->create([
            'category_id' => $productCategory->id,
            'email' => 'product@example.com',
        ]);

        Contact::factory()->create([
            'category_id' => $serviceCategory->id,
            'email' => 'service@example.com',
        ]);

        // Act
        // 「商品について」のカテゴリーIDを指定して確認
        $response = $this->getJson(
            '/api/v1/contacts?category_id='.$productCategory->id
        );

        // Assert
        $response->assertStatus(200);

        // 検索結果が1件だけ返ることを確認
        $response->assertJsonCount(1, 'data');

        // 指定したカテゴリーのデータが、正しい値で返ることを確認
        $response->assertJsonPath(
            'data.0.category.id',
            $productCategory->id
        );
        $response->assertJsonPath(
            'data.0.category.content',
            '商品について'
        );
        $response->assertJsonPath(
            'data.0.email',
            'product@example.com'
        );
    }

    // 作成日に一致するお問い合わせだけが返る
    public function test_contacts_can_be_filtered_by_date(): void
    {
        // Arrange
        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'today@example.com',
            'created_at' => '2026-08-15 10:00:00',
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'yesterday@example.com',
            'created_at' => '2026-08-14 10:00:00',
        ]);

        // Act
        // 作成日を「2026-08-15」で指定して確認
        $response = $this->getJson(
            '/api/v1/contacts?date=2026-08-15'
        );

        // Assert
        $response->assertStatus(200);

        // 検索結果が1件だけ返ることを確認
        $response->assertJsonCount(1, 'data');

        // 指定した作成日のデータが、正しい値で返ることを確認
        $response->assertJsonPath(
            'data.0.email',
            'today@example.com'
        );
    }

    // 指定した件数で2ページ目のデータが返る
    public function test_contacts_can_be_paginated(): void
    {
        // Arrange
        $category = Category::factory()->create();

        Contact::factory()->count(5)->create([
            'category_id' => $category->id,
        ]);

        // Act
        // 1ページ2件として、2ページ目を指定して確認
        $response = $this->getJson(
            '/api/v1/contacts?per_page=2&page=2'
        );

        // Assert
        $response->assertStatus(200);

        // 2ページ目に2件のデータが返ることを確認
        $response->assertJsonCount(2, 'data');

        // ページネーション情報が正しいことを確認
        $response->assertJsonPath('meta.current_page', 2);
        $response->assertJsonPath('meta.last_page', 3);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 5);
    }

    // 不正な検索条件では422とエラーJSONが返る
    public function test_contacts_index_returns_422_for_invalid_parameters(): void
    {
        // Act
        // 許可されていない性別「0」を指定して確認
        $response = $this->getJson('/api/v1/contacts?gender=0');

        // Assert
        $response->assertStatus(422);

        // genderのバリデーションエラーが返ることを確認
        $response->assertJsonValidationErrors(['gender']);
        $response->assertJsonPath(
            'errors.gender.0',
            '性別の値が不正です'
        );
    }

    // 以下、お問い合わせ詳細API
    // お問い合わせ詳細が関連情報を含むJSON形式で返る
    public function test_contact_show_returns_json(): void
    {
        // Arrange
        $category = Category::factory()->create([
            'content' => '商品について',
        ]);

        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'yamada@example.com',
        ]);

        $contact->tags()->attach($tag->id);

        // Act
        $response = $this->getJson(
            '/api/v1/contacts/'.$contact->id
        );

        // Assert
        $response->assertStatus(200);

        // 指定したお問い合わせと関連データが、正しい値で返ることを確認
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath(
            'data.category.id',
            $category->id
        );
        $response->assertJsonPath(
            'data.category.content',
            '商品について'
        );
        $response->assertJsonPath('data.email', 'yamada@example.com');
        $response->assertJsonPath('data.tags.0.id', $tag->id);
        $response->assertJsonPath('data.tags.0.name', '質問');

        // 仕様どおりのJSON構造で返ることを確認
        $response->assertJsonStructure([
            'data' => [
                'id',
                'category' => [
                    'id',
                    'content',
                ],
                'first_name',
                'last_name',
                'gender',
                'email',
                'tel',
                'address',
                'building',
                'detail',
                'tags' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
                'created_at',
                'updated_at',
            ],
        ]);
    }

    // 存在しないお問い合わせでは404のエラーJSONが返る
    public function test_contact_show_returns_404_when_not_found(): void
    {
        // Act
        $response = $this->getJson('/api/v1/contacts/999999');

        // Assert
        $response->assertStatus(404);

        // 仕様どおりのエラーJSONが返ることを確認
        $response->assertExactJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }

    // 以下、お問い合わせ作成API
    // お問い合わせが作成され201と作成内容が返る
    public function test_contact_can_be_created(): void
    {
        // Arrange
        $category = Category::factory()->create([
            'content' => '商品について',
        ]);

        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '08012345678',
            'address' => '東京都渋谷区',
            'building' => 'テストマンション101',
            'category_id' => $category->id,
            'detail' => '商品について質問があります。',
            'tag_ids' => [$tag->id],
        ];

        // Act
        $response = $this->postJson('/api/v1/contacts', $data);

        // Assert
        $response->assertStatus(201);

        // お問い合わせがデータベースに作成されたことを確認
        $this->assertDatabaseHas('contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'yamada@example.com',
            'category_id' => $category->id,
        ]);

        $contact = Contact::where(
            'email',
            'yamada@example.com'
        )->firstOrFail();

        // タグとの関連が中間テーブルに作成されたことを確認
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        // 作成したお問い合わせがレスポンスに含まれることを確認
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.email', 'yamada@example.com');
        $response->assertJsonPath('data.tags.0.id', $tag->id);
    }

    // 不正な入力ではお問い合わせが作成されず422が返る
    public function test_contact_store_returns_422_for_invalid_data(): void
    {
        // Act
        // 必須項目を送信せずに作成を試みる
        $response = $this->postJson('/api/v1/contacts', []);

        // Assert
        $response->assertStatus(422);

        // 必須項目のバリデーションエラーが返ることを確認
        $response->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);

        // お問い合わせが作成されていないことを確認
        $this->assertDatabaseCount('contacts', 0);
    }

    // 以下、お問い合わせ更新API
    // お問い合わせが更新され200と更新内容が返る
    public function test_contact_can_be_updated(): void
    {
        // Arrange
        $oldCategory = Category::factory()->create([
            'content' => '商品について',
        ]);

        $newCategory = Category::factory()->create([
            'content' => 'サービスについて',
        ]);

        $oldTag = Tag::factory()->create([
            'name' => '質問',
        ]);

        $newTag = Tag::factory()->create([
            'name' => '要望',
        ]);

        $contact = Contact::factory()->create([
            'category_id' => $oldCategory->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'old@example.com',
        ]);

        $contact->tags()->attach($oldTag->id);

        $data = [
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'new@example.com',
            'tel' => '09012345678',
            'address' => '大阪府大阪市',
            'building' => '更新後マンション202',
            'category_id' => $newCategory->id,
            'detail' => '更新後のお問い合わせ内容です。',
            'tag_ids' => [$newTag->id],
        ];

        // Act
        $response = $this->putJson(
            '/api/v1/contacts/'.$contact->id,
            $data
        );

        // Assert
        $response->assertStatus(200);

        // お問い合わせがデータベースで更新されたことを確認
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '佐藤',
            'last_name' => '花子',
            'email' => 'new@example.com',
            'category_id' => $newCategory->id,
        ]);

        // タグとの関連が新しいタグへ更新されたことを確認
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $newTag->id,
        ]);

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $oldTag->id,
        ]);

        // 更新後の値がレスポンスに含まれることを確認
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.email', 'new@example.com');
        $response->assertJsonPath(
            'data.category.id',
            $newCategory->id
        );
        $response->assertJsonPath('data.tags.0.id', $newTag->id);
    }

    // 存在しないお問い合わせの更新では404が返る
    public function test_contact_update_returns_404_when_not_found(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '08012345678',
            'address' => '東京都渋谷区',
            'building' => null,
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
        ];

        // Act
        $response = $this->putJson(
            '/api/v1/contacts/999999',
            $data
        );

        // Assert
        $response->assertStatus(404);

        // 仕様どおりのエラーJSONが返ることを確認
        $response->assertExactJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }

    // 不正な入力ではお問い合わせが更新されず422が返る
    public function test_contact_update_returns_422_for_invalid_data(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'update@example.com',
        ]);

        // Act
        // 必須項目を送信せずに更新を試みる
        $response = $this->putJson(
            '/api/v1/contacts/'.$contact->id,
            []
        );

        // Assert
        $response->assertStatus(422);

        // 必須項目のバリデーションエラーが返ることを確認
        $response->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);

        // 元のお問い合わせが変更されていないことを確認
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'email' => 'update@example.com',
        ]);
    }

    // 以下、お問い合わせ削除API
    // お問い合わせが削除され204が返る
    public function test_contact_can_be_deleted(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        // Act
        $response = $this->deleteJson(
            '/api/v1/contacts/'.$contact->id
        );

        // Assert
        $response->assertStatus(204);

        // レスポンス本文が空であることを確認
        $response->assertNoContent();

        // お問い合わせがデータベースから削除されたことを確認
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    // 存在しないお問い合わせの削除では404が返る
    public function test_contact_delete_returns_404_when_not_found(): void
    {
        // Act
        $response = $this->deleteJson('/api/v1/contacts/999999');

        // Assert
        $response->assertStatus(404);

        // 仕様どおりのエラーJSONが返ることを確認
        $response->assertExactJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }
}
