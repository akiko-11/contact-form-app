<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーはタグを登録できる
    public function test_authenticated_user_can_create_tag(): void
    {
        // Arrange
        $user = User::factory()->create();

        $data = [
            'name' => '登録',
        ];

        // Act
        $response = $this->actingAs($user)->post('/admin/tags', $data);

        // Assert
        // 登録後のリダイレクトを確認
        $response->assertRedirect('/admin');

        // tagsテーブルに名前が登録されることを確認
        $this->assertDatabaseHas('tags', [
            'name' => '登録',
        ]);
    }

    // 認証済みユーザーは編集対象のタグ名を編集画面で確認できる
    public function test_authenticated_user_can_view_tag_edit_page(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        // Act
        $response = $this->actingAs($user)->get(
            "/admin/tags/{$tag->id}/edit"
        );

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.tags.edit');
        // admin.tags.editへtag変数が渡され、
        // その値がテストで作成したタグと
        // 同じDBレコードであることを確認
        $response->assertViewHas(
            'tag',
            fn ($viewTag) => $viewTag->is($tag)
        );
        $response->assertSee('質問');
    }

    // 認証済みユーザーはタグ名を更新できる
    public function test_authenticated_user_can_update_tag(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '変更前',
        ]);

        // Act
        $response = $this->actingAs($user)->put(
            "/admin/tags/{$tag->id}",
            ['name' => '変更後']
        );

        // Assert
        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '変更後',
        ]);

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
            'name' => '変更前',
        ]);
    }

    // 認証済みユーザーはタグを削除できる
    public function test_authenticated_user_can_delete_tag(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '削除対象',
        ]);

        // Act
        $response = $this->actingAs($user)->delete(
            "/admin/tags/{$tag->id}"
        );

        // Assert
        $response->assertRedirect('/admin');

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    // 未認証ユーザーはタグを登録できず、ログイン画面へリダイレクトされる
    public function test_guest_cannot_create_tag(): void
    {
        // Act
        $response = $this->post('/admin/tags', [
            'name' => '未認証タグ',
        ]);

        // Assert
        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('tags', [
            'name' => '未認証タグ',
        ]);
    }

    // 未認証ユーザーはタグ編集画面を表示できず、ログイン画面へリダイレクトされる
    public function test_guest_cannot_view_tag_edit_page(): void
    {
        // Arrange
        $tag = Tag::factory()->create([
            'name' => '編集対象',
        ]);

        // Act
        $response = $this->get("/admin/tags/{$tag->id}/edit");

        // Assert
        $response->assertRedirect('/login');
    }

    // 未認証ユーザーはタグを更新できず、ログイン画面へリダイレクトされる
    public function test_guest_cannot_update_tag(): void
    {
        // Arrange
        $tag = Tag::factory()->create([
            'name' => '変更前',
        ]);

        // Act
        $response = $this->put(
            "/admin/tags/{$tag->id}",
            ['name' => '変更後']
        );

        // Assert
        $response->assertRedirect('/login');

        // タグ名が更新されていないことを確認
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '変更前',
        ]);

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
            'name' => '変更後',
        ]);
    }

    // 未認証ユーザーはタグを削除できず、ログイン画面へリダイレクトされる
    public function test_guest_cannot_delete_tag(): void
    {
        // Arrange
        $tag = Tag::factory()->create([
            'name' => '削除対象',
        ]);

        // Act
        $response = $this->delete("/admin/tags/{$tag->id}");

        // Assert
        $response->assertRedirect('/login');

        // タグが削除されていないことを確認
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '削除対象',
        ]);
    }
}
