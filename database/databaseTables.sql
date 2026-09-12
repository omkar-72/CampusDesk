/* CREATING THE STRUCTURE FOR PROJECT -> CampusDesk */


CREATE TABLE roles (
    role_id     SERIAL PRIMARY KEY,
    role_name   VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE departments (
    department_id   SERIAL PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    status          BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE users
(
    user_id         SERIAL PRIMARY KEY,
    role_id         INT REFERENCES roles(role_id) ON DELETE RESTRICT,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    mobile_number   VARCHAR(15),
    account_status  BOOLEAN DEFAULT TRUE,
    last_login      TIMESTAMP,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    profile_photo   BYTEA
);

CREATE TABLE students
(
    student_id      SERIAL PRIMARY KEY,
    user_id         INT UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    department_id   INT REFERENCES departments(department_id) ON DELETE RESTRICT,
    full_name       VARCHAR(150) NOT NULL,
    prn             VARCHAR(30) UNIQUE,
    roll_no         VARCHAR(20),
    course          VARCHAR(100),
    year            INT,
    semester        INT,
    division        VARCHAR(20),
    address         TEXT
);

CREATE TABLE authorities
(
    authority_id    SERIAL PRIMARY KEY,
    user_id         INT UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    department_id   INT REFERENCES departments(department_id) ON DELETE RESTRICT,
    name            VARCHAR(150) NOT NULL,
    designation     VARCHAR(100),
    status          BOOLEAN DEFAULT TRUE
);

CREATE TABLE grievance_categories
(
    category_id     SERIAL PRIMARY KEY,
    category_name   VARCHAR(100) NOT NULL UNIQUE,
    status          BOOLEAN DEFAULT TRUE
);

CREATE TABLE suggestion_categories
(
    category_id     SERIAL PRIMARY KEY,
    category_name   VARCHAR(100) NOT NULL UNIQUE,
    status          BOOLEAN DEFAULT TRUE
);


CREATE TABLE application_types
(
    application_type_id   SERIAL PRIMARY KEY,
    type_name             VARCHAR(100) NOT NULL UNIQUE,
    status                BOOLEAN DEFAULT TRUE
);


CREATE TABLE statuses
(
    status_id     SERIAL PRIMARY KEY,
    status_name   VARCHAR(50) NOT NULL,
    module_type   VARCHAR(30) NOT NULL,
    description   TEXT,
    status        BOOLEAN DEFAULT TRUE
);

CREATE TABLE grievances
(
    grievance_id       SERIAL PRIMARY KEY,
    student_id         INT REFERENCES students(student_id) ON DELETE RESTRICT,
    category_id        INT REFERENCES grievance_categories(category_id) ON DELETE RESTRICT,
    assigned_to        INT REFERENCES authorities(authority_id) ON DELETE SET NULL,
    status_id          INT REFERENCES statuses(status_id) ON DELETE RESTRICT,
    title              VARCHAR(200) NOT NULL,
    description        TEXT NOT NULL,
    anonymous_status   BOOLEAN DEFAULT FALSE,
    submission_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolution_date    TIMESTAMP,
    resolution_remarks TEXT
);

CREATE TABLE suggestions
(
    suggestion_id   SERIAL PRIMARY KEY,
    student_id      INT REFERENCES students(student_id) ON DELETE RESTRICT,
    category_id     INT REFERENCES suggestion_categories(category_id) ON DELETE RESTRICT,
    reviewed_by     INT REFERENCES authorities(authority_id) ON DELETE SET NULL,
    status_id       INT REFERENCES statuses(status_id) ON DELETE RESTRICT,
    title            VARCHAR(200) NOT NULL,
    description      TEXT NOT NULL,
    submission_date  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    decision_date    TIMESTAMP,
    remarks          TEXT
);


CREATE TABLE applications
(
    application_id      SERIAL PRIMARY KEY,
    student_id          INT REFERENCES students(student_id) ON DELETE RESTRICT,
    status_id           INT REFERENCES statuses(status_id) ON DELETE RESTRICT,
    application_type_id INT REFERENCES application_types(application_type_id) ON DELETE RESTRICT,
    assigned_to         INT REFERENCES authorities(authority_id) ON DELETE SET NULL,
    subject             VARCHAR(200) NOT NULL,
    description         TEXT NOT NULL,
    submission_date     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    review_date         TIMESTAMP,
    remarks             TEXT
);

CREATE TABLE notifications
(
    notification_id   SERIAL PRIMARY KEY,
    user_id           INT REFERENCES users(user_id) ON DELETE CASCADE,
    module_type       VARCHAR(30) NOT NULL,
    reference_id      INT NOT NULL,
    title             VARCHAR(200) NOT NULL,
    message           TEXT NOT NULL,
    notification_type VARCHAR(50),
    is_read           BOOLEAN DEFAULT FALSE,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE attachments
(
    attachment_id   SERIAL PRIMARY KEY,
    user_id         INT REFERENCES users(user_id) ON DELETE CASCADE,
    module_type     VARCHAR(30) NOT NULL,
    reference_id    INT NOT NULL,
    file_name       VARCHAR(255) NOT NULL,
    file_type       VARCHAR(100) NOT NULL,
    file_size       BIGINT NOT NULL,
    file_data       BYTEA NOT NULL,
    uploaded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs
(
    audit_id       SERIAL PRIMARY KEY,
    user_id        INT REFERENCES users(user_id) ON DELETE RESTRICT,
    module_type    VARCHAR(30) NOT NULL,
    reference_id   INT NOT NULL,
    action         VARCHAR(100) NOT NULL,
    ip_address     INET,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


