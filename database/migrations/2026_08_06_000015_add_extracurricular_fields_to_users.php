<?php

use App\Config\Migration;

class AddExtracurricularFieldsToUsers extends Migration {

    public function up() {
        if ($this->tableExists('users')) {
            if (!$this->columnExists('users', 'rombel')) {
                $this->execute("ALTER TABLE users ADD COLUMN rombel VARCHAR(20) DEFAULT NULL AFTER jenis_kelamin");
                echo "Added column: users.rombel\n";
            }
            if (!$this->columnExists('users', 'angkatan_masuk')) {
                $this->execute("ALTER TABLE users ADD COLUMN angkatan_masuk VARCHAR(50) DEFAULT NULL AFTER rombel");
                echo "Added column: users.angkatan_masuk\n";
            }
            if (!$this->columnExists('users', 'bulan_bergabung')) {
                $this->execute("ALTER TABLE users ADD COLUMN bulan_bergabung VARCHAR(30) DEFAULT NULL AFTER angkatan_masuk");
                echo "Added column: users.bulan_bergabung\n";
            }
        }
        echo "=== Migration 15 complete ===\n";
    }

    public function down() {
        if ($this->tableExists('users')) {
            if ($this->columnExists('users', 'rombel')) {
                $this->execute("ALTER TABLE users DROP COLUMN rombel");
            }
            if ($this->columnExists('users', 'angkatan_masuk')) {
                $this->execute("ALTER TABLE users DROP COLUMN angkatan_masuk");
            }
            if ($this->columnExists('users', 'bulan_bergabung')) {
                $this->execute("ALTER TABLE users DROP COLUMN bulan_bergabung");
            }
        }
        echo "=== Migration 15 rollback complete ===\n";
    }
}
