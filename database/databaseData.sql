INSERT INTO roles (role_name)
VALUES
('STUDENT'),
('AUTHORITY'),
('ADMIN');


INSERT INTO departments (department_name, status)
VALUES
('Computer Science', TRUE),
('Microbiology', TRUE),
('Biotechnology', TRUE),
('Commerce', TRUE),
('Arts', TRUE);


INSERT INTO users (role_id, email, password, mobile_number, account_status, last_login, profile_photo)
VALUES
    (1, 'student1@campusdesk.com', 'HASH_STUDENT_001', '9876543210', TRUE, NULL, NULL),
    (1, 'student2@campusdesk.com', 'HASH_STUDENT_002', '9876543211', TRUE, NULL, NULL),
    (1, 'student3@campusdesk.com', 'HASH_STUDENT_003', '9876543212', TRUE, NULL, NULL),
    (1, 'student4@campusdesk.com', 'HASH_STUDENT_004', '9876543213', TRUE, NULL, NULL),
    (1, 'student5@campusdesk.com', 'HASH_STUDENT_005', '9876543214', TRUE, NULL, NULL),

    (2, 'authority1@campusdesk.com', 'HASH_AUTH_001', '9876543220', TRUE, NULL, NULL),
    (2, 'authority2@campusdesk.com', 'HASH_AUTH_002', '9876543221', TRUE, NULL, NULL),
    (2, 'authority3@campusdesk.com', 'HASH_AUTH_003', '9876543222', TRUE, NULL, NULL),

    (3, 'admin1@campusdesk.com', 'HASH_ADMIN_001', '9876543230', TRUE, NULL, NULL),
    (3, 'admin2@campusdesk.com', 'HASH_ADMIN_002', '9876543231', TRUE, NULL, NULL);


INSERT INTO students (user_id, department_id, full_name, prn, roll_no, course, year, semester, division, address)
VALUES
    (1, 1, 'Ganesh Patil', 'PRN2025001', '01', 'B.Sc Computer Science', 3, 6, 'A', 'Pune'),
    (2, 1, 'Rahul Shinde', 'PRN2025002', '02', 'B.Sc Computer Science', 3, 6, 'A', 'Pune'),
    (3, 2, 'Omkar Jadhav', 'PRN2025003', '03', 'B.Sc Microbiology', 2, 4, 'B', 'Pimpri'),
    (4, 3, 'Amod Naikare', 'PRN2025004', '04', 'B.Sc Biotechnology', 2, 4, 'A', 'Akurdi'),
    (5, 4, 'Parth Kulkarni', 'PRN2025005', '05', 'B.Com', 3, 6, 'B', 'Mumbai');


INSERT INTO authorities (user_id, department_id, name, designation, status)
VALUES
    (6, 1, 'Dr. Sunita Joshi', 'HOD', TRUE),
    (7, 2, 'Prof. Rajesh Kulkarni', 'Department Coordinator', TRUE),
    (8, 3, 'Prof. Priya Deshmukh', 'Faculty Coordinator', TRUE);


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


INSERT INTO grievances
    (student_id, category_id, assigned_to, status_id, title, description,
     anonymous_status, submission_date, resolution_date, resolution_remarks)
