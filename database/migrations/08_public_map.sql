-- Public maps are opt-in; existing project visibility is deliberately ignored.
CREATE TABLE IF NOT EXISTS svm_public_maps (
 project_id INT NOT NULL PRIMARY KEY,
 token CHAR(48) NOT NULL UNIQUE,
 enabled TINYINT NOT NULL DEFAULT 0,
 fields_json TEXT NOT NULL,
 photos TINYINT NOT NULL DEFAULT 0,
 photo_notes TINYINT NOT NULL DEFAULT 0,
 reports TINYINT NOT NULL DEFAULT 0,
 email_notify TINYINT NOT NULL DEFAULT 0,
 last_mail_at DATETIME NULL,
 version INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS svm_public_objects (
 project_id INT NOT NULL,
 kind VARCHAR(5) NOT NULL,
 object_id INT NOT NULL,
 token CHAR(48) NOT NULL UNIQUE,
 enabled TINYINT NOT NULL DEFAULT 0,
 PRIMARY KEY(project_id,kind,object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS svm_public_reports (
 id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 project_id INT NOT NULL,
 kind VARCHAR(5) NOT NULL,
 object_id INT NOT NULL,
 object_code VARCHAR(100) NOT NULL,
 message TEXT NOT NULL,
 contact VARCHAR(200) NOT NULL DEFAULT '',
 status VARCHAR(12) NOT NULL DEFAULT 'new',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 updated_by INT NULL,
 KEY project_status(project_id,status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS svm_public_rate (
 bucket CHAR(64) NOT NULL PRIMARY KEY,
 hits INT NOT NULL DEFAULT 0,
 expires_at DATETIME NOT NULL,
 KEY expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
