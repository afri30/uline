<?php

namespace App\Controllers\Guru;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Teacher;
use Exception;

class QuestionController extends Controller
{
    private Question $questionModel;
    private Subject $subjectModel;
    private Teacher $teacherModel;

    public function __construct()
    {
        parent::__construct();
        $this->questionModel = new Question();
        $this->subjectModel = new Subject();
        $this->teacherModel = new Teacher();
    }

    /**
     * Helper to get current authenticated teacher record
     *
     * @return array
     * @throws Exception
     */
    private function getCurrentTeacher(): array
    {
        $currentUser = auth();
        $userId = (int)($currentUser['id'] ?? 0);
        $teacher = $this->teacherModel->findByUserId($userId);

        if (!$teacher) {
            throw new Exception("Profil pengajar (Guru) tidak ditemukan untuk akun ini.");
        }

        return $teacher;
    }

    /**
     * Display question bank list
     */
    public function index(): void
    {
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            $this->redirect('guru/dashboard');
            return;
        }

        $teacherId = (int)$teacher['id'];
        $search = $this->request->string('q');
        $subjectId = $this->request->string('subject_id');
        $difficulty = $this->request->string('difficulty');
        $type = $this->request->string('type');
        $page = max(1, (int)$this->request->input('page', 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $filters = [
            'search' => $search,
            'subject_id' => $subjectId,
            'difficulty' => $difficulty,
            'type' => $type
        ];

        $totalItems = $this->questionModel->countByTeacher($teacherId, $filters);
        $questions = $this->questionModel->getByTeacher($teacherId, $filters, $perPage, $offset);

        // Get subjects taught by this teacher (or all school subjects)
        $mySubjects = $this->subjectModel->getByTeacherId($teacherId);
        if (empty($mySubjects)) {
            $mySubjects = $this->subjectModel->findAll('name', 'ASC');
        }

        // Stats summary for teacher
        $stats = [
            'total' => $this->questionModel->countByTeacher($teacherId),
            'multiple_choice' => $this->questionModel->countByTeacher($teacherId, ['type' => 'multiple_choice']),
            'true_false' => $this->questionModel->countByTeacher($teacherId, ['type' => 'true_false']),
            'essay' => $this->questionModel->countByTeacher($teacherId, ['type' => 'essay']),
        ];

        $this->view('guru/questions/index', [
            'title' => 'Bank Soal Ujian - Panel Guru',
            'teacher' => $teacher,
            'questions' => $questions,
            'mySubjects' => $mySubjects,
            'totalItems' => $totalItems,
            'currentPage' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'subjectId' => $subjectId,
            'difficulty' => $difficulty,
            'type' => $type,
            'stats' => $stats
        ]);
    }

    /**
     * Store new question
     */
    public function store(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('guru/questions');
            return;
        }

        try {
            $teacher = $this->getCurrentTeacher();
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            $this->redirect('guru/questions');
            return;
        }

        $teacherId = (int)$teacher['id'];
        $subjectId = (int)$this->request->input('subject_id');
        $questionType = $this->request->string('question_type', 'multiple_choice');
        $questionText = $this->request->string('question_text');
        $difficulty = $this->request->string('difficulty', 'medium');
        $score = max(1, (int)$this->request->input('score', 10));

        // Options
        $optionA = $this->request->string('option_a');
        $optionB = $this->request->string('option_b');
        $optionC = $this->request->string('option_c');
        $optionD = $this->request->string('option_d');
        $correctAnswer = $this->request->string('correct_answer');

        // Validation
        if (empty($subjectId) || empty($questionText)) {
            Session::setFlash('error', 'Mata Pelajaran dan Teks Soal wajib diisi.');
            $this->redirect('guru/questions');
            return;
        }

        if ($questionType === 'multiple_choice') {
            if (empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD)) {
                Session::setFlash('error', 'Untuk soal Pilihan Ganda, semua Pilihan (A, B, C, D) wajib diisi.');
                $this->redirect('guru/questions');
                return;
            }
            if (empty($correctAnswer) || !in_array(strtoupper($correctAnswer), ['A', 'B', 'C', 'D'], true)) {
                Session::setFlash('error', 'Kunci jawaban pilihan ganda harus dipilih (A, B, C, atau D).');
                $this->redirect('guru/questions');
                return;
            }
            $correctAnswer = strtoupper($correctAnswer);
        } elseif ($questionType === 'true_false') {
            $optionA = null;
            $optionB = null;
            $optionC = null;
            $optionD = null;
            if (!in_array(strtoupper($correctAnswer), ['TRUE', 'FALSE'], true)) {
                Session::setFlash('error', 'Kunci jawaban Benar/Salah harus dipilih (TRUE atau FALSE).');
                $this->redirect('guru/questions');
                return;
            }
            $correctAnswer = strtoupper($correctAnswer);
        } else { // essay
            $optionA = null;
            $optionB = null;
            $optionC = null;
            $optionD = null;
            if (empty($correctAnswer)) {
                $correctAnswer = 'Panduan penilaian essay.';
            }
        }

