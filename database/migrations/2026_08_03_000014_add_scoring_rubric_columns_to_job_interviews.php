<?php

use App\Config\Migration;

class AddScoringRubricColumnsToJobInterviews extends Migration {
    public function up() {
        $cols = [
            ['score_technical', 'INT NULL'],
            ['score_problem_solving', 'INT NULL'],
            ['score_communication', 'INT NULL'],
            ['score_cultural_fit', 'INT NULL'],
        ];

        foreach ($cols as [$col, $type]) {
            if ($this->tableExists('job_interviews') && !$this->columnExists('job_interviews', $col)) {
                $this->execute("ALTER TABLE job_interviews ADD COLUMN $col $type");
                echo "Added column: job_interviews.$col\n";
            }
        }
    }

    public function down() {
        $cols = ['score_technical', 'score_problem_solving', 'score_communication', 'score_cultural_fit'];
        foreach ($cols as $col) {
            if ($this->tableExists('job_interviews') && $this->columnExists('job_interviews', $col)) {
                $this->execute("ALTER TABLE job_interviews DROP COLUMN $col");
                echo "Dropped column: job_interviews.$col\n";
            }
        }
    }
}
