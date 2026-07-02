CREATE TABLE IF NOT EXISTS `interactions` (
    `interaction_id`    INT PRIMARY KEY AUTO_INCREMENT,
    `person_id`         INT NOT NULL,
    `user_id`           INT NOT NULL,
    `interaction_date`  TIMESTAMP NOT NULL,
    `interaction_type`  ENUM('COFFEE_MEETING', 'EMAIL', 'LINKEDIN_MESSAGE', 'PHONE_CALL', 'LUNCH', 'MEETING', 'CONFERENCE', 'OTHER') NOT NULL,
    `memo`              TEXT NULL,
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY `idx_interactions_person_id` (`person_id`),
    KEY `idx_interactions_user_id` (`user_id`),
    CONSTRAINT `fk_interactions_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_interactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
