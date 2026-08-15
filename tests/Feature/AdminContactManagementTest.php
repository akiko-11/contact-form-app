<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactManagementTest extends TestCase
{
    use RefreshDatabase;

    // キーワードに一致するお問い合わせだけが表示される
    public function test_contacts_can_be_filtered_by_keyword(): void
    {
        // Arrange
        $user = User::factory()->create();
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
        $response = $this->actingAs($user)->get('/admin?keyword=山田');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.index');
        $response->assertSee('yamada@example.com');
        $response->assertDontSee('sato@example.com');
    }

    // 指定した性別のお問い合わせだけが表示される
    public function test_contacts_can_be_filtered_by_gender(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 2,
            'email' => 'female@example.com',
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 1,
            'email' => 'other-gender@example.com',
        ]);

        // Act
        $response = $this->actingAs($user)->get('/admin?gender=2');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('female@example.com');
        $response->assertDontSee('other-gender@example.com');
    }

    // 指定したカテゴリーのお問い合わせだけが表示される
    public function test_contacts_can_be_filtered_by_category(): void
    {
        // Arrange
        $user = User::factory()->create();

        $targetCategory = Category::factory()->create([
            'content' => '商品について',
        ]);

        $otherCategory = Category::factory()->create([
            'content' => 'サービスについて',
        ]);

        Contact::factory()->create([
            'category_id' => $targetCategory->id,
            'email' => 'target-category@example.com',
        ]);

        Contact::factory()->create([
            'category_id' => $otherCategory->id,
            'email' => 'other-category@example.com',
        ]);

        // Act
        $response = $this->actingAs($user)->get(
            "/admin?category_id={$targetCategory->id}"
        );

        // Assert
        $response->assertStatus(200);
        $response->assertSee('target-category@example.com');
        $response->assertDontSee('other-category@example.com');
    }

    // 指定した日付のお問い合わせだけが表示される
    public function test_contacts_can_be_filtered_by_date(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'target-date@example.com',
            'created_at' => '2026-08-13 10:00:00',
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'other-date@example.com',
            'created_at' => '2026-08-12 10:00:00',
        ]);

        // Act
        $response = $this->actingAs($user)->get('/admin?date=2026-08-13');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('target-date@example.com');
        $response->assertDontSee('other-date@example.com');
    }

    // お問い合わせ一覧が1ページにつき7件で表示される
    public function test_contacts_are_paginated_seven_per_page(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Contact::factory()->count(8)->create([
            'category_id' => $category->id,
        ]);

        // Act
        $firstResponse = $this->actingAs($user)->get('/admin');
        $secondResponse = $this->get('/admin?page=2');

        $firstPageContacts = $firstResponse->viewData('contacts');
        $secondPageContacts = $secondResponse->viewData('contacts');

        // Assert
        $firstResponse->assertStatus(200);

        // 1ページあたりの表示件数が7件であることを確認
        $this->assertSame(7, $firstPageContacts->perPage());

        // 1ページ目に実際に表示される件数を確認
        $this->assertCount(7, $firstPageContacts);

        // 全ページを合わせた件数を確認
        $this->assertSame(8, $firstPageContacts->total());

        $secondResponse->assertStatus(200);

        // 2ページ目であることを確認
        $this->assertSame(2, $secondPageContacts->currentPage());

        // 1ページ目に7件表示された残りの1件を確認
        $this->assertCount(1, $secondPageContacts);
    }

    // 指定したお問い合わせとカテゴリー情報が詳細画面に表示される
    public function test_contact_detail_is_displayed_with_category(): void
    {
        // Arrange
        $user = User::factory()->create();

        $category = Category::factory()->create([
            'content' => '商品について',
        ]);

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'yamada@example.com',
            'detail' => '商品の詳細について確認したいです。',
        ]);

        // Act
        $response = $this->actingAs($user)->get(
            "/admin/contacts/{$contact->id}"
        );

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.show');
        // admin.showへcontactという変数が渡され、
        // その値がテストで指定したお問い合わせと
        // 同じレコードであることを確認
        $response->assertViewHas(
            'contact',
            fn ($viewContact) => $viewContact->is($contact)
        );
        $response->assertSee('山田 太郎');
        $response->assertSee('yamada@example.com');
        $response->assertSee('商品について');
        $response->assertSee('商品の詳細について確認したいです。');
    }

    // 指定したお問い合わせが削除され、管理画面へリダイレクトされる
    public function test_contact_can_be_deleted(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'delete@example.com',
        ]);

        // Act
        $response = $this->actingAs($user)->delete(
            "/admin/contacts/{$contact->id}"
        );

        // Assert
        // 削除後に管理画面へリダイレクトされることを確認
        $response->assertRedirect('/admin');

        // 指定したお問い合わせが削除されたことを確認
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }
}
