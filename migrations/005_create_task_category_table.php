<?php

return "

CREATE TABLE task_categories (

    task_id INT,

    category_id INT,

    PRIMARY KEY(task_id, category_id),

    FOREIGN KEY (task_id)
    REFERENCES tasks(id)
    ON DELETE CASCADE,

    FOREIGN KEY (category_id)
    REFERENCES categories(id)
    ON DELETE CASCADE

)

";