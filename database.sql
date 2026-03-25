CREATE TABLE IF NOT EXISTS student_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_uuid CHAR(32) NOT NULL,
    user_id INT NOT NULL,
    student_name VARCHAR(150) NOT NULL,
    student_email VARCHAR(190) NOT NULL,
    student_identifier VARCHAR(120) NOT NULL,
    submission_mode ENUM('full_upload', 'topic_only') NOT NULL DEFAULT 'full_upload',
    project_topic TEXT NOT NULL,

    chapters_original_name VARCHAR(255) DEFAULT NULL,
    chapters_path VARCHAR(255) DEFAULT NULL,
    chapters_text LONGTEXT DEFAULT NULL,
    methodology_text LONGTEXT DEFAULT NULL,

    dataset_original_name VARCHAR(255) DEFAULT NULL,
    dataset_path VARCHAR(255) DEFAULT NULL,
    dataset_summary_json LONGTEXT DEFAULT NULL,

    chapter_outline_markdown LONGTEXT DEFAULT NULL,

    degree_level ENUM('NCE/ND', 'BSc/HND', 'PGD', 'MSc/MPhil', 'PhD') NOT NULL DEFAULT 'BSc/HND',
    target_pages INT NOT NULL DEFAULT 50,
    include_graphs TINYINT(1) NOT NULL DEFAULT 1,
    hypothesis_mode ENUM('yes', 'auto-detect') NOT NULL DEFAULT 'auto-detect',
    output_format ENUM('word', 'pdf') NOT NULL DEFAULT 'word',
    admin_notes TEXT DEFAULT NULL,

    system_prompt LONGTEXT DEFAULT NULL,
    user_prompt LONGTEXT DEFAULT NULL,
    ai_markdown LONGTEXT DEFAULT NULL,

    generated_file_name VARCHAR(255) DEFAULT NULL,
    generated_file_path VARCHAR(255) DEFAULT NULL,
    download_name VARCHAR(255) DEFAULT NULL,
    download_expires_at DATETIME DEFAULT NULL,

    status ENUM('uploaded', 'configured', 'generating', 'ready', 'reviewed', 'failed') NOT NULL DEFAULT 'uploaded',
    configured_by INT DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    generated_at DATETIME DEFAULT NULL,
    email_sent_at DATETIME DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    download_count INT NOT NULL DEFAULT 0,
    last_downloaded_at DATETIME DEFAULT NULL,
    error_message TEXT DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_student_jobs_uuid (job_uuid),
    UNIQUE KEY uq_student_jobs_download_name (download_name),
    KEY idx_student_jobs_user_status (user_id, status),
    KEY idx_student_jobs_status_created (status, created_at),
    KEY idx_student_jobs_download_name (download_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
