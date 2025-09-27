CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin','inactive') NOT NULL DEFAULT 'inactive',
    tokens_sent INT NOT NULL DEFAULT 0,
    tokens_generated INT NOT NULL DEFAULT 0,
    cost DECIMAL(10, 6) NOT NULL DEFAULT 0.00
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE Persons (
    person_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
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
    contact_cycle ENUM('WEEKLY', 'BIWEEKLY', 'MONTHLY', 'QUARTERLY', 'SEMI_ANNUALLY', 'ANNUALLY'),
    notes TEXT, -- Added for general notes about the person
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE Interactions (
    interaction_id INT PRIMARY KEY AUTO_INCREMENT,
    person_id INT NOT NULL,
    user_id INT NOT NULL, -- Which user logged the interaction?
    interaction_date TIMESTAMP NOT NULL,
    type ENUM('COFFEE_MEETING', 'EMAIL', 'LINKEDIN_MESSAGE', 'PHONE_CALL', 'LUNCH', 'MEETING', 'CONFERENCE', 'OTHER') NOT NULL,
    memo TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (person_id) REFERENCES Persons(person_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE `login_attempts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempt_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;