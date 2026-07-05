<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="results_template.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['reg_number','course_code','course_title','score','grade','semester','session']);
fputcsv($out, ['AMUP/2024/001','CSC101','Intro to Computing','78','B','First','2024/2025']);
fclose($out);
exit;