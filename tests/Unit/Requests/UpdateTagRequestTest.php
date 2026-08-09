<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateTagRequestTest extends TestCase
{
    use RefreshDatabase;

    // 現在のタグ名を変更せずに更新できる
    public function test_current_tag_name_passes_validation(): void
    {
        // Arrange
        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        $data = [
            'name' => '質問',
        ];

        $request = $this->createUpdateTagRequest($tag);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    // ほかのタグが使用している名前への変更は拒否される
    public function test_name_used_by_another_tag_fails_validation(): void
    {
        // Arrange
        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        Tag::factory()->create([
            'name' => '要望',
        ]);

        $data = [
            'name' => '要望',
        ];

        $request = $this->createUpdateTagRequest($tag);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    private function createUpdateTagRequest(Tag $tag): UpdateTagRequest
    {
        // 仮のルートを作成
        $route = new Route(
            ['PUT'],
            'admin/tags/{tag}',
            fn () => null
        );

        // URLとルートを結び付ける
        $route->bind(
            Request::create("/admin/tags/{$tag->id}", 'PUT')
        );

        // ルートの{tag}にTagを設定
        $route->setParameter('tag', $tag);

        // UpdateTagRequestにルートを設定
        $request = new UpdateTagRequest;
        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}
