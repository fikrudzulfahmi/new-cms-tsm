<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use ZipArchive;

/**
 * Membaca soal dari berkas Word (.docx) atau RTF dengan format teks sederhana:
 *   1. Teks soal
 *   A. opsi   B. opsi ...
 *   Jawaban: B            (PG)  | Jawaban: A,C (PG kompleks) | Jawaban: Benar (benar/salah)
 *   Jawaban: teks | teks2 (isian)  | tanpa Jawaban (uraian)
 *   Kiri => Kanan         (menjodohkan, min. 2 baris)
 *   Skor: 2   Pembahasan: ...   Jenis: uraian
 */
class QuestionImporter
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    /** @return string[] paragraf */
    public static function paragraphs(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \RuntimeException('Berkas terlalu besar (maks 2 MB).');
        }

        if ($ext === 'docx') {
            return self::fromDocx($file->getRealPath());
        }
        $raw = (string) file_get_contents($file->getRealPath());
        if ($ext === 'txt') {
            if (! mb_check_encoding($raw, 'UTF-8')) {
                throw new \RuntimeException('Berkas teks harus berenkode UTF-8.');
            }

            return array_map('trim', preg_split('/\R/u', $raw));
        }

        return self::fromRtf($raw);
    }

    private static function fromDocx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Berkas .docx tidak dapat dibuka.');
        }
        try {
            $stat = $zip->statName('word/document.xml');
            if (! $stat || $stat['size'] > 8 * 1024 * 1024) {
                throw new \RuntimeException('Isi dokumen tidak valid atau terlalu besar.');
            }
            $xml = $zip->getFromName('word/document.xml');
        } finally {
            $zip->close();
        }
        $dom = new DOMDocument;
        if (! @$dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new \RuntimeException('Isi dokumen rusak.');
        }
        $x = new DOMXPath($dom);
        $x->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $out = [];
        foreach ($x->query('//w:p') as $p) {
            $t = '';
            foreach ($x->query('.//w:t | .//w:tab | .//w:br', $p) as $n) {
                $t .= $n->localName === 't' ? $n->textContent : ($n->localName === 'tab' ? ' ' : "\n");
            }
            foreach (preg_split('/\R/u', $t) as $line) {
                $out[] = trim($line);
            }
        }

        return $out;
    }

    private static function fromRtf(string $rtf): array
    {
        if (! str_starts_with(ltrim($rtf), '{\\rtf')) {
            throw new \RuntimeException('Berkas bukan RTF yang valid.');
        }
        $rtf = preg_replace('/\{\\\*[^{}]*(\{[^{}]*\}[^{}]*)*\}/', '', $rtf);
        $rtf = preg_replace('/\{\\(fonttbl|colortbl|stylesheet|info|pict)\b(?:[^{}]|\{[^{}]*\})*\}/s', '', $rtf);
        $rtf = preg_replace_callback("/\\'([0-9a-f]{2})/i", fn ($m) => mb_convert_encoding(chr(hexdec($m[1])), 'UTF-8', 'Windows-1252'), $rtf);
        $rtf = preg_replace_callback('/\\u(-?\d+)\??./u', fn ($m) => mb_chr((int) $m[1] < 0 ? (int) $m[1] + 65536 : (int) $m[1], 'UTF-8'), $rtf);
        $rtf = preg_replace('/\\(par|line)\b ?/', "\n", $rtf);
        $rtf = preg_replace('/\\tab\b ?/', ' ', $rtf);
        $rtf = preg_replace('/\\[a-z]+-?\d* ?/i', '', $rtf);
        $rtf = str_replace(['{', '}', '\\\\'], ['', '', '\\'], $rtf);

        return array_map('trim', preg_split('/\R/u', $rtf));
    }

    /** @return array{items: array, errors: string[]} */
    public static function parse(array $lines): array
    {
        $items = [];
        $errors = [];
        $cur = null;
        $flush = function () use (&$cur, &$items, &$errors) {
            if ($cur) {
                [$item, $err] = self::build($cur);
                $err ? $errors[] = 'Soal '.$cur['no'].': '.$err : $items[] = $item;
            }
            $cur = null;
        };
        $last = null;
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            if (preg_match('/^(\d{1,3})\s*[.)]\s+(.+)$/u', $line, $m)) {
                $flush();
                $cur = ['no' => $m[1], 'body' => $m[2], 'opts' => [], 'pairs' => [], 'ans' => null, 'pts' => 1, 'exp' => '', 'type' => null];
                $last = 'body';
            } elseif (! $cur) {
                continue;
            } elseif (preg_match('/^([A-Ha-h])\s*[.)]\s+(.+)$/u', $line, $m)) {
                $cur['opts'][strtoupper($m[1])] = $m[2];
                $last = 'opt:'.strtoupper($m[1]);
            } elseif (preg_match('/^(jawaban|kunci)\s*:\s*(.*)$/iu', $line, $m)) {
                $cur['ans'] = trim($m[2]);
                $last = 'ans';
            } elseif (preg_match('/^(skor|poin|bobot)\s*:\s*(\d+(?:[.,]\d+)?)/iu', $line, $m)) {
                $cur['pts'] = (float) str_replace(',', '.', $m[2]);
                $last = null;
            } elseif (preg_match('/^pembahasan\s*:\s*(.*)$/iu', $line, $m)) {
                $cur['exp'] = $m[1];
                $last = 'exp';
            } elseif (preg_match('/^jenis\s*:\s*(.*)$/iu', $line, $m)) {
                $cur['type'] = strtolower(trim($m[1]));
                $last = null;
            } elseif (preg_match('/^(.{1,200}?)\s*=>\s*(.{1,200})$/u', $line, $m)) {
                $cur['pairs'][] = [trim($m[1]), trim($m[2])];
                $last = null;
            } elseif ($last === 'body') {
                $cur['body'] .= "\n".$line;
            } elseif ($last && str_starts_with($last, 'opt:')) {
                $cur['opts'][substr($last, 4)] .= ' '.$line;
            } elseif ($last === 'exp') {
                $cur['exp'] .= "\n".$line;
            }
        }
        $flush();

        return ['items' => $items, 'errors' => $errors];
    }

    private static function build(array $c): array
    {
        $body = trim($c['body']);
        $ans = $c['ans'];
        $base = ['body' => $body, 'explanation' => $c['exp'] ?: null, 'points' => $c['pts'] > 0 ? $c['pts'] : 1, 'options' => []];
        if (mb_strlen($body) > 5000) {
            return [null, 'teks soal terlalu panjang'];
        }
        $forced = match ($c['type']) {
            'uraian', 'essay' => 'uraian', 'isian' => 'isian', default => null,
        };
        if (count($c['pairs']) >= 2 && ! $forced) {
            $base['type'] = 'menjodohkan';
            foreach ($c['pairs'] as [$l, $r]) {
                $base['options'][] = ['body' => mb_substr($l, 0, 500), 'match_body' => mb_substr($r, 0, 500), 'is_correct' => false];
            }

            return [$base, null];
        }
        if (count($c['opts']) >= 2 && ! $forced) {
            if (! $ans) {
                return [null, 'pilihan ganda tanpa baris "Jawaban:"'];
            }
            $keys = array_values(array_filter(array_map('trim', preg_split('/[,\s;&]+/', strtoupper($ans)))));
            foreach ($keys as $k) {
                if (! isset($c['opts'][$k])) {
                    return [null, 'kunci "'.$k.'" tidak ada di pilihan'];
                }
            }
            $base['type'] = count($keys) > 1 ? 'pg_kompleks' : 'pg';
            foreach ($c['opts'] as $k => $t) {
                $base['options'][] = ['body' => mb_substr($t, 0, 500), 'match_body' => null, 'is_correct' => in_array($k, $keys, true)];
            }

            return [$base, null];
        }
        $low = mb_strtolower((string) $ans);
        if (! $forced && in_array($low, ['benar', 'salah', 'true', 'false', 'b', 's'], true) && ! $c['opts']) {
            $base['type'] = 'benar_salah';
            $isTrue = in_array($low, ['benar', 'true', 'b'], true);
            $base['options'] = [
                ['body' => 'Benar', 'match_body' => null, 'is_correct' => $isTrue],
                ['body' => 'Salah', 'match_body' => null, 'is_correct' => ! $isTrue],
            ];

            return [$base, null];
        }
        if ($forced !== 'uraian' && $ans) {
            $base['type'] = 'isian';
            foreach (array_filter(array_map('trim', explode('|', $ans))) as $a) {
                $base['options'][] = ['body' => mb_substr($a, 0, 500), 'match_body' => null, 'is_correct' => true];
            }

            return [$base, null];
        }
        $base['type'] = 'uraian';

        return [$base, null];
    }
}