VALUES
    (1, 3, 1, 1, 'Issue with Fee Payment',
     'Unable to complete the semester fee payment.', FALSE,
     '2026-08-01 09:15:00', NULL, NULL),

    (2, 2, 1, 2, 'Examination Form Issue',
     'Unable to submit the examination form online.', FALSE,
     '2026-08-02 10:20:00', NULL, NULL),

    (3, 1, 2, 3, 'Academic Schedule Issue',
     'Class schedule has not been updated properly.', FALSE,
     '2026-08-03 11:00:00', NULL, NULL),

    (4, 5, 3, 4, 'Classroom Infrastructure',
     'Projector in the classroom is not working.', FALSE,
     '2026-08-04 12:10:00',
     '2026-08-06 15:30:00', 'Projector was repaired.'),

    (5, 4, 1, 5, 'Library Access Problem',
     'Unable to access the digital library resources.', FALSE,
     '2026-08-05 13:25:00',
     '2026-08-07 11:00:00', 'Issue could not be resolved.'),

    (1, 6, 2, 1, 'Hostel Maintenance Issue',
     'Water supply problem in the hostel.', FALSE,
     '2026-08-06 09:40:00', NULL, NULL),

    (2, 7, 3, 2, 'Transport Timing Issue',
     'College bus timing is inconvenient for students.', FALSE,
     '2026-08-07 10:15:00', NULL, NULL),

    (3, 8, 1, 3, 'Student Service Delay',
     'Delay in receiving student service.', FALSE,
     '2026-08-08 11:30:00', NULL, NULL),

    (4, 3, 2, 4, 'Fee Receipt Issue',
     'Fee receipt is not available after payment.', FALSE,
     '2026-08-09 12:45:00',
     '2026-08-11 14:00:00', 'Receipt was generated successfully.'),

    (5, 2, 3, 1, 'Exam Timetable Issue',
     'Examination timetable contains incorrect information.', FALSE,
     '2026-08-10 14:10:00', NULL, NULL),

    (1, 1, 1, 2, 'Course Information Issue',
     'Course information is not displayed correctly.', FALSE,
     '2026-08-11 09:05:00', NULL, NULL),

    (2, 5, 2, 3, 'Laboratory Equipment',
     'Some laboratory equipment is not functioning.', FALSE,
     '2026-08-12 10:30:00', NULL, NULL),

    (3, 4, 3, 4, 'Library Book Availability',
     'Required reference books are unavailable.', FALSE,
     '2026-08-13 11:20:00',
     '2026-08-15 13:10:00', 'Books were made available.'),

    (4, 6, 1, 1, 'Hostel Room Issue',
     'Maintenance is required in the hostel room.', TRUE,
     '2026-08-14 12:35:00', NULL, NULL),

    (5, 7, 2, 2, 'Bus Pass Issue',
     'Unable to renew the college bus pass.', FALSE,
     '2026-08-15 13:40:00', NULL, NULL),

    (1, 8, 3, 3, 'Student ID Service',
     'Delay in student identity card service.', FALSE,
     '2026-08-16 14:25:00', NULL, NULL),

    (2, 3, 1, 4, 'Fee Payment Confirmation',
     'Payment confirmation is not displayed.', FALSE,
     '2026-08-17 09:50:00',
     '2026-08-19 12:00:00', 'Payment confirmation updated.'),

    (3, 2, 2, 5, 'Examination Registration',
     'Examination registration request was rejected.', FALSE,
     '2026-08-18 10:45:00',
     '2026-08-20 10:30:00', 'Required information was incomplete.'),

    (4, 1, 3, 1, 'Lecture Schedule Issue',
     'Lecture schedule is not visible on the portal.', FALSE,
     '2026-08-19 11:15:00', NULL, NULL),

    (5, 5, 1, 2, 'Classroom Maintenance',
     'Fans and lights require maintenance.', FALSE,
     '2026-08-20 12:50:00', NULL, NULL);


INSERT INTO suggestions
    (student_id, category_id, reviewed_by, status_id, title, description,
     submission_date, decision_date, remarks)
