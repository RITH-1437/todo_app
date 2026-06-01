<?php
return "CREATE TABLE categories (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);";