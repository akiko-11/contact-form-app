<?php

namespace App\Services;

use App\Models\Contact;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactCsvExportService
{
    public function download(array $conditions): StreamedResponse
    {
        // 共通の検索条件を適用
        $contacts = Contact::with('category')
            ->filter($conditions);

        // 新着順で全件取得、同一日時ではIDの降順
        $contacts = $contacts
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return response()->streamDownload(function () use ($contacts) {
            $stream = fopen('php://output', 'w');

            // UTF-8のBOMを出力
            fwrite($stream, "\xEF\xBB\xBF");

            // ヘッダー行
            fputcsv(
                $stream,
                ['ID', '氏名', '性別', 'メール', '電話', '住所', '建物', 'カテゴリ', '内容', '作成日時'],
                ',',
                '"',
                ''
            );

            $genderLabels = [
                1 => '男性',
                2 => '女性',
                3 => 'その他',
            ];

            foreach ($contacts as $contact) {
                fputcsv(
                    $stream,
                    [
                        $contact->id,
                        $contact->first_name.' '.$contact->last_name,
                        $genderLabels[$contact->gender] ?? '',
                        $contact->email,
                        $contact->tel,
                        $contact->address,
                        $contact->building,
                        $contact->category->content ?? '',
                        $contact->detail,
                        $contact->created_at,
                    ],
                    ',',
                    '"',
                    ''
                );
            }

            fclose($stream);
        }, 'contacts.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
