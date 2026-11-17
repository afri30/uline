<?php

declare(strict_types=1);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

require_once dirname(__DIR__) . '/app/Helpers/functions.php';

use App\Core\Database;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\User;

echo "========================================================\n";
echo "  TESTING EXAM GRADING & RESULTS ANALYTICS SYSTEM\n";
echo "========================================================\n\n";

$db = Database::getConnection();
$sessionModel = new ExamSession();
$examModel = new Exam();
$answerModel = new Answer();
$questionModel = new Question();
$teacherModel = new Teacher();
$studentModel = new Student();
$userModel = new User();

$totalTests = 0;
$passedTests = 0;

function assertTest(bool $condition, string $testName): void {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName}\n";
    }
}

// -------------------------------------------------------------
// Test Setup: Retrieve Guru 1 (Budi), Exam 1, and Students
// -------------------------------------------------------------
echo "1. PREPARING TEST DATA\n";
$teacherBudi = $teacherModel->findByUserId(2); // Guru Budi
assertTest($teacherBudi !== null, "Guru Budi (Teacher ID: " . ($teacherBudi['id'] ?? 'null') . ") exists");

$exam = $examModel->find(1); // Exam 1
assertTest($exam !== null, "Exam 1 exists");

$student1 = $studentModel->findByUserId(4); // Siswa Ahmad
$student2 = $studentModel->findByUserId(5); // Siswa Dewi
assertTest($student1 !== null && $student2 !== null, "Students Ahmad & Dewi exist");

// Ensure sessions exist and have answers for testing
$session1 = $sessionModel->getOrCreateSession((int)$exam['id'], (int)$student1['id'], (int)$exam['duration']);
$session2 = $sessionModel->getOrCreateSession((int)$exam['id'], (int)$student2['id'], (int)$exam['duration']);

assertTest(!empty($session1['id']), "Session for Student 1 is created / retrieved (ID: {$session1['id']})");
assertTest(!empty($session2['id']), "Session for Student 2 is created / retrieved (ID: {$session2['id']})");

// Get questions in Exam 1
$questions = $examModel->getExamQuestions((int)$exam['id']);
assertTest(count($questions) >= 2, "Exam 1 has at least 2 questions (Count: " . count($questions) . ")");

// Student 1 answers: all correct
foreach ($questions as $q) {
    $answerModel->saveOrUpdateAnswer(
        (int)$session1['id'],
        (int)$q['id'],
        $q['correct_answer'],
        $q
    );
}
$sessionModel->finishSession((int)$session1['id'], 'finished');

// Student 2 answers: Q1 wrong, rest correct
foreach ($questions as $idx => $q) {
    if ($idx === 0) {
        $wrongOpt = (strtoupper($q['correct_answer']) === 'A') ? 'B' : 'A';
        $answerModel->saveOrUpdateAnswer(
            (int)$session2['id'],
            (int)$q['id'],
            $wrongOpt,
            $q
        );
    } else {
        $answerModel->saveOrUpdateAnswer(
            (int)$session2['id'],
            (int)$q['id'],
            $q['correct_answer'],
            $q
        );
    }
}
$sessionModel->finishSession((int)$session2['id'], 'finished');

// -------------------------------------------------------------
echo "\n2. TESTING TEACHER EXAM SUMMARY AGGREGATIONS\n";
// -------------------------------------------------------------
$teacherExams = $sessionModel->getTeacherExamsWithResultSummary((int)$teacherBudi['id']);
assertTest(count($teacherExams) > 0, "getTeacherExamsWithResultSummary returns exams for Guru Budi");

$examSummary = null;
foreach ($teacherExams as $te) {
    if ((int)$te['exam_id'] === 1) {
        $examSummary = $te;
        break;
    }
}
assertTest($examSummary !== null, "Exam 1 found in teacher summary");
assertTest((int)$examSummary['total_participants'] >= 2, "Total participants for Exam 1 is at least 2 (Actual: {$examSummary['total_participants']})");
assertTest((int)$examSummary['finished_participants'] >= 2, "Finished participants is at least 2 (Actual: {$examSummary['finished_participants']})");
assertTest($examSummary['avg_grade'] > 0, "Average grade is calculated (> 0, Actual: {$examSummary['avg_grade']})");
assertTest($examSummary['highest_grade'] == 100, "Highest grade is 100 for perfect score student (Actual: {$examSummary['highest_grade']})");

// -------------------------------------------------------------
echo "\n3. TESTING EXAM STATS SUMMARY\n";
// -------------------------------------------------------------
$stats = $sessionModel->getExamStatsSummary(1);
assertTest($stats['total_participants'] >= 2, "ExamStats: total_participants >= 2");
assertTest($stats['finished_count'] >= 2, "ExamStats: finished_count >= 2");
assertTest($stats['highest_grade'] >= $stats['lowest_grade'], "ExamStats: highest_grade ({$stats['highest_grade']}) >= lowest_grade ({$stats['lowest_grade']})");
assertTest(isset($stats['pass_count']) && isset($stats['pass_percentage']), "ExamStats: pass_count & pass_percentage present");

