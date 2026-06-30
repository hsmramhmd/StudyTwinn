-- ============================================================
--  StudyTwin — Full Database Schema + Sample Data
--  Compatible: MySQL 5.7+ / MariaDB 10.4+
--  Charset:    utf8mb4
--
--  Tables
--    1. users             — all accounts (student / tutor / admin)
--    2. tutors            — tutor profile & pricing
--    3. availability      — tutor weekly time slots
--    4. bookings          — session bookings
--    5. reviews           — student ratings/comments
--    6. messages          — chat between users
--    7. notifications     — per-user alert feed
--    8. payments          — booking payment records
--    9. tutor_top_rank    — manually curated top-3 leaderboard
--   10. quest_completions — (optional) persisted quest claims
--
--  Views
--    • leaderboard        — student XP ranking
--    • teammate_map       — students who share tutors
-- ============================================================

SET SQL_MODE   = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone  = "+00:00";
START TRANSACTION;

/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
--  DROP + CREATE DATABASE
-- ============================================================
DROP DATABASE IF EXISTS `studytwin`;
CREATE DATABASE `studytwin`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE `studytwin`;

-- ============================================================
--  1. USERS
--     Stores every account. Role drives which dashboard loads.
-- ============================================================
CREATE TABLE `users` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `full_name`     VARCHAR(100) NOT NULL,
    `email`         VARCHAR(100) NOT NULL,
    `password`      VARCHAR(255) NOT NULL,   -- ⚠ plain-text for dev; hash in production
    `role`          ENUM('student','tutor','admin') DEFAULT 'student',
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `avatar_color`  VARCHAR(30)  DEFAULT 'orange',  -- orange|teal|purple|rose|green|midnight|gold|sky
    `avatar_animal` VARCHAR(30)  DEFAULT 'fox',     -- fox|cat|bear|rabbit|owl|penguin
    `avatar_outfit` VARCHAR(30)  DEFAULT 'none',    -- none|graduation|chef|ninja|wizard|astronaut|knight|crown
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
--  2. TUTORS
--     One row per tutor. user_id links back to users table.
-- ============================================================
CREATE TABLE `tutors` (
    `id`             INT(11)        NOT NULL AUTO_INCREMENT,
    `user_id`        INT(11)        NOT NULL,
    `subject`        VARCHAR(100)   NOT NULL,
    `expertise`      VARCHAR(100)   DEFAULT NULL,
    `bio`            TEXT           DEFAULT NULL,
    `rating`         DECIMAL(2,1)   DEFAULT 4.5,
    `price_per_hour` DECIMAL(10,2)  DEFAULT 0.00,
    PRIMARY KEY (`id`),
    KEY `fk_tutors_user` (`user_id`),
    CONSTRAINT `tutors_ibfk_1`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
--  3. AVAILABILITY
--     Weekly recurring time slots set by tutors.
--     Overlap validation is enforced in availability.php.
-- ============================================================
CREATE TABLE `availability` (
    `id`         INT(11) NOT NULL AUTO_INCREMENT,
    `tutor_id`   INT(11) NOT NULL,
    `day`        ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    `start_time` TIME    NOT NULL,
    `end_time`   TIME    NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_avail_tutor` (`tutor_id`),
    CONSTRAINT `availability_ibfk_1`
        FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
--  4. BOOKINGS
--     A student books a tutor for a specific date + subject.
--     Status flow: pending → confirmed → completed | cancelled
-- ============================================================
CREATE TABLE `bookings` (
    `id`           INT(11)      NOT NULL AUTO_INCREMENT,
    `student_id`   INT(11)      NOT NULL,
    `tutor_id`     INT(11)      NOT NULL,
    `subject`      VARCHAR(100) NOT NULL,
    `session_date` DATE         NOT NULL,
    `status`       ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_book_student` (`student_id`),
    KEY `fk_book_tutor`   (`tutor_id`),
    CONSTRAINT `bookings_ibfk_1`
        FOREIGN KEY (`student_id`) REFERENCES `users`   (`id`) ON DELETE CASCADE,
    CONSTRAINT `bookings_ibfk_2`
        FOREIGN KEY (`tutor_id`)   REFERENCES `tutors`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
--  5. REVIEWS
--     Students rate tutors after a completed booking.
--     rating is 0.0–5.0 in 0.5 steps.
-- ============================================================
CREATE TABLE `reviews` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `student_id` INT(11)      NOT NULL,
    `tutor_id`   INT(11)      NOT NULL,
    `rating`     DECIMAL(2,1) NOT NULL,
    `comment`    TEXT         DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_rev_student` (`student_id`),
    KEY `fk_rev_tutor`   (`tutor_id`),
    CONSTRAINT `reviews_ibfk_1`
        FOREIGN KEY (`student_id`) REFERENCES `users`  (`id`) ON DELETE CASCADE,
    CONSTRAINT `reviews_ibfk_2`
        FOREIGN KEY (`tutor_id`)   REFERENCES `tutors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
--  6. MESSAGES
--     Direct messages between any two users (student ↔ tutor).
-- ============================================================
CREATE TABLE `messages` (
    `id`          INT(11)   NOT NULL AUTO_INCREMENT,
    `sender_id`   INT(11)   NOT NULL,
    `receiver_id` INT(11)   NOT NULL,
    `message`     TEXT      NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_read`     TINYINT(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `fk_msg_sender`   (`sender_id`),
    KEY `fk_msg_receiver` (`receiver_id`),
    CONSTRAINT `messages_ibfk_1`
        FOREIGN KEY (`sender_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `messages_ibfk_2`
        FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
--  7. NOTIFICATIONS
--     System alerts pushed to individual users.
-- ============================================================
CREATE TABLE `notifications` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)      NOT NULL,
    `title`      VARCHAR(255) DEFAULT NULL,
    `message`    TEXT         DEFAULT NULL,
    `is_read`    TINYINT(1)   DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_notif_user` (`user_id`),
    CONSTRAINT `notifications_ibfk_1`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
--  8. PAYMENTS
--     One payment record per booking once student pays.
-- ============================================================
CREATE TABLE `payments` (
    `id`             INT(11)       NOT NULL AUTO_INCREMENT,
    `booking_id`     INT(11)       NOT NULL,
    `student_id`     INT(11)       NOT NULL,
    `amount`         DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('card','online_banking','ewallet') NOT NULL,
    `status`         ENUM('paid','pending','failed') DEFAULT 'paid',
    `paid_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_pay_booking` (`booking_id`),
    KEY `fk_pay_student` (`student_id`),
    CONSTRAINT `payments_ibfk_1`
        FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `payments_ibfk_2`
        FOREIGN KEY (`student_id`) REFERENCES `users`    (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
--  9. TUTOR TOP RANK
--     Manually maintained top-ranked tutors shown on the
--     tutortoprank.php page. rank_position must be unique.
-- ============================================================
CREATE TABLE `tutor_top_rank` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `tutor_id`      INT(11)      NOT NULL,
    `rating`        DECIMAL(2,1) NOT NULL,
    `rank_position` INT(11)      NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rank_position` (`rank_position`),
    KEY `fk_toprank_tutor` (`tutor_id`),
    CONSTRAINT `tutor_top_rank_ibfk_1`
        FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 10. QUEST COMPLETIONS  (optional — referenced in quest.php)
--     Persists claimed quests so rewards are not given twice.
--     If you want quests to reset on every page load, skip this.
-- ============================================================
CREATE TABLE `quest_completions` (
    `id`          INT(11)     NOT NULL AUTO_INCREMENT,
    `user_id`     INT(11)     NOT NULL,
    `quest_id`    VARCHAR(60) NOT NULL,
    `claimed_at`  DATETIME    DEFAULT CURRENT_TIMESTAMP,
    `xp_awarded`  INT         DEFAULT 0,
    `pts_awarded` INT         DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_quest` (`user_id`, `quest_id`),
    CONSTRAINT `quest_completions_ibfk_1`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
--  VIEWS
-- ============================================================

-- leaderboard: student XP ranking
--   XP formula: (completed sessions × 50) + (reviews left × 10)
CREATE OR REPLACE VIEW `leaderboard` AS
SELECT
    u.id,
    u.full_name,
    COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.id END)                    AS completed_sessions,
    COUNT(DISTINCT r.id)                                                                AS review_count,
    COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.id END) * 50
        + COUNT(DISTINCT r.id) * 10                                                     AS xp
FROM users u
LEFT JOIN bookings b ON b.student_id = u.id
LEFT JOIN reviews  r ON r.student_id = u.id
WHERE u.role = 'student'
GROUP BY u.id, u.full_name;

-- teammate_map: pairs of students who share at least one tutor
CREATE OR REPLACE VIEW `teammate_map` AS
SELECT
    b1.student_id                                                                      AS main_student,
    b2.student_id                                                                      AS teammate_id,
    COUNT(DISTINCT b2.tutor_id)                                                        AS shared_tutors,
    COUNT(DISTINCT CASE WHEN b2.status = 'completed' THEN b2.id END)                  AS completed_sessions,
    COUNT(DISTINCT r.id)                                                               AS review_count,
    COUNT(DISTINCT CASE WHEN b2.status = 'completed' THEN b2.id END) * 50
        + COUNT(DISTINCT r.id) * 10                                                    AS xp
FROM bookings b1
JOIN  bookings b2 ON b1.tutor_id = b2.tutor_id AND b1.student_id <> b2.student_id
LEFT JOIN reviews r ON r.student_id = b2.student_id
GROUP BY b1.student_id, b2.student_id;

-- ============================================================
--  SAMPLE DATA
-- ============================================================

-- ── users ──────────────────────────────────────────────────
INSERT INTO `users` (`id`,`full_name`,`email`,`password`,`role`,`avatar_color`,`avatar_animal`,`avatar_outfit`) VALUES
(1,  'Nur Aina',                         'aina@test.com',                      '123456', 'tutor',   'orange', 'fox',    'none'),
(2,  'Ahmad Razak',                      'razak@test.com',                     '123456', 'tutor',   'orange', 'fox',    'none'),
(3,  'Siti Aminah',                      'siti@test.com',                      '123456', 'tutor',   'orange', 'fox',    'none'),
(4,  'ALYA BASIRAH ABDULLAH',            'student@test.com',                   '123456', 'student', 'purple', 'cat',    'graduation'),
(5,  'Admin User',                       'admin@test.com',                     '123456', 'admin',   'orange', 'fox',    'none'),
(6,  'NUR ATHIRAH FADHLIN BINTI MAT NAWI','2025237002@student.uitm.edu.my',   '12345',  'student', 'orange', 'fox',    'none'),
(7,  'Muhammad Fathi Ubaidi',            'fathi@test.com',                     '123456', 'tutor',   'orange', 'fox',    'none'),
(8,  'Muhammad Faiz Iskandar',           'faiz@test.com',                      '123456', 'tutor',   'orange', 'fox',    'none'),
(11, 'Nur Aisyah Ahmad',                 'aisyah.tutor@studytwin.com',         '123456', 'tutor',   'orange', 'fox',    'none'),
(12, 'Muhammad Hakim Zulkifli',          'hakim.tutor@studytwin.com',          '123456', 'tutor',   'orange', 'fox',    'none'),
(13, 'Siti Nur Iman',                    'iman.tutor@studytwin.com',           '123456', 'tutor',   'orange', 'fox',    'none'),
(14, 'Amirul Syafiq',                    'amirul.tutor@studytwin.com',         '123456', 'tutor',   'orange', 'fox',    'none'),
(15, 'Nur Athirah Sofea',                'athirah.tutor@studytwin.com',        '123456', 'tutor',   'orange', 'fox',    'none'),
(16, 'Muhammad Danish',                  'danish.tutor@studytwin.com',         '123456', 'tutor',   'orange', 'fox',    'none'),
(17, 'Aina Syuhada',                     'aina.tutor@studytwin.com',           '123456', 'tutor',   'orange', 'fox',    'none'),
(18, 'Farhan Iskandar',                  'farhan.tutor@studytwin.com',         '123456', 'tutor',   'orange', 'fox',    'none'),
(19, 'Siti Balqis',                      'balqis.tutor@studytwin.com',         '123456', 'tutor',   'orange', 'fox',    'none');

-- ── tutors ─────────────────────────────────────────────────
INSERT INTO `tutors` (`id`,`user_id`,`subject`,`expertise`,`bio`,`rating`,`price_per_hour`) VALUES
(1,  1,  'Web Development',                     'HTML, CSS, PHP',                                               'Expert in web development',                                                                         4.9, 25.00),
(2,  2,  'Database Design',                     'MySQL & SQL',                                                  'Database specialist',                                                                               4.8, 30.00),
(3,  3,  'Networking',                          'Cisco Networking',                                             'Network tutor',                                                                                     4.7, 20.00),
(15, 15, 'Gamification for Content Management', 'Gamification, Content Strategy, User Engagement',             'Experienced tutor in gamification techniques, content management systems, and user engagement.',      4.8, 35.00),
(16, 7,  'Web Development',                     'HTML, CSS, JavaScript, PHP, MySQL',                           'Passionate web developer with experience in building responsive websites and web applications.',      4.9, 40.00),
(17, 8,  'Database Management',                 'MySQL, SQL, Database Design, ERD',                            'Specialized in database design, normalization, and SQL query optimization.',                         4.7, 38.00),
(18, 11, 'Information Management',              'Records Management, Information Systems',                     'Helping students understand information organization and records management principles.',              4.6, 30.00),
(19, 12, 'Digital Marketing',                   'SEO, Social Media Marketing, Analytics',                     'Expert in online marketing strategies and digital business growth.',                                   4.8, 45.00),
(20, 13, 'Programming Fundamentals',            'C++, Java, Python',                                           'Experienced programming tutor focusing on beginner-friendly coding lessons.',                        4.9, 35.00),
(21, 14, 'System Analysis and Design',          'UML, SDLC, System Documentation',                            'Guides students through system development life cycle and software design concepts.',                  4.7, 42.00),
(22, 15, 'Data Analytics',                      'Excel, Power BI, Data Visualization',                        'Helping students analyze and visualize data effectively for decision making.',                        4.8, 50.00),
(23, 17, 'Cybersecurity Basics',                'Network Security, Ethical Hacking, Security Awareness',      'Provides practical knowledge on cybersecurity fundamentals and best practices.',                       4.9, 55.00),
(24, 18, 'Artificial Intelligence',             'Machine Learning, AI Concepts, Data Science',                'Introduces AI concepts and machine learning applications for beginners.',                             4.8, 60.00),
(25, 19, 'Mobile App Development',              'Android Studio, Flutter, UI Design',                         'Specialized in mobile application development for Android and cross-platform systems.',                4.7, 50.00);

-- ── bookings ───────────────────────────────────────────────
INSERT INTO `bookings` (`id`,`student_id`,`tutor_id`,`subject`,`session_date`,`status`) VALUES
(1, 4, 1, 'Web Development',  '2026-06-20', 'completed'),
(2, 4, 2, 'Database Design',  '2026-06-22', 'confirmed'),
(3, 4, 3, 'Networking',       '2026-06-25', 'pending'),
(4, 5, 1, 'HTML, CSS, PHP',   '2026-06-22', 'pending'),
(5, 5, 2, 'MySQL & SQL',      '2026-06-21', 'pending'),
(6, 5, 3, 'Cisco Networking', '2026-06-03', 'pending');

-- ── reviews ────────────────────────────────────────────────
INSERT INTO `reviews` (`id`,`student_id`,`tutor_id`,`rating`,`comment`) VALUES
(1, 4, 1, 5.0, 'Very clear explanation'),
(2, 4, 2, 4.5, 'Good teaching style'),
(3, 4, 3, 4.0, 'Helpful session');

-- ── messages ───────────────────────────────────────────────
INSERT INTO `messages` (`id`,`sender_id`,`receiver_id`,`message`) VALUES
(1, 4, 1, 'hi when are you available?');

-- ── notifications ──────────────────────────────────────────
INSERT INTO `notifications` (`id`,`user_id`,`title`,`message`) VALUES
(1, 4, 'Booking Confirmed', 'Your Web Development session is confirmed.'),
(2, 4, 'Reminder',          'You have a tutoring session tomorrow.');

-- ── payments ───────────────────────────────────────────────
INSERT INTO `payments` (`id`,`booking_id`,`student_id`,`amount`,`payment_method`,`status`) VALUES
(1, 4, 5, 25.00, 'online_banking', 'paid'),
(2, 2, 4, 30.00, 'ewallet',        'paid'),
(3, 3, 4, 20.00, 'online_banking', 'paid');

-- ── tutor_top_rank ─────────────────────────────────────────
INSERT INTO `tutor_top_rank` (`id`,`tutor_id`,`rating`,`rank_position`) VALUES
(1, 1, 4.9, 1),
(2, 2, 4.8, 2),
(3, 3, 4.7, 3);

COMMIT;

-- ============================================================
--  11. ROOMS (new for tutor-created bookable sessions/rooms)
--     Specific one-time sessions with student limit, date/time.
--     Students join by creating a booking linked to room_id.
-- ============================================================
CREATE TABLE IF NOT EXISTS `rooms` (
    `id`             INT(11)      NOT NULL AUTO_INCREMENT,
    `tutor_id`       INT(11)      NOT NULL,
    `title`          VARCHAR(100) NOT NULL DEFAULT 'Study Session',
    `subject`        VARCHAR(100) NOT NULL,
    `session_date`   DATE         NOT NULL,
    `start_time`     TIME         NOT NULL,
    `end_time`       TIME         NOT NULL,
    `max_students`   INT(11)      NOT NULL DEFAULT 5,
    `description`    TEXT         DEFAULT NULL,
    `status`         ENUM('open','closed','cancelled') DEFAULT 'open',
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_rooms_tutor` (`tutor_id`),
    CONSTRAINT `rooms_ibfk_1`
        FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add room_id to bookings (for linking to specific rooms)
ALTER TABLE `bookings` 
    ADD COLUMN `room_id` INT(11) NULL AFTER `tutor_id`,
    ADD KEY `fk_book_room` (`room_id`),
    ADD CONSTRAINT `bookings_ibfk_3`
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;

-- ============================================================
--  END OF studytwin_database.sql
-- ============================================================