<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    // 1つのタグが中間テーブルを介して複数のお問い合わせに紐づく
    public function test_tag_belongs_to_many_contacts(): void
    {
        // Arrange

        $category = Category::factory()->create();

        // 同じカテゴリーに属するお問い合わせを2件作成
        $contacts = Contact::factory()->count(2)->create([
            'category_id' => $category->id,
        ]);

        // お問い合わせに紐づけるタグを1件作成
        $tag = Tag::factory()->create();

        // Act

        // 1つのタグと2件のお問い合わせをcontact_tagへ登録
        $tag->contacts()->attach($contacts->pluck('id'));

        // Tagモデルのcontactsリレーションからお問い合わせを取得
        $relatedContacts = $tag->contacts;

        // Assert

        // タグに紐づくお問い合わせが2件であることを確認
        $this->assertCount(2, $relatedContacts);

        // 作成した各お問い合わせが取得結果に含まれることを確認
        $this->assertTrue($relatedContacts->contains($contacts[0]));
        $this->assertTrue($relatedContacts->contains($contacts[1]));
    }
}
