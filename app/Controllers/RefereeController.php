<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Database;
use BBAF\Core\Request;
use BBAF\Core\Upload;
use BBAF\Core\View;

final class RefereeController
{
    public function form(Request $request): void
    {
        $row = $this->row($request->param('token'));
        if ($row === null) {
            flash('error', 'This link is invalid or has expired.');
            View::redirect('/');
        }
        View::render('public/referee', [
            'title' => 'Recommendation',
            'metaDescription' => 'Submit a recommendation or attestation.',
            'token' => $request->param('token'),
            'referee' => $row,
        ], 'auth');
    }

    public function submit(Request $request): void
    {
        $row = $this->row($request->param('token'));
        if ($row === null || $row['status'] === 'submitted') {
            flash('error', 'This link can no longer be used.');
            View::redirect('/');
        }
        $text = trim($request->inputRaw('response'));
        $stored = '';
        $original = '';
        $file = $request->file('letter');
        if ($file !== null) {
            try {
                $saved = Upload::storePrivate($file, Upload::documentMimes(), 5 * 1048576);
                $stored = $saved['stored_name'];
                $original = $saved['original_name'];
            } catch (\Throwable $error) {
                flash('error', $error->getMessage());
                View::redirect('/r/' . $request->param('token'));
            }
        }
        if ($text === '' && $stored === '') {
            flash('error', 'Write a short recommendation or upload a letter.');
            View::redirect('/r/' . $request->param('token'));
        }
        Database::exec(
            "UPDATE application_referees SET response_text = ?, stored_name = ?, original_name = ?, status = 'submitted', submitted_at = UTC_TIMESTAMP(), token_hash = NULL WHERE id = ?",
            [$text, $stored, $original, (int) $row['id']]
        );
        flash('success', 'Thank you. The Foundation has received your response.');
        View::redirect('/');
    }

    private function row(string $token): ?array
    {
        return Database::one(
            "SELECT * FROM application_referees WHERE token_hash = ? AND token_expires > UTC_TIMESTAMP()",
            [hash('sha256', $token)]
        );
    }
}
