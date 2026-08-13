<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactSubmitFlowTest extends TestCase
{
    use RefreshDatabase;

    // 正しい入力内容でお問い合わせ確認ページが表示される
    public function test_confirm_page_displays_submitted_contact_data(): void
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
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => [$tag->id],
        ];

        // Act
        $response = $this->post('/contacts/confirm', $data);

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('contact.confirm');
        $response->assertViewHas('validated');
        $response->assertViewHas('category');
        $response->assertViewHas('tags');
        $response->assertSee('山田 太郎');
        $response->assertSee('taro@example.com');
        $response->assertSee('商品について');
        $response->assertSee('質問');
        $response->assertSee('お問い合わせ内容です。');
    }

    // 必須項目が空の場合は入力画面へ戻り、バリデーションエラーが返される
    public function test_confirm_with_empty_required_fields_redirects_with_errors(): void
    {
        // Act
        $response = $this->from('/')->post('/contacts/confirm', []);

        // Assert
        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);
    }

    // 正しい入力内容でお問い合わせと選択したタグが登録される
    public function test_valid_contact_is_stored_with_selected_tag(): void
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
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => [$tag->id],
        ];

        // Act
        $response = $this->post('/contacts', $data);

        // Assert
        $response->assertRedirect('/thanks');

        $this->assertDatabaseHas('contacts', [
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'detail' => 'お問い合わせ内容です。',
        ]);

        $contact = Contact::where('email', 'taro@example.com')->firstOrFail();

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    // 必須項目が空の場合は登録されず、バリデーションエラーが返される
    public function test_store_with_empty_required_fields_redirects_with_errors(): void
    {
        // Act
        $response = $this->post('/contacts', []);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);

        $this->assertDatabaseCount('contacts', 0);
    }
}
