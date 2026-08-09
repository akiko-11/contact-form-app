<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    // 1つのカテゴリから、紐づく複数のお問い合わせ（hasMany）が正しく取得できる
    public function test_category_has_many_contacts(): void
    {
        // Arrange
        $category = Category::factory()->create();

        // 同じカテゴリーに属するお問い合わせを2件作成
        $contacts = Contact::factory()->count(2)->create([
            'category_id' => $category->id,
        ]);

        // Act

        // Categoryモデルのcontactsリレーションを通して、
        // このカテゴリーに紐づくお問い合わせを取得
        $relatedContacts = $category->contacts;

        // Assert

        // 紐づくお問い合わせが2件取得できたことを確認
        $this->assertCount(2, $relatedContacts);

        // 作成された各お問い合わせが取得結果に含まれることを確認
        $this->assertTrue($relatedContacts->contains($contacts[0]));
        $this->assertTrue($relatedContacts->contains($contacts[1]));
    }
}