// -------------------------------------------------------------
echo "\n4. TESTING PARTICIPANT RANKINGS & GRADING BREAKDOWN\n";
// -------------------------------------------------------------
$participants = $sessionModel->getExamParticipants(1, [], 'rank_asc');
assertTest(count($participants) >= 2, "getExamParticipants returns at least 2 participants");

// Check Ranking 1
$rank1 = $participants[0];
assertTest($rank1['rank'] === 1, "Rank #1 assigned to top scorer");
assertTest($rank1['grade'] == 100, "Rank #1 has 100 grade (Student: {$rank1['student_name']})");
assertTest($rank1['correct_count'] == count($questions), "Rank #1 has all correct answers");
assertTest($rank1['incorrect_count'] == 0, "Rank #1 has 0 incorrect answers");
assertTest($rank1['unanswered_count'] == 0, "Rank #1 has 0 unanswered questions");

// Check Ranking 2
$rank2 = $participants[1];
assertTest($rank2['rank'] === 2, "Rank #2 assigned to second participant");
assertTest($rank2['grade'] < 100, "Rank #2 has grade < 100 (Student: {$rank2['student_name']}, Grade: {$rank2['grade']})");
assertTest($rank2['correct_count'] == (count($questions) - 1), "Rank #2 has " . (count($questions) - 1) . " correct answers");
assertTest($rank2['incorrect_count'] == 1, "Rank #2 has 1 incorrect answer");

// Test Sorting
$sortedDescName = $sessionModel->getExamParticipants(1, [], 'name_desc');
assertTest(count($sortedDescName) >= 2, "Participants can be sorted by name_desc");

// -------------------------------------------------------------
echo "\n5. TESTING STUDENT EXAM ANSWER BREAKDOWN\n";
// -------------------------------------------------------------
$breakdown = $sessionModel->getStudentExamAnswerBreakdown(1, (int)$student1['id']);
assertTest($breakdown !== null, "getStudentExamAnswerBreakdown returns breakdown data for Student 1");
assertTest(isset($breakdown['session']) && isset($breakdown['summary']) && isset($breakdown['questions']), "Breakdown structure has session, summary, and questions");
assertTest($breakdown['summary']['grade'] == 100, "Breakdown summary grade is 100");
assertTest(count($breakdown['questions']) == count($questions), "Breakdown includes all question cards");
assertTest($breakdown['questions'][0]['is_correct'] === true, "Question 1 marked is_correct = true");

$breakdown2 = $sessionModel->getStudentExamAnswerBreakdown(1, (int)$student2['id']);
assertTest($breakdown2 !== null, "getStudentExamAnswerBreakdown returns breakdown data for Student 2");
assertTest($breakdown2['questions'][0]['is_correct'] === false, "Question 1 for Student 2 marked is_correct = false");
assertTest($breakdown2['questions'][1]['is_correct'] === true, "Question 2 for Student 2 marked is_correct = true");

// -------------------------------------------------------------
echo "\n6. TESTING STUDENT RESULTS HISTORY LIST\n";
// -------------------------------------------------------------
$historyStudent1 = $sessionModel->getStudentHistory((int)$student1['id']);
assertTest(count($historyStudent1) >= 1, "Student 1 has at least 1 exam in history");
assertTest($historyStudent1[0]['grade'] == 100, "Student 1 history record shows 100 grade");
assertTest($historyStudent1[0]['correct_count'] == count($questions), "Student 1 history record shows all correct answers (Actual: {$historyStudent1[0]['correct_count']})");

// -------------------------------------------------------------
echo "\n7. TESTING TEACHER ACCESS CONTROL & ISOLATION\n";
// -------------------------------------------------------------
$teacherSiti = $teacherModel->findByUserId(3); // Guru Siti (Teacher ID 2)
assertTest($teacherSiti !== null, "Guru Siti (Teacher ID: {$teacherSiti['id']}) exists");

$sitiExams = $sessionModel->getTeacherExamsWithResultSummary((int)$teacherSiti['id']);
// Exam 1 belongs to Guru Budi, so it should NOT be in Siti's exams list
$sitiHasExam1 = false;
foreach ($sitiExams as $se) {
    if ((int)$se['exam_id'] === 1) {
        $sitiHasExam1 = true;
        break;
    }
}
assertTest(!$sitiHasExam1, "Teacher isolation: Guru Siti cannot see Guru Budi's exam in results summary");

// -------------------------------------------------------------
echo "\n========================================================\n";
echo "  TEST SUMMARY: {$passedTests} / {$totalTests} PASSED\n";
echo "========================================================\n";

if ($passedTests === $totalTests) {
    echo "SUCCESS: ALL TESTS PASSED PROPERLY!\n";
    exit(0);
} else {
    echo "ERROR: SOME TESTS FAILED.\n";
    exit(1);
}