VALUES
    (1, 1, 1, 6, 'Improve Study Facilities',
     'Provide more study spaces for students.',
     '2026-08-01 09:30:00', NULL, NULL),

    (2, 2, 1, 7, 'Improve Classroom Infrastructure',
     'Upgrade classroom furniture and equipment.',
     '2026-08-02 10:15:00', NULL, NULL),

    (3, 3, 2, 8, 'Improve Library Services',
     'Extend library working hours during examinations.',
     '2026-08-03 11:00:00', '2026-08-05 14:00:00',
     'Suggestion accepted for implementation.'),

    (4, 4, 3, 9, 'More Student Activities',
     'Organize more academic and cultural activities.',
     '2026-08-04 12:20:00', '2026-08-06 10:30:00',
     'Suggestion was not approved.'),

    (5, 5, 1, 10, 'Digital Notice Board',
     'Provide important notices through the student portal.',
     '2026-08-05 13:10:00', '2026-08-08 15:00:00',
     'Digital notice facility implemented.'),

    (1, 6, 2, 6, 'Improve Campus Facilities',
     'Improve basic facilities available on campus.',
     '2026-08-06 09:45:00', NULL, NULL),

    (2, 1, 3, 7, 'Online Study Material',
     'Provide study material through the student portal.',
     '2026-08-07 10:40:00', NULL, NULL),

    (3, 2, 1, 8, 'Better Laboratory Facilities',
     'Improve laboratory equipment and facilities.',
     '2026-08-08 11:25:00', '2026-08-10 12:00:00',
     'Suggestion accepted.'),

    (4, 3, 2, 10, 'Library Digital Resources',
     'Add more digital books and journals.',
     '2026-08-09 12:15:00', '2026-08-12 13:30:00',
     'Digital resources added.'),

    (5, 4, 3, 6, 'Sports Activities',
     'Conduct more sports events for students.',
     '2026-08-10 13:05:00', NULL, NULL),

    (1, 5, 1, 7, 'Clean Campus Initiative',
     'Introduce regular campus cleanliness activities.',
     '2026-08-11 09:20:00', NULL, NULL),

    (2, 6, 2, 8, 'Hostel Facilities',
     'Improve facilities provided in the hostel.',
     '2026-08-12 10:35:00', '2026-08-14 11:15:00',
     'Suggestion accepted.'),

    (3, 1, 3, 9, 'Academic Workshops',
     'Conduct additional technical workshops.',
     '2026-08-13 11:50:00', '2026-08-15 14:20:00',
     'Suggestion rejected due to scheduling constraints.'),

    (4, 2, 1, 10, 'Smart Classrooms',
     'Introduce smart classroom facilities.',
     '2026-08-14 12:40:00', '2026-08-17 10:00:00',
     'Smart classroom facility implemented.'),

    (5, 3, 2, 7, 'Improve Reading Area',
     'Increase seating capacity in the library reading area.',
     '2026-08-15 13:25:00', NULL, NULL);


INSERT INTO applications
    (student_id, status_id, application_type_id, assigned_to,
     subject, description, submission_date, review_date, remarks)
VALUES
    (1, 11, 1, 1,
     'Fee Installment Request',
     'Request for permission to pay semester fees in installments.',
     '2026-08-01 09:20:00', NULL, NULL),

    (2, 12, 2, 1,
     'Bonafide Certificate',
     'Request for a bonafide certificate for official purpose.',
     '2026-08-02 10:10:00', NULL, NULL),

    (3, 13, 4, 2,
     'Scholarship Application',
     'Application for scholarship consideration.',
     '2026-08-03 11:15:00', NULL, NULL),

    (4, 14, 5, 3,
     'Identity Card Request',
     'Request for a new student identity card.',
     '2026-08-04 12:05:00',
     '2026-08-06 14:00:00',
     'Application approved.'),

    (5, 15, 3, 1,
     'Leaving Certificate',
     'Request for issue of leaving certificate.',
     '2026-08-05 13:20:00',
     '2026-08-07 11:30:00',
     'Application rejected due to incomplete documents.'),

    (1, 12, 6, 2,
     'Examination Application',
     'Application regarding examination registration.',
     '2026-08-06 09:40:00', NULL, NULL),

    (2, 11, 7, 3,
     'Document Verification',
     'Request for verification of submitted documents.',
     '2026-08-07 10:25:00', NULL, NULL),

    (3, 14, 8, 1,
     'Other Student Service',
     'Request for a general student service.',
     '2026-08-08 11:35:00',
     '2026-08-10 13:15:00',
     'Application approved.'),

    (4, 13, 2, 2,
     'Bonafide Certificate',
     'Request for bonafide certificate for internship purpose.',
     '2026-08-09 12:30:00', NULL, NULL),

    (5, 14, 4, 3,
     'Scholarship Application',
     'Application for academic scholarship.',
     '2026-08-10 13:45:00',
     '2026-08-12 10:00:00',
     'Scholarship application approved.'),

    (1, 15, 5, 1,
     'Identity Card Replacement',
     'Request for replacement of lost identity card.',
     '2026-08-11 09:10:00',
     '2026-08-13 12:20:00',
     'Required information was incomplete.'),

    (2, 12, 1, 2,
     'Fee Installment Request',
     'Request for fee installment facility.',
     '2026-08-12 10:40:00', NULL, NULL),

    (3, 11, 3, 3,
     'Leaving Certificate',
     'Request for leaving certificate.',
     '2026-08-13 11:50:00', NULL, NULL),

    (4, 14, 6, 1,
     'Examination Application',
     'Application related to examination registration.',
     '2026-08-14 12:35:00',
     '2026-08-16 14:10:00',
     'Application approved.'),

    (5, 13, 7, 2,
     'Document Verification',
     'Request for verification of academic documents.',
     '2026-08-15 13:15:00', NULL, NULL);


