<?php

$password = password_hash(
    '123456',
    PASSWORD_DEFAULT
);

return "

INSERT INTO users
(name, email, password)

VALUES

(
    'Nairith',
    'nairith@example.com',
    '$password'
),

(
    'John Doe',
    'john@example.com',
    '$password'
)

";
