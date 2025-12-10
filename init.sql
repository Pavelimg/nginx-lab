-- Создание таблицы для участников конференции
CREATE TABLE IF NOT EXISTS conference_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    birth_year INT NOT NULL,
    section VARCHAR(100) NOT NULL,
    participation_type VARCHAR(50) NOT NULL,
    needs_certificate BOOLEAN DEFAULT FALSE,
    newsletter_subscription BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание индексов для оптимизации
CREATE INDEX idx_email ON conference_participants(email);
CREATE INDEX idx_created_at ON conference_participants(created_at);
CREATE INDEX idx_birth_year ON conference_participants(birth_year);