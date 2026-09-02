<?php

/**
 * Crea (o recrea) la base de datos SQLite de Yii Bank con su esquema.
 *
 * Uso: php protected/data/init.db.php
 */

$dbPath = __DIR__ . '/bank.db';

if (file_exists($dbPath)) {
    unlink($dbPath);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE accounts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        account_number TEXT NOT NULL UNIQUE,
        account_type TEXT NOT NULL,
        balance INTEGER NOT NULL DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'active',
        currency TEXT NOT NULL DEFAULT 'EUR',
        withdrawal_count INTEGER NOT NULL DEFAULT 0,
        withdrawal_reset_date TIMESTAMP,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
");

$pdo->exec('CREATE INDEX idx_accounts_account_number ON accounts(account_number)');
$pdo->exec('CREATE INDEX idx_accounts_status ON accounts(status)');
$pdo->exec('CREATE INDEX idx_accounts_user_id ON accounts(user_id)');

$pdo->exec("
    CREATE TABLE transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        from_account_id INTEGER,
        to_account_id INTEGER,
        amount INTEGER NOT NULL,
        transaction_type TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'pending',
        idempotency_key TEXT UNIQUE,
        description TEXT,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_account_id) REFERENCES accounts(id),
        FOREIGN KEY (to_account_id) REFERENCES accounts(id)
    )
");

$pdo->exec('CREATE INDEX idx_transactions_status ON transactions(status)');
$pdo->exec('CREATE INDEX idx_transactions_from_account ON transactions(from_account_id)');
$pdo->exec('CREATE INDEX idx_transactions_to_account ON transactions(to_account_id)');

echo "Base de datos creada en: {$dbPath}\n";
