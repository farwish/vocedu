<?php

namespace App\Repositories;

use App\Basics\BaseRepository;
use App\Enums\PatternEnum;
use App\Models\Member;
use App\Models\PractiseRecord;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;

class PractiseRecordRepository extends BaseRepository
{
    /** @var QuestionRepository $questionRepository  */
    protected $questionRepository;

    public function __construct(PractiseRecord $practiseRecord, DatabaseManager $dbManager)
    {
        parent::__construct($practiseRecord, $dbManager);

        $this->questionRepository = app(QuestionRepository::class);
    }

    /**
     * Practise Record Save
     *
     * @param Member $member
     * @param int $categoryId
     * @param int $questionId
     * @param string|null $replyAnswer
     *
     * @return bool
     */
    public function recordSave(Member $member, int $categoryId, int $questionId, ?string $replyAnswer = null): bool
    {
        // Check practise_record existence
        $model = $this->specificRecordInfo($member, $categoryId, $questionId);

        if ($model) {
            // Update

            $model->setAttribute('reply_answer', !is_null($replyAnswer) ? $replyAnswer : '');
            $model->setAttribute('updated_at', now());

            return $model->save();

        } else {
            // Create

            $model = $this->newModel();

            // required field
            $model->setAttribute('member_id', $member->getAttribute('id'));
            $model->setAttribute('category_id', $categoryId);
            $model->setAttribute('question_id', $questionId);

            // only optional field for database.
            if ($replyAnswer) {
                $model->setAttribute('reply_answer', $replyAnswer);
            }

            return $model->save();
        }

        return true;
    }

    /**
     * Record info
     *
     * @param Member $member
     *
     * @return array
     */
    public function recordInfo(Member $member)
    {
        $lastPractiseRecord = $this->lastPractiseRecord($member);

        // No practise record.
        if (! $lastPractiseRecord) {
            return [];
        }

        $category = $lastPractiseRecord->category()->first();
        $question = $lastPractiseRecord->question()->first();
        $chapter = $question->chapter()->first();

        // SELECT count(id) FROM questions where
        // category_id = {$category} AND sort >= {$questionSort} AND id < {$questionId}
        // order by sort DESC, id ASC;
        $questionsIds = $this->questionRepository
            ->newQuery()
            ->where('category_id', '=', $category->getAttribute('id'))
            ->where('sort', '>=', $question->getAttribute('sort'))
            ->orderBy('sort', 'DESC')
            ->orderBy('id', 'ASC')
            ->pluck('id')
            ->toArray()
        ;

        $previousQuestionCount = array_search($question->getAttribute('id'), $questionsIds);

        return [
            'categoryId' => $category->getAttribute('id'),
            'categoryName' => $category->getAttribute('name'),
            'chapterName' => $chapter->getAttribute('name'),
            'questionSerialNumber' => $previousQuestionCount + 1, // 第几题
        ];
    }

    /**
     * Wrongs count / collects count / notes count
     *
     * @param Member $member
     * @param int|null $categoryId
     * @param PractiseRecord|null $lastPractiseRecord
     *
     * @return int
     */
    public function recordSummary(Member $member, ?int $categoryId = null)
    {
        $lastPractiseRecord = $this->lastPractiseRecord($member);

        // No practise record.
        if (! $lastPractiseRecord) {
            return 0;
        }

        if (! $categoryId) {
            $category = $lastPractiseRecord->category()->first();
            $categoryId = $category->getAttribute('id');
        }

        $summary['wrongsCount'] = $this->newQuery()

            // relate questions and patterns
            ->leftJoin('questions', function ($join) {
                $join->on('practise_records.question_id', '=', 'questions.id');
            })
            ->leftJoin('patterns', function ($join) {
                $join->on('questions.pattern_id', '=', 'patterns.id');
            })

            // specify owner
            ->where('practise_records.member_id', $member->getAttribute('id'))
            ->where('practise_records.category_id', $categoryId)

            // only objective question
            ->where('patterns.type', '=', PatternEnum::TYPE_OBJECTIVE)

            // reply_answer not empty
            ->where('practise_records.reply_answer', '!=', '')
            ->whereNotNull('practise_records.reply_answer')

            // reply_answer not right
            ->whereColumn('practise_records.reply_answer', '!=', 'questions.right_answer')

            // ->toSql();
            ->count('practise_records.id');

        $summary['collectsCount'] = app(PractiseCollectRepository::class)->practiseCollectsCount($member, $categoryId);
        $summary['notesCount'] = app(PractiseNoteRepository::class)->practiseNotesCount($member, $categoryId);

        return $summary;
    }

    public function currentSubjectInfo(Member $member)
    {
        $lastPractiseRecord = $this->lastPractiseRecord($member);

        if (! $lastPractiseRecord) {
            return [];
        }

        $category = $lastPractiseRecord->category()->first();

        // check member's category.
        $categoryMember = app(CategoryRepository::class)->categoryMember($category, $member);

        return [
            'categoryName' => $category->getAttribute('name'),
            'questionsCount' => (string)$category->questions()->count(),
            'openStatus' => $categoryMember ? '已开通' : '试用',
            'expiredAt' => $categoryMember ? $categoryMember->pivot->expired_at : '-',
        ];
    }

    /**
     * To query unique record existence
     *
     * @param Member $member
     * @param int $categoryId
     * @param int|null $questionId
     *
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public function specificRecordInfo(Member $member, int $categoryId, ?int $questionId = null)
    {
        $qb = $this->newQuery()
            ->where('member_id', $member->getAttribute('id'))
            ->where('category_id', $categoryId);

        if ($questionId) {
            $qb->where('question_id', $questionId);
        }

        return $qb
            ->orderBy('updated_at', 'DESC')
            ->first();
    }

    /**
     * question_ids that have records with answer exist.
     *
     * @param Member $member
     * @param int $categoryId
     * @param array $questionIds
     *
     * @return array
     */
    public function hasRecordsAndAnswerQuestionIds(Member $member, int $categoryId, array $questionIds): array
    {
        return $this->newQuery()
            ->where('member_id', $member->getAttribute('id'))
            ->where('category_id', $categoryId)
            ->whereIn('question_id', $questionIds)
            ->where('reply_answer', '!=', '')
            ->whereNotNull('reply_answer')
            ->pluck('question_id')
            ->unique()
            ->toArray()
        ;
    }

    /**
     * Last record row
     *
     * @param Member $member
     *
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    protected function lastPractiseRecord(Member $member)
    {
        return $this->newQuery()
            ->where('member_id', $member->getAttribute('id'))
            ->orderBy('updated_at', 'DESC')
            ->first();
    }
}
