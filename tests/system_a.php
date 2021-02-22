<?php
file_put_contents(
    __DIR__ . '/system_a.txt',
    file_get_contents("php://input"),
    FILE_APPEND
);
