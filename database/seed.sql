USE online_exam;

INSERT INTO users (name, email, password_hash, role, status) VALUES
('Examly Administrator', 'admin@examly.local', '$2y$12$hieq8BhWBnBugjghkNZKMOM8DkDog2N5pivu1RZ3CDph27SQIDorC', 'admin', 'active'),
('Dr. Mehta', 'teacher@examly.local', '$2y$12$6Fv3Pu6eq7jHOhPprtGsGubnV3ov.L69vCxMIZreVOec219dN.QGW', 'teacher', 'active'),
('Tushar Sharma', 'student@examly.local', '$2y$12$LMFr.zIiUzbydOdYRvA3MuNNAlNrN0yykczxi9zLDxPFY/dtjfJpS', 'student', 'active');

INSERT INTO classes (class_name, section, academic_year) VALUES
('Grade 10', 'A', '2026-2027');
INSERT INTO subjects (subject_name, description) VALUES
('Mathematics', 'Algebra, arithmetic and problem solving.'),
('Physics', 'Foundations of mechanics and energy.');
INSERT INTO class_students (class_id, student_id) VALUES (1, 3);
INSERT INTO class_teachers (class_id, teacher_id, subject_id) VALUES (1, 2, 1), (1, 2, 2);

INSERT INTO exams (title, description, teacher_id, class_id, start_time, end_time, duration_minutes, total_marks, passing_marks, max_attempts, shuffle_questions, shuffle_options, status, result_publish_at)
VALUES
('Mathematics Practice Exam', 'A short practice assessment covering arithmetic and algebra.', 2, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY), 60, 10, 5, 2, 0, 0, 'published', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('Physics Quiz', 'An upcoming quiz on motion and energy.', 2, 1, DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 3 DAY), 45, 5, 3, 1, 0, 0, 'scheduled', DATE_ADD(NOW(), INTERVAL 3 DAY));

INSERT INTO questions (exam_id, question_text, question_type, marks, negative_marks, position) VALUES
(1, 'What is the value of 2 + 2 × 2?', 'single_choice', 2, 0, 1),
(1, 'Which of the following are prime numbers?', 'multiple_choice', 3, 0, 2),
(1, 'The square root of 81 is 9.', 'true_false', 2, 0, 3),
(1, 'What is 15% of 200?', 'single_choice', 3, 0, 4),
(2, 'Speed is distance divided by time.', 'true_false', 1, 0, 1),
(2, 'What is the SI unit of energy?', 'single_choice', 2, 0, 2),
(2, 'Which is a unit of speed?', 'single_choice', 2, 0, 3);

INSERT INTO question_options (question_id, option_text, is_correct, position) VALUES
(1, '4', 0, 1), (1, '6', 1, 2), (1, '8', 0, 3), (1, '10', 0, 4),
(2, '2', 1, 1), (2, '9', 0, 2), (2, '11', 1, 3), (2, '15', 0, 4),
(3, 'True', 1, 1), (3, 'False', 0, 2),
(4, '20', 0, 1), (4, '25', 0, 2), (4, '30', 1, 3), (4, '35', 0, 4),
(5, 'True', 1, 1), (5, 'False', 0, 2),
(6, 'Joule', 1, 1), (6, 'Watt', 0, 2), (6, 'Newton', 0, 3), (6, 'Pascal', 0, 4),
(7, 'Metres per second', 1, 1), (7, 'Kilograms', 0, 2), (7, 'Newtons', 0, 3);

INSERT INTO notifications (user_id, title, message) VALUES
(3, 'Welcome to Examly', 'Your student account is ready. You are enrolled in Mathematics Practice Exam.');
INSERT INTO audit_logs (user_id, action, entity_type, entity_id) VALUES
(1, 'seeded_demo_data', 'users', 3);
