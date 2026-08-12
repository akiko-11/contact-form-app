<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenAccessTest extends TestCase
{
    use RefreshDatabase;

    // お問い合わせ入力画面にカテゴリーとタグが表示される
    public function test_contact_form_page_displays_categories_and_tags(): void
    {
        // Arrange
        $category = Category::factory()->create([
            'content' => '商品について',
        ]);

        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        // Act
        $response = $this->get('/');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('contact.index');
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');
        $response->assertSee($category->content);
        $response->assertSee($tag->name);
    }

    // サンクスページが正常に表示される
    public function test_thanks_page_is_displayed(): void
    {
        // Act
        $response = $this->get('/thanks');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('contact.thanks');
        $response->assertSee('お問い合わせありがとうございました');
    }

    // 認証済みユーザーは管理画面を表示できる
    public function test_authenticated_user_can_access_admin_dashboard(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get('/admin');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.index');
        $response->assertViewHas('contacts');
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');
    }

    // 未認証ユーザーは管理画面へアクセスするとログイン画面へリダイレクトされる
    public function test_guest_is_redirected_to_login_from_admin_dashboard(): void
    {
        // Act
        $response = $this->get('/admin');

        // Assert
        $response->assertRedirect('/login');
    }
}
