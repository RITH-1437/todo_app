<?php

return "

ALTER TABLE tasks

ADD priority ENUM('low', 'medium', 'high')

DEFAULT 'medium'

";