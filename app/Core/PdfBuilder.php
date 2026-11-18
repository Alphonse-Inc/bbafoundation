<?php

declare(strict_types=1);

namespace BBAF\Core;

/**
 * Thin wrapper around the vendored FPDF library.
 * Core fonts are WinAnsi, so text is transliterated before it is drawn.
 */
final class PdfBuilder
{
    private \FPDF $pdf;

    public function __construct(string $title)
    {
        $this->boot();
        $this->pdf = new \FPDF();
        $this->pdf->SetTitle(self::plain($title), true);
        $this->pdf->SetAuthor(self::plain(Settings::get('org_short', 'BBAFoundation')));
        $this->pdf->SetAutoPageBreak(true, 18);
        $this->pdf->AddPage();
        $this->headerBand($title);
    }

    public function h2(string $text): void
    {
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Helvetica', 'B', 13);
        $this->pdf->SetTextColor(15, 42, 74);
        $this->pdf->MultiCell(0, 7, self::plain($text));
        $this->pdf->SetTextColor(30, 41, 59);
        $this->pdf->Ln(1);
    }

    public function p(string $text): void
    {
        $this->pdf->SetFont('Helvetica', '', 11);
        $this->pdf->MultiCell(0, 6, self::plain($text));
        $this->pdf->Ln(1);
    }

    public function field(string $label, string $value): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 10);
        $this->pdf->SetTextColor(71, 85, 105);
        $this->pdf->Cell(55, 6, self::plain($label), 0, 0);
        $this->pdf->SetFont('Helvetica', '', 11);
        $this->pdf->SetTextColor(30, 41, 59);
        $this->pdf->MultiCell(0, 6, self::plain($value === '' ? '—' : $value));
    }

    public function qr(string $payload, float $size = 32): void
    {
        $file = self::qrPng($payload);
        if ($file === null) {
            $this->p($payload);
            return;
        }
        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY();
        $this->pdf->Image($file, $x, $y, $size, $size);
        $this->pdf->Ln($size + 2);
        @unlink($file);
    }

    public function output(): string
    {
        return $this->pdf->Output('S');
    }

    public static function qrPng(string $payload): ?string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }
        require_once BBAF_ROOT . '/lib/qrcode.php';
        $qr = \QRCode::getMinimumQRCode($payload, QR_ERROR_CORRECT_LEVEL_M);
        $count = $qr->getModuleCount();
        $scale = 6;
        $quiet = 4;
        $pixels = ($count + ($quiet * 2)) * $scale;
        $image = imagecreatetruecolor($pixels, $pixels);
        if ($image === false) {
            return null;
        }
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 15, 42, 74);
        imagefilledrectangle($image, 0, 0, $pixels, $pixels, $white);
        for ($row = 0; $row < $count; $row++) {
            for ($col = 0; $col < $count; $col++) {
                if ($qr->isDark($row, $col)) {
                    $x = ($col + $quiet) * $scale;
                    $y = ($row + $quiet) * $scale;
                    imagefilledrectangle($image, $x, $y, $x + $scale - 1, $y + $scale - 1, $ink);
                }
            }
        }
        $file = BBAF_ROOT . '/storage/cache/qr-' . bin2hex(random_bytes(6)) . '.png';
        imagepng($image, $file);
        imagedestroy($image);

        return $file;
    }

    private function headerBand(string $title): void
    {
        $this->pdf->SetFillColor(15, 42, 74);
        $this->pdf->Rect(0, 0, 210, 28, 'F');
        $this->pdf->SetFillColor(217, 165, 32);
        $this->pdf->Rect(0, 28, 210, 2.5, 'F');
        $this->pdf->SetY(8);
        $this->pdf->SetTextColor(250, 248, 243);
        $this->pdf->SetFont('Helvetica', 'B', 14);
        $this->pdf->Cell(0, 7, self::plain(Settings::get('legal_name', 'Biodun and Bolanle Arokodare Foundation')), 0, 1, 'C');
        $this->pdf->SetFont('Helvetica', '', 10);
        $this->pdf->Cell(0, 6, self::plain(Settings::get('tagline', 'A manifestation of education objectives')), 0, 1, 'C');
        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(15, 42, 74);
        $this->pdf->SetFont('Helvetica', 'B', 16);
        $this->pdf->MultiCell(0, 8, self::plain($title), 0, 'L');
        $this->pdf->SetTextColor(30, 41, 59);
    }

    private function boot(): void
    {
        if (!class_exists(\FPDF::class)) {
            define('FPDF_FONTPATH', BBAF_ROOT . '/lib/fpdf/font/');
            require_once BBAF_ROOT . '/lib/fpdf/fpdf.php';
        }
    }

    public static function plain(string $text): string
    {
        $map = [
            '—' => '-', '–' => '-', '‘' => "'", '’' => "'", '“' => '"', '”' => '"',
            '…' => '...', '₦' => 'NGN ', "\u{00A0}" => ' ',
        ];
        $text = strtr($text, $map);
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

        return $converted === false ? preg_replace('/[^\x20-\x7E]/', '', $text) ?? '' : $converted;
    }
}
