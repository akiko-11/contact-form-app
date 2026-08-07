# COACHTECH お問い合わせフォーム

## 概要

一般ユーザーが利用できる公開のお問い合わせフォームです。

一般ユーザーは、お問い合わせ内容を入力・確認して送信できます。
管理者はログイン後、お問い合わせ内容の確認・検索・削除を行えます。

## ER図

## 環境構築

```bash
git clone <リポジトリURL>
cd <プロジェクト名>
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

## 使用技術

- HTML
- CSS
- PHP 8.5.7
- Laravel 10.50.2
- MySQL 8.4.10
- Vite
- Tailwind CSS 3.4.19
- Docker
- Laravel Sail
- phpMyAdmin



## エンドポイント一覧


## 開発環境URL

- お問い合わせフォーム：`http://localhost`
- phpMyAdmin：`http://localhost:8080`

## 作成者

渡利明子