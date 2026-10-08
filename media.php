<?php
require __DIR__.'/bootstrap.php';
try {
 $u=require_user(); $m=query('SELECT media_url FROM messages WHERE id=? AND (sender_id=? OR receiver_id=?)','iii',[(int)($_GET['id'] ?? 0),$u['id'],$u['id']])->get_result()->fetch_assoc();
 if (!$m || !$m['media_url']) {http_response_code(404);exit;}
 $path=realpath(__DIR__.'/'.$m['media_url']); $root=realpath(__DIR__.'/uploads');
 if (!$path || !$root || !str_starts_with($path,$root.DIRECTORY_SEPARATOR)) {http_response_code(404);exit;}
 header('Content-Type: '.(new finfo(FILEINFO_MIME_TYPE))->file($path));
 header('Content-Length: '.filesize($path));header('Cache-Control: private, no-store');
 header('Content-Disposition: inline; filename="attachment.'.pathinfo($path,PATHINFO_EXTENSION).'"'); readfile($path);
} catch(Throwable $e) {http_response_code(403);}
