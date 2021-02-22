<?php
file_put_contents(__DIR__ . '/system_b.txt', http_build_query($_GET), FILE_APPEND);