INSERT INTO notifications
    (user_id, module_type, reference_id, title, message, notification_type, is_read, created_at)
VALUES
    (1, 'GRIEVANCE', 1, 'Grievance Submitted',
     'Your grievance has been submitted successfully.', 'SUBMISSION', TRUE, '2026-08-01 09:30:00'),

    (2, 'GRIEVANCE', 2, 'Grievance Under Review',
     'Your grievance is currently under review.', 'STATUS_UPDATE', FALSE, '2026-08-02 10:30:00'),

    (3, 'GRIEVANCE', 3, 'Grievance In Progress',
     'Your grievance is being processed.', 'STATUS_UPDATE', TRUE, '2026-08-03 11:30:00'),

    (4, 'GRIEVANCE', 4, 'Grievance Resolved',
     'Your grievance has been resolved.', 'STATUS_UPDATE', TRUE, '2026-08-04 16:00:00'),

    (5, 'GRIEVANCE', 5, 'Grievance Rejected',
     'Your grievance has been rejected.', 'STATUS_UPDATE', FALSE, '2026-08-05 16:30:00'),

    (1, 'GRIEVANCE', 6, 'Grievance Submitted',
     'Your grievance has been submitted successfully.', 'SUBMISSION', FALSE, '2026-08-06 10:00:00'),

    (2, 'GRIEVANCE', 7, 'Grievance Under Review',
     'Your grievance is currently under review.', 'STATUS_UPDATE', TRUE, '2026-08-07 11:00:00'),

    (3, 'GRIEVANCE', 8, 'Grievance In Progress',
     'Your grievance is being processed.', 'STATUS_UPDATE', FALSE, '2026-08-08 12:00:00'),

    (4, 'GRIEVANCE', 9, 'Grievance Resolved',
     'Your grievance has been resolved.', 'STATUS_UPDATE', TRUE, '2026-08-09 13:00:00'),

    (5, 'GRIEVANCE', 10, 'Grievance Submitted',
     'Your grievance has been submitted successfully.', 'SUBMISSION', FALSE, '2026-08-10 14:00:00'),

    (1, 'SUGGESTION', 1, 'Suggestion Submitted',
     'Your suggestion has been submitted successfully.', 'SUBMISSION', TRUE, '2026-08-01 10:00:00'),

    (2, 'SUGGESTION', 2, 'Suggestion Under Review',
     'Your suggestion is currently under review.', 'STATUS_UPDATE', FALSE, '2026-08-02 11:00:00'),

    (3, 'SUGGESTION', 3, 'Suggestion Accepted',
     'Your suggestion has been accepted.', 'STATUS_UPDATE', TRUE, '2026-08-03 12:00:00'),

    (4, 'SUGGESTION', 4, 'Suggestion Rejected',
     'Your suggestion has been rejected.', 'STATUS_UPDATE', FALSE, '2026-08-04 13:00:00'),

    (5, 'SUGGESTION', 5, 'Suggestion Implemented',
     'Your suggestion has been implemented.', 'STATUS_UPDATE', TRUE, '2026-08-05 14:00:00'),

    (1, 'SUGGESTION', 6, 'Suggestion Submitted',
     'Your suggestion has been submitted successfully.', 'SUBMISSION', FALSE, '2026-08-06 10:30:00'),

    (2, 'SUGGESTION', 7, 'Suggestion Under Review',
     'Your suggestion is currently under review.', 'STATUS_UPDATE', TRUE, '2026-08-07 11:30:00'),

    (3, 'SUGGESTION', 8, 'Suggestion Accepted',
     'Your suggestion has been accepted.', 'STATUS_UPDATE', FALSE, '2026-08-08 12:30:00'),

    (4, 'SUGGESTION', 9, 'Suggestion Rejected',
     'Your suggestion has been rejected.', 'STATUS_UPDATE', TRUE, '2026-08-09 13:30:00'),

    (5, 'SUGGESTION', 10, 'Suggestion Implemented',
     'Your suggestion has been implemented.', 'STATUS_UPDATE', FALSE, '2026-08-10 14:30:00'),

    (1, 'APPLICATION', 1, 'Application Submitted',
     'Your application has been submitted successfully.', 'SUBMISSION', TRUE, '2026-08-01 10:15:00'),

    (2, 'APPLICATION', 2, 'Application Processing',
     'Your application is being processed.', 'STATUS_UPDATE', FALSE, '2026-08-02 11:15:00'),

    (3, 'APPLICATION', 3, 'Application Under Review',
     'Your application is under review.', 'STATUS_UPDATE', TRUE, '2026-08-03 12:15:00'),

    (4, 'APPLICATION', 4, 'Application Approved',
     'Your application has been approved.', 'STATUS_UPDATE', TRUE, '2026-08-04 15:00:00'),

    (5, 'APPLICATION', 5, 'Application Rejected',
     'Your application has been rejected.', 'STATUS_UPDATE', FALSE, '2026-08-05 15:30:00'),

    (1, 'APPLICATION', 6, 'Application Processing',
     'Your application is being processed.', 'STATUS_UPDATE', FALSE, '2026-08-06 11:00:00'),

    (2, 'APPLICATION', 7, 'Application Submitted',
     'Your application has been submitted successfully.', 'SUBMISSION', TRUE, '2026-08-07 12:00:00'),

    (3, 'APPLICATION', 8, 'Application Approved',
     'Your application has been approved.', 'STATUS_UPDATE', FALSE, '2026-08-08 13:00:00'),

    (4, 'APPLICATION', 9, 'Application Under Review',
     'Your application is under review.', 'STATUS_UPDATE', TRUE, '2026-08-09 14:00:00'),

    (5, 'APPLICATION', 10, 'Application Approved',
     'Your application has been approved.', 'STATUS_UPDATE', FALSE, '2026-08-10 15:00:00');

