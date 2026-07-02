-- Abweichung vom alten Ist-Schema (siehe itdesign.md Abschnitt 6):
-- Sperrt gegen Benutzerkonto + IP (identifier) statt nur gegen IP, mit Sperrzeitpunkt
-- statt Live-Zählung. Funktional gleichwertig zum alten reinen IP-Log, aber granularer.
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `identifier`    VARCHAR(255) NOT NULL,
    `attempts`      INT NOT NULL DEFAULT 0,
    `last_attempt`  DATETIME NOT NULL,
    `locked_until`  DATETIME NULL,

    UNIQUE KEY `uk_login_attempts_identifier` (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
