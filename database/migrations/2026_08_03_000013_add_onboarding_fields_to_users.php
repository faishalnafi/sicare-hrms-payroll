<?php

use App\Config\Migration;

class AddOnboardingFieldsToUsers extends Migration {
    public function up() {
        $cols = [
            ['ktp_file', 'VARCHAR(255) NULL'],
            ['kk_file', 'VARCHAR(255) NULL'],
            ['npwp_file', 'VARCHAR(255) NULL'],
            ['bank_book_file', 'VARCHAR(255) NULL'],
            ['emergency_contact_name', 'VARCHAR(100) NULL'],
            ['emergency_contact_phone', 'VARCHAR(20) NULL'],
            ['emergency_contact_relation', 'VARCHAR(50) NULL'],
            ['onboarding_status', "VARCHAR(30) DEFAULT 'not_started'"],
            ['employee_id', 'VARCHAR(50) NULL'],
        ];

        foreach ($cols as [$col, $type]) {
            if ($this->tableExists('users') && !$this->columnExists('users', $col)) {
                $this->execute("ALTER TABLE users ADD COLUMN $col $type");
                echo "Added column: users.$col\n";
            }
        }
    }

    public function down() {
        $cols = [
            'ktp_file', 'kk_file', 'npwp_file', 'bank_book_file',
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
            'onboarding_status', 'employee_id'
        ];

        foreach ($cols as $col) {
            if ($this->tableExists('users') && $this->columnExists('users', $col)) {
                $this->execute("ALTER TABLE users DROP COLUMN $col");
                echo "Dropped column: users.$col\n";
            }
        }
    }
}
