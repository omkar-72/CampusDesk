// in 1st terminal
php -S localhost:8000
http://localhost:8000/test_connection.php

// in 2nd terminal
psql -U postgres -d campusdesk
\watch 2

// login for student
omkar.gavane@campusdesk.com
omkar@1234

amod.naikare@campusdesk.com
amod@1234

authority@campusdesk.com
Authority@123

// done by ganesh
login@gmail.com

Login@123



step 1:-
database open kar in terminal (psql)

step 2:-
CREATE EXTENSION IF NOT EXISTS pgcrypto;

INSERT INTO users (email, password, role_id)
VALUES (
    'authority@campusdesk.com',
    crypt('authority@123', gen_salt('bf')),
    (SELECT role_id FROM roles WHERE role_name = 'AUTHORITY')
);

step 3:-
login using those email and password in authority dashbord
