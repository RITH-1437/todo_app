<?php 
return "CREATE TABLE comments (

    id INT AUTO_INCREMENT PRIMARY KEY,

    task_id INT,

    message TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (task_id)
    REFERENCES tasks(id)
    ON DELETE CASCADE

);";