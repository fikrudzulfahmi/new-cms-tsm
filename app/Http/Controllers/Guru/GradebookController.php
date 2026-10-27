<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Submission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GradebookController extends Controller
{
    private function own(Request $r, Course $course): void
    {
        abort_unless($course->teacher_id === $r->user()->id, 403);
    }

    /** Susun matriks nilai: satu baris per siswa, satu kolom per tugas & quiz (persen). */
    private function matrix(Course $course): array
    {
        $assignments = Assignment::where('course_id', $course->id)->where('is_published', true)
            ->orderByRaw('due_at IS NULL')->orderBy('due_at')->get(['id', 'title', 'max_score']);
        $quizzes = Quiz::where('course_id', $course->id)->where('is_published', true)->orderBy('opens_at')->get(['id', 'title']);
        $students = $course->students();

        $scores = Submission::whereIn('assignment_id', $assignments->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))->get(['assignment_id', 'student_id', 'score', 'graded_at'])
            ->keyBy(fn ($s) => $s->assignment_id.'-'.$s->student_id);

        $best = QuizAttempt::whereIn('quiz_id', $quizzes->pluck('id'))->whereIn('student_id', $students->pluck('id'))
            ->whereNotNull('score')->selectRaw('quiz_id, student_id, max(score) best')
            ->groupBy('quiz_id', 'student_id')->get()->keyBy(fn ($r) => $r->quiz_id.'-'.$r->student_id);

        $rows = $students->map(function ($st) use ($assignments, $quizzes, $scores, $best) {
            $cells = [];
            foreach ($assignments as $a) {
                $sub = $scores[$a->id.'-'.$st->id] ?? null;
                $cells[] = $sub && $sub->score !== null && (float) $a->max_score > 0
                    ? round((float) $sub->score / (float) $a->max_score * 100, 2) : null;
            }
            foreach ($quizzes as $z) {
                $row = $best[$z->id.'-'.$st->id] ?? null;
                $cells[] = $row ? round((float) $row->best, 2) : null;
            }
            $filled = array_values(array_filter($cells, fn ($v) => $v !== null));

            return [
                'id' => $st->id, 'name' => $st->name, 'nis' => $st->username,
                'cells' => $cells,
                'average' => $filled ? round(array_sum($filled) / count($cells), 2) : null,
                'completed' => count($filled),
            ];
        })->values()->all();

        return [
            'columns' => array_merge(
                $assignments->map(fn ($a) => ['id' => 'a'.$a->id, 'title' => $a->title, 'kind' => 'Tugas'])->all(),
                $quizzes->map(fn ($z) => ['id' => 'z'.$z->id, 'title' => $z->title, 'kind' => 'Quiz'])->all(),
            ),
            'rows' => $rows,
        ];
    }

    public function index(Request $request, Course $course)
    {
        $this->own($request, $course);
        $m = $this->matrix($course);

        return Inertia::render('Lms/Guru/Gradebook', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'columns' => $m['columns'], 'rows' => $m['rows'],
        ]);
    }

    public function export(Request $request, Course $course)
    {
        $this->own($request, $course);
        $m = $this->matrix($course);

        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Buku Nilai');

        $head = ['No', 'NIS', 'Nama'];
        foreach ($m['columns'] as $c) {
            $head[] = $c['title'].' ('.$c['kind'].')';
        }
        $head[] = 'Rata-rata';
        $head[] = 'Terisi';

        $sheet->fromArray($head, null, 'A1');
        $row = 2;
        foreach ($m['rows'] as $i => $r) {
            $line = [$i + 1, $this->cell($r['nis']), $this->cell($r['name'])];
            foreach ($r['cells'] as $v) {
                $line[] = $v;
            }
            $line[] = $r['average'];
            $line[] = $r['completed'];
            $sheet->fromArray($line, null, 'A'.$row);
            $row++;
        }

        $last = count($head);
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($last);
        $sheet->getStyle('A1:'.$col.'1')->getFont()->setBold(true);
        $sheet->getStyle('A1:'.$col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $sheet->getStyle('A1:'.$col.'1')->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:'.$col.'1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('D2');
        foreach (range(1, $last) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }
        $sheet->getStyle('D2:'.$col.($row - 1))->getNumberFormat()->setFormatCode('0.00');
        if ($row > 2) {
            $sheet->getStyle('A1:'.$col.($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }

        $name = 'buku-nilai-'.preg_replace('/[^a-z0-9]+/', '-', strtolower($course->title)).'-'.date('Ymd').'.xlsx';
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($book))->save($tmp);

        return response()->download($tmp, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Cegah formula injection: teks yang diawali =,+,-,@ diberi kutip. */
    private function cell(?string $v): string
    {
        $v = (string) $v;

        return $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) ? "'".$v : $v;
    }
}