INSERT INTO attachments
    (user_id, module_type, reference_id, file_name, file_type, file_size, file_data, uploaded_at)
VALUES
    (1, 'GRIEVANCE', 1, 'fee_receipt.pdf', 'application/pdf', 10240, decode('25504446', 'hex'), '2026-08-01 09:35:00'),
    (2, 'GRIEVANCE', 2, 'exam_form.pdf', 'application/pdf', 15360, decode('25504446', 'hex'), '2026-08-02 10:35:00'),
    (3, 'GRIEVANCE', 3, 'schedule.png', 'image/png', 20480, decode('89504E47', 'hex'), '2026-08-03 11:35:00'),
    (4, 'GRIEVANCE', 4, 'classroom.jpg', 'image/jpeg', 25600, decode('FFD8FFE0', 'hex'), '2026-08-04 12:15:00'),

    (1, 'SUGGESTION', 1, 'study_area.pdf', 'application/pdf', 12000, decode('25504446', 'hex'), '2026-08-01 10:05:00'),
    (2, 'SUGGESTION', 2, 'classroom.png', 'image/png', 18500, decode('89504E47', 'hex'), '2026-08-02 11:05:00'),
    (3, 'SUGGESTION', 3, 'library.pdf', 'application/pdf', 22000, decode('25504446', 'hex'), '2026-08-03 12:05:00'),

    (1, 'APPLICATION', 1, 'fee_request.pdf', 'application/pdf', 14000, decode('25504446', 'hex'), '2026-08-01 10:20:00'),
    (4, 'APPLICATION', 4, 'id_card.jpg', 'image/jpeg', 28000, decode('FFD8FFE0', 'hex'), '2026-08-04 15:10:00'),
    (5, 'APPLICATION', 5, 'scholarship.pdf', 'application/pdf', 18000, decode('25504446', 'hex'), '2026-08-05 15:40:00');


INSERT INTO audit_logs
    (user_id, module_type, reference_id, action, ip_address, created_at)
