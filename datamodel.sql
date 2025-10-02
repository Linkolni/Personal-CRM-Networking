CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin','inactive') NOT NULL DEFAULT 'inactive',
    tokens_sent INT NOT NULL DEFAULT 0,
    tokens_generated INT NOT NULL DEFAULT 0,
    tokens_cost DECIMAL(10, 6) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE persons (
    person_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL, -- Einziges Pflichtfeld bezogen auf Person.
    email1 VARCHAR(255),
    email2 VARCHAR(255),
    phone1 VARCHAR(50),
    phone2 VARCHAR(50),
    company VARCHAR(150),
    position VARCHAR(150), -- Added for the job title
    linkedin_profile VARCHAR(255),
    website VARCHAR(255),
    birthday DATE,
    status ENUM('NEW', 'ACTIVE', 'INACTIVE') DEFAULT 'NEW',
    priority ENUM('TOP10', 'TOP25', 'TOP50', 'TOP100'),
    circles VARCHAR(150),
    contact_cycle ENUM('WEEKLY', 'BIWEEKLY', 'MONTHLY', 'QUARTERLY', 'SEMI_ANNUALLY', 'ANNUALLY'),
    notes TEXT, -- Added for general notes about the person
    openai_conversation_id VARCHAR(255) DEFAULT NULL, -- For storing conversation context
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE interactions (
    interaction_id INT PRIMARY KEY AUTO_INCREMENT,
    person_id INT NOT NULL,
    user_id INT NOT NULL, -- Bleibt NOT NULL
    interaction_date TIMESTAMP NOT NULL,
    interaction_type ENUM('COFFEE_MEETING', 'EMAIL', 'LINKEDIN_MESSAGE', 'PHONE_CALL', 'LUNCH', 'MEETING', 'CONFERENCE', 'OTHER') NOT NULL,
    memo TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (person_id) REFERENCES persons(person_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE -- <-- Geändert zu CASCADE
);


CREATE TABLE `login_attempts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempt_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;