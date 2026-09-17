INSERT INTO roles (role_name)
VALUES
('STUDENT'),
('AUTHORITY'),
('ADMIN');


INSERT INTO departments (department_name, status)
VALUES
('Computer Science', TRUE),
('Information Technology', TRUE),
('Microbiology', TRUE),
('Biotechnology', TRUE),
('Commerce', TRUE),
('Arts', TRUE),
('Mechanical Engineering', TRUE),
('Civil Engineering', TRUE),
('Electrical Engineering', TRUE),
('Electronics and Telecommunication', TRUE),
('BBA', TRUE),
('BCA', TRUE),
('MBA', TRUE),
('MCA', TRUE),
('M.Sc Computer Science', TRUE),
('M.Sc Microbiology', TRUE),
('M.Sc Biotechnology', TRUE);

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