VALUES
    (1, 'GRIEVANCE', 1, 'GRIEVANCE_CREATED', '192.168.1.101', '2026-08-01 09:30:00'),
    (2, 'GRIEVANCE', 2, 'GRIEVANCE_CREATED', '192.168.1.102', '2026-08-02 10:30:00'),
    (3, 'GRIEVANCE', 3, 'GRIEVANCE_ASSIGNED', '192.168.1.103', '2026-08-03 11:30:00'),
    (4, 'GRIEVANCE', 4, 'STATUS_UPDATED', '192.168.1.104', '2026-08-04 16:00:00'),
    (5, 'GRIEVANCE', 5, 'STATUS_UPDATED', '192.168.1.105', '2026-08-05 16:30:00'),
    (1, 'GRIEVANCE', 6, 'GRIEVANCE_CREATED', '192.168.1.101', '2026-08-06 10:00:00'),
    (2, 'GRIEVANCE', 7, 'GRIEVANCE_ASSIGNED', '192.168.1.102', '2026-08-07 11:00:00'),
    (3, 'GRIEVANCE', 8, 'STATUS_UPDATED', '192.168.1.103', '2026-08-08 12:00:00'),
    (4, 'GRIEVANCE', 9, 'GRIEVANCE_RESOLVED', '192.168.1.104', '2026-08-09 13:00:00'),
    (5, 'GRIEVANCE', 10, 'GRIEVANCE_CREATED', '192.168.1.105', '2026-08-10 14:00:00'),

    (1, 'SUGGESTION', 1, 'SUGGESTION_CREATED', '192.168.1.101', '2026-08-01 10:00:00'),
    (2, 'SUGGESTION', 2, 'SUGGESTION_CREATED', '192.168.1.102', '2026-08-02 11:00:00'),
    (3, 'SUGGESTION', 3, 'SUGGESTION_REVIEWED', '192.168.1.103', '2026-08-03 12:00:00'),
    (4, 'SUGGESTION', 4, 'STATUS_UPDATED', '192.168.1.104', '2026-08-04 13:00:00'),
    (5, 'SUGGESTION', 5, 'SUGGESTION_IMPLEMENTED', '192.168.1.105', '2026-08-05 14:00:00'),
    (1, 'SUGGESTION', 6, 'SUGGESTION_CREATED', '192.168.1.101', '2026-08-06 10:30:00'),
    (2, 'SUGGESTION', 7, 'SUGGESTION_REVIEWED', '192.168.1.102', '2026-08-07 11:30:00'),
    (3, 'SUGGESTION', 8, 'STATUS_UPDATED', '192.168.1.103', '2026-08-08 12:30:00'),
    (4, 'SUGGESTION', 9, 'SUGGESTION_REJECTED', '192.168.1.104', '2026-08-09 13:30:00'),
    (5, 'SUGGESTION', 10, 'SUGGESTION_IMPLEMENTED', '192.168.1.105', '2026-08-10 14:30:00'),

    (1, 'APPLICATION', 1, 'APPLICATION_CREATED', '192.168.1.101', '2026-08-01 10:15:00'),
    (2, 'APPLICATION', 2, 'APPLICATION_CREATED', '192.168.1.102', '2026-08-02 11:15:00'),
    (3, 'APPLICATION', 3, 'APPLICATION_REVIEWED', '192.168.1.103', '2026-08-03 12:15:00'),
    (4, 'APPLICATION', 4, 'APPLICATION_APPROVED', '192.168.1.104', '2026-08-04 15:00:00'),
    (5, 'APPLICATION', 5, 'APPLICATION_REJECTED', '192.168.1.105', '2026-08-05 15:30:00'),
    (1, 'APPLICATION', 6, 'APPLICATION_PROCESSING', '192.168.1.101', '2026-08-06 11:00:00'),
    (2, 'APPLICATION', 7, 'APPLICATION_CREATED', '192.168.1.102', '2026-08-07 12:00:00'),
    (3, 'APPLICATION', 8, 'APPLICATION_APPROVED', '192.168.1.103', '2026-08-08 13:00:00'),
    (4, 'APPLICATION', 9, 'APPLICATION_REVIEWED', '192.168.1.104', '2026-08-09 14:00:00'),
    (5, 'APPLICATION', 10, 'APPLICATION_APPROVED', '192.168.1.105', '2026-08-10 15:00:00');
