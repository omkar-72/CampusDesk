INSERT INTO roles (role_name)
VALUES
('STUDENT'),
('AUTHORITY'),
('ADMIN');


INSERT INTO departments (department_name, status)
VALUES
('Computer Science', TRUE),
('Mathematics', TRUE),
('Statistics', TRUE), 
('Physics', TRUE),
('Chemistry', TRUE),
('Electronics', TRUE),
('Biotechnology', TRUE),
('Microbiology', TRUE),
('Botany', TRUE),
('Zoology', TRUE),
('Environmental Science', TRUE),
('Geology', TRUE),
('Economics', TRUE),
('English', TRUE),
('Marathi', TRUE),
('Hindi', TRUE),
('Sanskrit', TRUE),
('History', TRUE),
('Political Science', TRUE),
('Psychology', TRUE),
('Sociology', TRUE),
('Philosophy', TRUE),
('Geography', TRUE),
('Public Administration', TRUE),
('Commerce', TRUE),
('Digital Art and Animation', TRUE),
('Fashion Technology', TRUE),
('Interior Design', TRUE),
('Media and Communication', TRUE);

INSERT INTO departments (department_name, status)
VALUES
('Administration Office', TRUE),
('Grant-in-Aid Office', TRUE),
('Self-Finance Office', TRUE),
('Accounts and Finance Section', TRUE),
('Registrar Office', TRUE),
('Examination Section', TRUE),
('Student Affairs', TRUE),
('Library', TRUE),
('Information Technology / ERP', TRUE),
('Research Coordination', TRUE),
('IQAC', TRUE),
('Sports / Gymkhana', TRUE),
('NSS', TRUE),
('NCC', TRUE),
('Hostel Administration', TRUE),
('Career Facilitation Center', TRUE),
('Student Development Board', TRUE),
('Student Grievance Redressal', TRUE);


INSERT INTO grievance_categories (category_name, status)
VALUES
    ('Academic Issues', TRUE),
    ('Examination', TRUE),
    ('Fees and Finance', TRUE),
    ('Library', TRUE),
    ('Infrastructure', TRUE),
    ('Hostel', TRUE),
    ('Transport', TRUE),
    ('Student Services', TRUE);


INSERT INTO suggestion_categories (category_name, status)
VALUES
    ('Academic Improvement', TRUE),
    ('Infrastructure Development', TRUE),
    ('Library Services', TRUE),
    ('Student Activities', TRUE),
    ('Digital Services', TRUE),
    ('Campus Facilities', TRUE);


INSERT INTO application_types (type_name, status)
VALUES
    ('Fee Installment', TRUE),
    ('Bonafide Certificate', TRUE),
    ('Leaving Certificate', TRUE),
    ('Scholarship', TRUE),
    ('Identity Card', TRUE),
    ('Exam Related', TRUE),
    ('Document Verification', TRUE),
    ('Other', TRUE);



INSERT INTO statuses
    (status_name, module_type, description, status)
VALUES
    ('New', 'GRIEVANCE', 'Grievance has been newly submitted', TRUE),
    ('Under Review', 'GRIEVANCE', 'Grievance is being reviewed', TRUE),
    ('In Progress', 'GRIEVANCE', 'Grievance is being processed', TRUE),
    ('Resolved', 'GRIEVANCE', 'Grievance has been resolved', TRUE),
    ('Rejected', 'GRIEVANCE', 'Grievance has been rejected', TRUE),

    ('New', 'SUGGESTION', 'Suggestion has been newly submitted', TRUE),
    ('Under Review', 'SUGGESTION', 'Suggestion is being reviewed', TRUE),
    ('Accepted', 'SUGGESTION', 'Suggestion has been accepted', TRUE),
    ('Rejected', 'SUGGESTION', 'Suggestion has been rejected', TRUE),
    ('Implemented', 'SUGGESTION', 'Suggestion has been implemented', TRUE),

    ('New', 'APPLICATION', 'Application has been newly submitted', TRUE),
    ('Processing', 'APPLICATION', 'Application is being processed', TRUE),
    ('Under Review', 'APPLICATION', 'Application is under review', TRUE),
    ('Approved', 'APPLICATION', 'Application has been approved', TRUE),
    ('Rejected', 'APPLICATION', 'Application has been rejected', TRUE);

