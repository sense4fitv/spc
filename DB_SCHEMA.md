-- ==========================================
-- S.P.O.R. - Database Schema v2
-- ==========================================

-- 1. USERS & AUTH
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL, -- Email-ul va fi folosit ca username
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'director', 'manager', 'executant', 'auditor') NOT NULL,
    `role_level` TINYINT UNSIGNED NOT NULL, -- 100=Admin, 80=Director, 50=Manager, 20=Executant, 10=Auditor
    `region_id` INT UNSIGNED NULL, -- NULL = super user (acces la toate regiunile)
    `first_name` VARCHAR(100),
    `last_name` VARCHAR(100),
    `active` TINYINT(1) DEFAULT 1,
    `avatar_url` VARCHAR(255) NULL, -- Pentru UI-ul modern
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`region_id`) REFERENCES `regions`(`id`) ON DELETE SET NULL,
    INDEX idx_role_level (`role_level`),
    INDEX idx_region_id (`region_id`)
);

-- 2. ORGANIZATIONAL HIERARCHY
CREATE TABLE `regions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `manager_id` INT UNSIGNED NULL, -- Director Regional
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE TABLE `contracts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `region_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `contract_number` VARCHAR(50) NULL, -- Ex: #CN-2024-001
    `client_name` VARCHAR(150),
    `manager_id` INT UNSIGNED NULL, -- Manager Contract
    `start_date` DATE,
    `end_date` DATE,
    `progress_percentage` TINYINT DEFAULT 0, -- 0-100
    `status` ENUM('planning', 'active', 'on_hold', 'completed') DEFAULT 'planning',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`region_id`) REFERENCES `regions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE TABLE `subdivisions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `contract_id` INT UNSIGNED NOT NULL,
    `code` VARCHAR(20) NOT NULL, -- Ex: SUB-01
    `name` VARCHAR(150) NOT NULL,
    `details` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`contract_id`) REFERENCES `contracts`(`id`) ON DELETE CASCADE
);

CREATE TABLE `departments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE, -- Ex: Financiar, Operational
    `color_code` VARCHAR(7) DEFAULT '#808080'
);

-- Relatia many-to-many: Users <-> Departments
-- Daca un user nu are nicio asociere = super user (vede toate departamentele)
CREATE TABLE `user_departments` (
    `user_id` INT UNSIGNED NOT NULL,
    `department_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `department_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
);

-- 3. TASKS CORE
CREATE TABLE `tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subdivision_id` INT UNSIGNED NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `status` ENUM('new', 'in_progress', 'blocked', 'review', 'completed') DEFAULT 'new',
    `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    `deadline` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`subdivision_id`) REFERENCES `subdivisions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
);

CREATE TABLE `task_assignees` (
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`task_id`, `user_id`),
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `task_departments` (
    `task_id` INT UNSIGNED NOT NULL,
    `department_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`task_id`, `department_id`),
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
);

-- 4. TASK RESOURCES & LOGS
CREATE TABLE `task_comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `task_files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `uploaded_by` INT UNSIGNED NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `filepath` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50),
    `file_size` INT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)
);

CREATE TABLE `task_activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `old_value` TEXT NULL,
    `new_value` TEXT NULL,
    `description` VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
);

-- 5. SYSTEM & SECURITY
CREATE TABLE `user_logins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NOT NULL,
    `login_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- TABELA NOUA: NOTIFICARI
CREATE TABLE `notifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL, -- Destinatarul
    `type` ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    `title` VARCHAR(100) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) NULL, -- Link catre task/contract (ex: /tasks/view/102)
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- INDEX OPTIMIZATION
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_deadline ON tasks(deadline);
CREATE INDEX idx_notifications_user_unread ON notifications(user_id, is_read);