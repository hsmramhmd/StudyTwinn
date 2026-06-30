<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "studytwin"
);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Auto-create rooms table and room_id column if they don't exist (for new "Rooms/Sessions" feature)
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS `rooms` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `tutor_id` INT(11) NOT NULL,
        `title` VARCHAR(100) NOT NULL DEFAULT 'Study Session',
        `subject` VARCHAR(100) NOT NULL,
        `session_date` DATE NOT NULL,
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `max_students` INT(11) NOT NULL DEFAULT 5,
        `description` TEXT DEFAULT NULL,
        `status` ENUM('open','closed','cancelled') DEFAULT 'open',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `fk_rooms_tutor` (`tutor_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$col_check = mysqli_query($conn, "SHOW COLUMNS FROM `bookings` LIKE 'room_id'");
if ($col_check && mysqli_num_rows($col_check) == 0) {
    mysqli_query($conn, "ALTER TABLE `bookings` ADD `room_id` INT(11) NULL AFTER `tutor_id`");
    // Note: Foreign key omitted for safety on existing data; can be added manually if needed
}

?>