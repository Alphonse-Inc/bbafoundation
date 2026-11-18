-- BBAFoundation schema
-- MySQL 5.7+ / MariaDB 10.3+. InnoDB, utf8mb4.
-- Timestamps are stored in UTC. The application displays Africa/Lagos.
-- The installer runs this file once. It does not drop existing tables.

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS roles (
    id TINYINT UNSIGNED NOT NULL,
    slug VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,
    description VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_id TINYINT UNSIGNED NOT NULL,
    surname VARCHAR(120) NOT NULL DEFAULT '',
    first_name VARCHAR(120) NOT NULL DEFAULT '',
    other_names VARCHAR(160) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL DEFAULT '',
    phone_normalized VARCHAR(40) NOT NULL DEFAULT '',
    gender VARCHAR(20) NOT NULL DEFAULT '',
    password_hash VARCHAR(255) NOT NULL,
    email_verified_at DATETIME NULL,
    verification_hash CHAR(64) NULL,
    verification_expires DATETIME NULL,
    totp_secret VARCHAR(64) NULL,
    totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    failed_logins SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_login_at DATETIME NULL,
    remember_hash CHAR(64) NULL,
    page_size SMALLINT UNSIGNED NOT NULL DEFAULT 25,
    notify_email TINYINT(1) NOT NULL DEFAULT 1,
    consent_at DATETIME NULL,
    consent_version VARCHAR(40) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_phone (phone_normalized),
    KEY idx_users_role (role_id),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    request_ip VARCHAR(45) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_resets_user (user_id),
    KEY idx_resets_token (token_hash),
    CONSTRAINT fk_resets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(40) NOT NULL DEFAULT '',
    entity_id INT UNSIGNED NULL,
    summary VARCHAR(500) NOT NULL,
    ip VARCHAR(45) NOT NULL DEFAULT '',
    user_agent VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_audit_created (created_at),
    KEY idx_audit_user (user_id),
    KEY idx_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    bucket VARCHAR(80) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 1,
    window_start DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_rate_bucket (bucket, ip, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    skey VARCHAR(120) NOT NULL,
    svalue MEDIUMTEXT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (skey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS labels (
    lkey VARCHAR(120) NOT NULL,
    lvalue TEXT NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (lkey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    disk_path VARCHAR(255) NOT NULL,
    public_path VARCHAR(255) NOT NULL DEFAULT '',
    mime VARCHAR(120) NOT NULL DEFAULT '',
    original_name VARCHAR(255) NOT NULL DEFAULT '',
    alt_text VARCHAR(255) NOT NULL DEFAULT '',
    byte_size INT UNSIGNED NOT NULL DEFAULT 0,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    checksum CHAR(64) NOT NULL DEFAULT '',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_media_public (public_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(120) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    meta_title VARCHAR(255) NOT NULL DEFAULT '',
    meta_description VARCHAR(320) NOT NULL DEFAULT '',
    page_type VARCHAR(40) NOT NULL DEFAULT 'page',
    status VARCHAR(20) NOT NULL DEFAULT 'published',
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pages_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_versions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    page_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    effective_label VARCHAR(80) NOT NULL DEFAULT '',
    note VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_versions_page (page_id),
    CONSTRAINT fk_versions_page FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hero_slides (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sort_order INT NOT NULL DEFAULT 0,
    kicker VARCHAR(160) NOT NULL DEFAULT '',
    title VARCHAR(255) NOT NULL,
    subtitle TEXT NOT NULL,
    primary_label VARCHAR(80) NOT NULL DEFAULT '',
    primary_url VARCHAR(255) NOT NULL DEFAULT '',
    secondary_label VARCHAR(80) NOT NULL DEFAULT '',
    secondary_url VARCHAR(255) NOT NULL DEFAULT '',
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_slides_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blocks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    group_key VARCHAR(40) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    title VARCHAR(255) NOT NULL DEFAULT '',
    body MEDIUMTEXT NULL,
    link_url VARCHAR(255) NOT NULL DEFAULT '',
    extra_json LONGTEXT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    is_original TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_blocks_group (group_key, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faq_groups (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faqs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    group_id INT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    question VARCHAR(500) NOT NULL,
    answer MEDIUMTEXT NOT NULL,
    is_addition TINYINT(1) NOT NULL DEFAULT 0,
    flag_note VARCHAR(500) NOT NULL DEFAULT '',
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_faqs_group (group_id, sort_order),
    CONSTRAINT fk_faqs_group FOREIGN KEY (group_id) REFERENCES faq_groups (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trustees (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    role_label VARCHAR(80) NOT NULL DEFAULT '',
    listing_name VARCHAR(180) NOT NULL,
    profile_name VARCHAR(180) NOT NULL DEFAULT '',
    credential VARCHAR(255) NOT NULL DEFAULT '',
    summary TEXT NULL,
    full_profile MEDIUMTEXT NULL,
    structured_json LONGTEXT NULL,
    photo_file VARCHAR(180) NOT NULL DEFAULT '',
    social_facebook VARCHAR(255) NOT NULL DEFAULT '',
    social_twitter VARCHAR(255) NOT NULL DEFAULT '',
    social_youtube VARCHAR(255) NOT NULL DEFAULT '',
    social_linkedin VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    meta_description VARCHAR(320) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY uq_trustees_slug (slug),
    KEY idx_trustees_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sort_order INT NOT NULL DEFAULT 0,
    code VARCHAR(20) NOT NULL DEFAULT '',
    name VARCHAR(180) NOT NULL DEFAULT '',
    link_url VARCHAR(255) NOT NULL DEFAULT '',
    image_file VARCHAR(180) NOT NULL DEFAULT '',
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_affiliates_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_news_cat_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_posts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NULL,
    author_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    excerpt VARCHAR(500) NOT NULL DEFAULT '',
    body MEDIUMTEXT NOT NULL,
    featured_path VARCHAR(255) NOT NULL DEFAULT '',
    featured_alt VARCHAR(255) NOT NULL DEFAULT '',
    meta_title VARCHAR(255) NOT NULL DEFAULT '',
    meta_description VARCHAR(320) NOT NULL DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    is_demo TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_news_slug (slug),
    KEY idx_news_status (status, published_at),
    CONSTRAINT fk_news_category FOREIGN KEY (category_id) REFERENCES news_categories (id) ON DELETE SET NULL,
    CONSTRAINT fk_news_author FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    excerpt VARCHAR(500) NOT NULL DEFAULT '',
    body MEDIUMTEXT NOT NULL,
    venue VARCHAR(255) NOT NULL DEFAULT '',
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    featured_path VARCHAR(255) NOT NULL DEFAULT '',
    featured_alt VARCHAR(255) NOT NULL DEFAULT '',
    meta_title VARCHAR(255) NOT NULL DEFAULT '',
    meta_description VARCHAR(320) NOT NULL DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    is_demo TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_events_slug (slug),
    KEY idx_events_status (status, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS redirects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_path VARCHAR(255) NOT NULL,
    target_path VARCHAR(255) NOT NULL,
    status_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    note VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY uq_redirects_source (source_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lgas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    state_name VARCHAR(80) NOT NULL DEFAULT 'Ekiti',
    note VARCHAR(255) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lgas_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS institutions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(220) NOT NULL,
    institution_type VARCHAR(40) NOT NULL DEFAULT 'university',
    ownership VARCHAR(40) NOT NULL DEFAULT 'government',
    state_name VARCHAR(80) NOT NULL DEFAULT '',
    is_suggestion TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_institutions_name (name),
    KEY idx_institutions_type (institution_type, ownership)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cycles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    announcement MEDIUMTEXT NULL,
    opens_at DATETIME NULL,
    closes_at DATETIME NULL,
    extension_until DATETIME NULL,
    award_count INT UNSIGNED NOT NULL DEFAULT 10,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    require_indigene TINYINT(1) NOT NULL DEFAULT 1,
    allow_ijero TINYINT(1) NOT NULL DEFAULT 1,
    allow_ewu TINYINT(1) NOT NULL DEFAULT 1,
    allow_other TINYINT(1) NOT NULL DEFAULT 0,
    require_government TINYINT(1) NOT NULL DEFAULT 1,
    min_cgpa DECIMAL(4,2) NOT NULL DEFAULT 3.50,
    poly_min VARCHAR(40) NOT NULL DEFAULT 'upper_credit',
    hard_block TINYINT(1) NOT NULL DEFAULT 0,
    referee_links TINYINT(1) NOT NULL DEFAULT 1,
    legacy_zip TINYINT(1) NOT NULL DEFAULT 0,
    max_file_bytes INT UNSIGNED NOT NULL DEFAULT 5242880,
    max_total_bytes INT UNSIGNED NOT NULL DEFAULT 20971520,
    blind_review TINYINT(1) NOT NULL DEFAULT 0,
    scores_released TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_cycles_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cycle_documents (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cycle_id INT UNSIGNED NOT NULL,
    doc_key VARCHAR(40) NOT NULL,
    label VARCHAR(180) NOT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    flag_note VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY uq_cycle_doc (cycle_id, doc_key),
    CONSTRAINT fk_cycle_docs FOREIGN KEY (cycle_id) REFERENCES cycles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rubric_criteria (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cycle_id INT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    name VARCHAR(160) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    weight DECIMAL(6,2) NOT NULL DEFAULT 1,
    max_score TINYINT UNSIGNED NOT NULL DEFAULT 10,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_rubric_cycle (cycle_id),
    CONSTRAINT fk_rubric_cycle FOREIGN KEY (cycle_id) REFERENCES cycles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cycle_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reference VARCHAR(30) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    current_step TINYINT UNSIGNED NOT NULL DEFAULT 1,
    surname VARCHAR(120) NOT NULL DEFAULT '',
    other_names VARCHAR(160) NOT NULL DEFAULT '',
    sex VARCHAR(20) NOT NULL DEFAULT '',
    date_of_birth DATE NULL,
    place_of_birth VARCHAR(160) NOT NULL DEFAULT '',
    lga_id INT UNSIGNED NULL,
    community VARCHAR(40) NOT NULL DEFAULT '',
    community_other VARCHAR(160) NOT NULL DEFAULT '',
    parents_hometown TEXT NULL,
    phone VARCHAR(40) NOT NULL DEFAULT '',
    phone_normalized VARCHAR(40) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL DEFAULT '',
    id_type VARCHAR(80) NOT NULL DEFAULT '',
    nin_cipher TEXT NULL,
    nin_hash CHAR(64) NULL,
    residential_address TEXT NULL,
    secondary_school_name VARCHAR(255) NOT NULL DEFAULT '',
    secondary_school_address TEXT NULL,
    secondary_dates VARCHAR(160) NOT NULL DEFAULT '',
    principal_name VARCHAR(160) NOT NULL DEFAULT '',
    institution_id INT UNSIGNED NULL,
    institution_name VARCHAR(255) NOT NULL DEFAULT '',
    institution_address TEXT NULL,
    department VARCHAR(160) NOT NULL DEFAULT '',
    faculty VARCHAR(160) NOT NULL DEFAULT '',
    institution_type VARCHAR(40) NOT NULL DEFAULT '',
    ownership VARCHAR(40) NOT NULL DEFAULT '',
    government_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    current_level VARCHAR(40) NOT NULL DEFAULT '',
    admission_year VARCHAR(10) NOT NULL DEFAULT '',
    course_duration VARCHAR(40) NOT NULL DEFAULT '',
    cgpa VARCHAR(20) NOT NULL DEFAULT '',
    cgpa_scale VARCHAR(20) NOT NULL DEFAULT '5.00',
    poly_grade VARCHAR(40) NOT NULL DEFAULT '',
    academic_flag TINYINT(1) NOT NULL DEFAULT 0,
    academic_flag_reason VARCHAR(255) NOT NULL DEFAULT '',
    personal_statement MEDIUMTEXT NULL,
    declaration_accepted TINYINT(1) NOT NULL DEFAULT 0,
    signature_name VARCHAR(200) NOT NULL DEFAULT '',
    declared_at DATETIME NULL,
    submitted_at DATETIME NULL,
    reopened_until DATETIME NULL,
    reopen_reason VARCHAR(255) NOT NULL DEFAULT '',
    duplicate_flag TINYINT(1) NOT NULL DEFAULT 0,
    duplicate_note VARCHAR(255) NOT NULL DEFAULT '',
    eligibility_json LONGTEXT NULL,
    eligibility_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    eligibility_override VARCHAR(20) NULL,
    eligibility_override_reason VARCHAR(255) NOT NULL DEFAULT '',
    retained_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_app_cycle_user (cycle_id, user_id),
    UNIQUE KEY uq_app_reference (reference),
    KEY idx_app_status (cycle_id, status),
    KEY idx_app_email (cycle_id, email),
    KEY idx_app_phone (cycle_id, phone_normalized),
    KEY idx_app_nin (cycle_id, nin_hash),
    KEY idx_app_community (community),
    KEY idx_app_level (current_level),
    CONSTRAINT fk_app_cycle FOREIGN KEY (cycle_id) REFERENCES cycles (id),
    CONSTRAINT fk_app_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_app_lga FOREIGN KEY (lga_id) REFERENCES lgas (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS application_documents (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    doc_key VARCHAR(40) NOT NULL,
    original_name VARCHAR(255) NOT NULL DEFAULT '',
    stored_name VARCHAR(80) NOT NULL,
    mime VARCHAR(120) NOT NULL DEFAULT '',
    byte_size INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    reviewer_note VARCHAR(500) NOT NULL DEFAULT '',
    uploaded_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_docs_app (application_id, doc_key),
    CONSTRAINT fk_docs_app FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS application_referees (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    kind VARCHAR(20) NOT NULL DEFAULT 'referee',
    full_name VARCHAR(180) NOT NULL DEFAULT '',
    role_title VARCHAR(160) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL DEFAULT '',
    phone VARCHAR(40) NOT NULL DEFAULT '',
    address TEXT NULL,
    token_hash CHAR(64) NULL,
    token_expires DATETIME NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    response_text TEXT NULL,
    stored_name VARCHAR(80) NOT NULL DEFAULT '',
    original_name VARCHAR(255) NOT NULL DEFAULT '',
    submitted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_referees_app (application_id),
    KEY idx_referees_token (token_hash),
    CONSTRAINT fk_referees_app FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviewer_assignments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assignment (application_id, reviewer_id),
    KEY idx_assignment_reviewer (reviewer_id),
    CONSTRAINT fk_asg_app FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE,
    CONSTRAINT fk_asg_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS review_scores (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    assignment_id INT UNSIGNED NOT NULL,
    criterion_id INT UNSIGNED NOT NULL,
    score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_score (assignment_id, criterion_id),
    CONSTRAINT fk_score_asg FOREIGN KEY (assignment_id) REFERENCES reviewer_assignments (id) ON DELETE CASCADE,
    CONSTRAINT fk_score_criterion FOREIGN KEY (criterion_id) REFERENCES rubric_criteria (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS review_comments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    assignment_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_comments_asg (assignment_id),
    CONSTRAINT fk_comments_asg FOREIGN KEY (assignment_id) REFERENCES reviewer_assignments (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS review_conflicts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    reason VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_conflict (application_id, reviewer_id),
    CONSTRAINT fk_conflict_app FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE,
    CONSTRAINT fk_conflict_user FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS interviews (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    scheduled_at DATETIME NULL,
    location VARCHAR(255) NOT NULL DEFAULT '',
    notes TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'planned',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_interviews_app (application_id),
    CONSTRAINT fk_interviews_app FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS awardees (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    cycle_id INT UNSIGNED NOT NULL,
    verification_code VARCHAR(24) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending_acceptance',
    decision_rationale TEXT NULL,
    decided_by INT UNSIGNED NULL,
    decided_at DATETIME NULL,
    accept_deadline DATETIME NULL,
    accepted_at DATETIME NULL,
    declined_at DATETIME NULL,
    showcase_consent TINYINT(1) NOT NULL DEFAULT 0,
    public_note VARCHAR(500) NOT NULL DEFAULT '',
    expected_graduation VARCHAR(20) NOT NULL DEFAULT '',
    ended_reason VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_awardee_app (application_id),
    UNIQUE KEY uq_awardee_code (verification_code),
    KEY idx_awardee_status (status),
    KEY idx_awardee_cycle (cycle_id),
    CONSTRAINT fk_aw_app FOREIGN KEY (application_id) REFERENCES applications (id),
    CONSTRAINT fk_aw_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_aw_cycle FOREIGN KEY (cycle_id) REFERENCES cycles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS renewals (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    awardee_id INT UNSIGNED NOT NULL,
    academic_year VARCHAR(20) NOT NULL DEFAULT '',
    cgpa VARCHAR(20) NOT NULL DEFAULT '',
    notes TEXT NULL,
    stored_name VARCHAR(80) NOT NULL DEFAULT '',
    original_name VARCHAR(255) NOT NULL DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT 'submitted',
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note VARCHAR(500) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_renewals_awardee (awardee_id),
    CONSTRAINT fk_renewals_awardee FOREIGN KEY (awardee_id) REFERENCES awardees (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disbursements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    awardee_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NULL,
    currency VARCHAR(8) NOT NULL DEFAULT 'NGN',
    paid_on DATE NULL,
    method VARCHAR(80) NOT NULL DEFAULT '',
    reference_no VARCHAR(120) NOT NULL DEFAULT '',
    payee VARCHAR(180) NOT NULL DEFAULT '',
    notes TEXT NULL,
    stored_name VARCHAR(80) NOT NULL DEFAULT '',
    original_name VARCHAR(255) NOT NULL DEFAULT '',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_disb_awardee (awardee_id),
    CONSTRAINT fk_disb_awardee FOREIGN KEY (awardee_id) REFERENCES awardees (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    body VARCHAR(500) NOT NULL DEFAULT '',
    url VARCHAR(255) NOT NULL DEFAULT '',
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_notes_user (user_id, read_at, created_at),
    CONSTRAINT fk_notes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NULL,
    body TEXT NOT NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_messages_user (user_id, created_at),
    CONSTRAINT fk_messages_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_templates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tkey VARCHAR(60) NOT NULL,
    name VARCHAR(160) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    body_text MEDIUMTEXT NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_templates_key (tkey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_queue (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    to_email VARCHAR(190) NOT NULL,
    to_name VARCHAR(180) NOT NULL DEFAULT '',
    subject VARCHAR(200) NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    body_text MEDIUMTEXT NOT NULL,
    template_key VARCHAR(60) NOT NULL DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT 'queued',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 5,
    last_error VARCHAR(500) NOT NULL DEFAULT '',
    scheduled_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_queue_status (status, scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(180) NOT NULL,
    phone VARCHAR(40) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(180) NOT NULL DEFAULT '',
    message VARCHAR(200) NOT NULL,
    ip VARCHAR(45) NOT NULL DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_contact_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    token_hash CHAR(64) NULL,
    confirmed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_newsletter_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS data_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    request_type VARCHAR(20) NOT NULL,
    details TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(500) NOT NULL DEFAULT '',
    handled_by INT UNSIGNED NULL,
    handled_at DATETIME NULL,
    export_path VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_data_requests_user (user_id),
    CONSTRAINT fk_data_requests_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS breach_incidents (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    discovered_at DATETIME NULL,
    contained_at DATETIME NULL,
    notified_at DATETIME NULL,
    reported_at DATETIME NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'open',
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS backup_runs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kind VARCHAR(40) NOT NULL,
    file_path VARCHAR(255) NOT NULL DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT 'ok',
    note VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS legacy_imports (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_url VARCHAR(500) NOT NULL,
    local_path VARCHAR(255) NOT NULL DEFAULT '',
    checksum CHAR(64) NOT NULL DEFAULT '',
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    alt_text VARCHAR(255) NOT NULL DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    error_text VARCHAR(500) NOT NULL DEFAULT '',
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_legacy_url (source_url)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