        try {
            $this->questionModel->create([
                'teacher_id' => $teacherId,
                'subject_id' => $subjectId,
                'question_text' => $questionText,
                'question_type' => $questionType,
                'option_a' => $optionA,
                'option_b' => $optionB,
                'option_c' => $optionC,
                'option_d' => $optionD,
                'correct_answer' => $correctAnswer,
                'score' => $score,
                'difficulty' => in_array($difficulty, ['easy', 'medium', 'hard'], true) ? $difficulty : 'medium'
            ]);

            Session::setFlash('success', 'Soal ujian baru berhasil ditambahkan ke Bank Soal!');
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal menyimpan soal: ' . $e->getMessage());
        }

        $this->redirect('guru/questions');
    }

    /**
     * Update existing question (with ownership check)
     */
    public function update(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('guru/questions');
            return;
        }

        try {
            $teacher = $this->getCurrentTeacher();
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            $this->redirect('guru/questions');
            return;
        }

        $teacherId = (int)$teacher['id'];
        $questionId = (int)$this->request->input('id');

        // Security Ownership check
        if (!$this->questionModel->isOwnedBy($questionId, $teacherId)) {
            Session::setFlash('error', 'Akses ditolak. Anda tidak memiliki hak untuk mengubah soal ini.');
            $this->redirect('guru/questions');
            return;
        }

        $subjectId = (int)$this->request->input('subject_id');
        $questionType = $this->request->string('question_type', 'multiple_choice');
        $questionText = $this->request->string('question_text');
        $difficulty = $this->request->string('difficulty', 'medium');
        $score = max(1, (int)$this->request->input('score', 10));

        $optionA = $this->request->string('option_a');
        $optionB = $this->request->string('option_b');
        $optionC = $this->request->string('option_c');
        $optionD = $this->request->string('option_d');
        $correctAnswer = $this->request->string('correct_answer');

        if (empty($subjectId) || empty($questionText)) {
            Session::setFlash('error', 'Mata Pelajaran dan Teks Soal wajib diisi.');
            $this->redirect('guru/questions');
            return;
        }

        if ($questionType === 'multiple_choice') {
            if (empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD)) {
                Session::setFlash('error', 'Untuk soal Pilihan Ganda, semua Pilihan (A, B, C, D) wajib diisi.');
                $this->redirect('guru/questions');
                return;
            }
            if (empty($correctAnswer) || !in_array(strtoupper($correctAnswer), ['A', 'B', 'C', 'D'], true)) {
                Session::setFlash('error', 'Kunci jawaban pilihan ganda harus dipilih (A, B, C, atau D).');
                $this->redirect('guru/questions');
                return;
            }
            $correctAnswer = strtoupper($correctAnswer);
        } elseif ($questionType === 'true_false') {
            $optionA = null;
            $optionB = null;
            $optionC = null;
            $optionD = null;
            if (!in_array(strtoupper($correctAnswer), ['TRUE', 'FALSE'], true)) {
                Session::setFlash('error', 'Kunci jawaban Benar/Salah harus dipilih (TRUE atau FALSE).');
                $this->redirect('guru/questions');
                return;
            }
            $correctAnswer = strtoupper($correctAnswer);
        } else {
            $optionA = null;
            $optionB = null;
            $optionC = null;
            $optionD = null;
        }

        try {
            $this->questionModel->update($questionId, [
                'subject_id' => $subjectId,
                'question_text' => $questionText,
                'question_type' => $questionType,
                'option_a' => $optionA,
                'option_b' => $optionB,
                'option_c' => $optionC,
                'option_d' => $optionD,
                'correct_answer' => $correctAnswer,
                'score' => $score,
                'difficulty' => in_array($difficulty, ['easy', 'medium', 'hard'], true) ? $difficulty : 'medium'
            ]);

            Session::setFlash('success', 'Soal ujian berhasil diperbarui!');
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal memperbarui soal: ' . $e->getMessage());
        }

        $this->redirect('guru/questions');
    }

    /**
     * Delete question (with ownership check)
     */
    public function delete(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('guru/questions');
            return;
        }

        try {
            $teacher = $this->getCurrentTeacher();
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            $this->redirect('guru/questions');
            return;
        }

        $teacherId = (int)$teacher['id'];
        $questionId = (int)$this->request->input('id');

        if (!$this->questionModel->isOwnedBy($questionId, $teacherId)) {
            Session::setFlash('error', 'Akses ditolak. Anda tidak berhak menghapus soal ini.');
            $this->redirect('guru/questions');
            return;
        }

        try {
            $this->questionModel->delete($questionId);
            Session::setFlash('success', 'Soal ujian berhasil dihapus dari Bank Soal.');
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal menghapus soal: ' . $e->getMessage());
        }

        $this->redirect('guru/questions');
    }

    /**
     * Preview single question details via AJAX/JSON or page
     */
    public function preview(): void
    {
        try {
            $teacher = $this->getCurrentTeacher();
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 403);
            return;
        }

        $teacherId = (int)$teacher['id'];
        $questionId = (int)$this->request->input('id');

        $question = $this->questionModel->findWithSubjectAndTeacher($questionId, $teacherId);

        if (!$question) {
            $this->json(['status' => 'error', 'message' => 'Soal tidak ditemukan atau Anda tidak memiliki akses.'], 404);
            return;
        }

        $this->json(['status' => 'success', 'data' => $question]);
    }
}
