-- ============================================================
-- SMS - Phase 12 Migration
-- Adds: timetables, library, transport, preferences
-- ============================================================

USE `sms-db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. TIMETABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS `timetable_periods` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `name`             VARCHAR(60) NOT NULL,
  `sequence`         TINYINT UNSIGNED NOT NULL,
  `start_time`       TIME NOT NULL,
  `end_time`         TIME NOT NULL,
  `is_break`         TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_period` (`academic_year_id`,`sequence`),
  CONSTRAINT `fk_period_ay` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `timetable_entries` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `term_id`          INT UNSIGNED NOT NULL,
  `grade_id`         INT UNSIGNED NOT NULL,
  `section_id`       INT UNSIGNED NOT NULL,
  `subject_id`       INT UNSIGNED NOT NULL,
  `teacher_id`       BIGINT UNSIGNED DEFAULT NULL,
  `period_id`        INT UNSIGNED NOT NULL,
  `day_of_week`      TINYINT UNSIGNED NOT NULL COMMENT '1=Mon,7=Sun',
  `room`             VARCHAR(30) DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tt_slot` (`academic_year_id`,`term_id`,`section_id`,`period_id`,`day_of_week`),
  KEY `idx_tt_teacher` (`teacher_id`,`day_of_week`,`period_id`),
  CONSTRAINT `fk_tt_ay`      FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_term`    FOREIGN KEY (`term_id`)          REFERENCES `terms`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_grade`   FOREIGN KEY (`grade_id`)         REFERENCES `grades`(`id`),
  CONSTRAINT `fk_tt_section` FOREIGN KEY (`section_id`)       REFERENCES `sections`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_subject` FOREIGN KEY (`subject_id`)       REFERENCES `subjects`(`id`),
  CONSTRAINT `fk_tt_teacher` FOREIGN KEY (`teacher_id`)       REFERENCES `teachers`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tt_period`  FOREIGN KEY (`period_id`)        REFERENCES `timetable_periods`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. LIBRARY
-- ============================================================

CREATE TABLE IF NOT EXISTS `library_categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80) NOT NULL,
  `code`       VARCHAR(20) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lc_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_books` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `isbn`          VARCHAR(30) DEFAULT NULL,
  `title`         VARCHAR(255) NOT NULL,
  `author`        VARCHAR(200) DEFAULT NULL,
  `publisher`     VARCHAR(200) DEFAULT NULL,
  `category_id`   INT UNSIGNED DEFAULT NULL,
  `edition`       VARCHAR(60) DEFAULT NULL,
  `year`          SMALLINT UNSIGNED DEFAULT NULL,
  `total_copies`  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `available_copies` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `shelf`         VARCHAR(40) DEFAULT NULL,
  `status`        ENUM('active','archived') NOT NULL DEFAULT 'active',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_book_title` (`title`),
  KEY `idx_book_isbn` (`isbn`),
  KEY `idx_book_category` (`category_id`),
  CONSTRAINT `fk_book_cat` FOREIGN KEY (`category_id`) REFERENCES `library_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_issues` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `book_id`       BIGINT UNSIGNED NOT NULL,
  `student_id`    BIGINT UNSIGNED DEFAULT NULL,
  `teacher_id`    BIGINT UNSIGNED DEFAULT NULL,
  `issued_by`     BIGINT UNSIGNED DEFAULT NULL,
  `issue_date`    DATE NOT NULL,
  `due_date`      DATE NOT NULL,
  `return_date`   DATE DEFAULT NULL,
  `fine_amount`   DECIMAL(8,2) NOT NULL DEFAULT 0,
  `fine_paid`     TINYINT(1) NOT NULL DEFAULT 0,
  `status`        ENUM('issued','returned','overdue','lost') NOT NULL DEFAULT 'issued',
  `notes`         VARCHAR(255) DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_issue_book` (`book_id`),
  KEY `idx_issue_student` (`student_id`),
  KEY `idx_issue_status` (`status`),
  KEY `idx_issue_due` (`due_date`),
  CONSTRAINT `fk_issue_book`    FOREIGN KEY (`book_id`)    REFERENCES `library_books`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_issue_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_issue_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_issue_user`    FOREIGN KEY (`issued_by`)  REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. TRANSPORT
-- ============================================================

CREATE TABLE IF NOT EXISTS `transport_routes` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(120) NOT NULL,
  `code`          VARCHAR(30) NOT NULL,
  `description`   VARCHAR(255) DEFAULT NULL,
  `driver_name`   VARCHAR(120) DEFAULT NULL,
  `driver_phone`  VARCHAR(30) DEFAULT NULL,
  `vehicle_no`    VARCHAR(30) DEFAULT NULL,
  `capacity`      SMALLINT UNSIGNED NOT NULL DEFAULT 40,
  `fee`           DECIMAL(8,2) NOT NULL DEFAULT 0,
  `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_route_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transport_stops` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `route_id`    INT UNSIGNED NOT NULL,
  `name`        VARCHAR(120) NOT NULL,
  `sequence`    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `pickup_time` TIME DEFAULT NULL,
  `drop_time`   TIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_stop_route` (`route_id`),
  CONSTRAINT `fk_stop_route` FOREIGN KEY (`route_id`) REFERENCES `transport_routes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transport_assignments` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id`  BIGINT UNSIGNED NOT NULL,
  `route_id`    INT UNSIGNED NOT NULL,
  `stop_id`     INT UNSIGNED DEFAULT NULL,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `status`      ENUM('active','cancelled') NOT NULL DEFAULT 'active',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_transport` (`student_id`,`academic_year_id`),
  KEY `idx_trans_route` (`route_id`),
  CONSTRAINT `fk_trans_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trans_route`   FOREIGN KEY (`route_id`)   REFERENCES `transport_routes`(`id`),
  CONSTRAINT `fk_trans_stop`    FOREIGN KEY (`stop_id`)    REFERENCES `transport_stops`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trans_ay`      FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. USER PREFERENCES
-- ============================================================

CREATE TABLE IF NOT EXISTS `user_preferences` (
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `pref_key`    VARCHAR(60) NOT NULL,
  `pref_value`  VARCHAR(255) DEFAULT NULL,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`pref_key`),
  CONSTRAINT `fk_pref_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. SEED DATA
-- ============================================================

INSERT IGNORE INTO `library_categories` (`name`,`code`) VALUES
('Mathematics','MATH'),
('Science','SCI'),
('Language','LANG'),
('Social Studies','SOC'),
('Literature','LIT'),
('Reference','REF'),
('Technology','TECH'),
('Other','OTH');

SET FOREIGN_KEY_CHECKS = 1;

-- Migration complete
SELECT '✔ Phase 12 migration complete' AS status;