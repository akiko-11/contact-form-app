<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    // テストごとにデータベースを初期状態へ戻す
    use RefreshDatabase;

    // お問い合わせが特定のカテゴリーに属し、複数のタグと同期できる
    public function test_contact_belongs_to_category_and_syncs_multiple_tags(): void
    {
        // Arrange

        $category = Category::factory()->create();

        // 上で作成したカテゴリーに属するお問い合わせを作成
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        // お問い合わせに紐づけるタグを2件作成
        $tags = Tag::factory()->count(2)->create();

        // Act

        // 2件のタグIDをcontact_tag中間テーブルへ同期
        $contact->tags()->sync($tags->pluck('id'));

        // categoryとtagsリレーションのデータを取得
        $contact->load(['category', 'tags']);

        // Assert

        // お問い合わせが作成したカテゴリーに属することを確認
        $this->assertTrue($contact->category->is($category));

        // お問い合わせに紐づくタグが2件であることを確認
        $this->assertCount(2, $contact->tags);

        // 作成した各タグが、お問い合わせのタグに含まれることを確認
        $this->assertTrue($contact->tags->contains($tags[0]));
        $this->assertTrue($contact->tags->contains($tags[1]));
    }
}
