<?php
require __DIR__.'/bootstrap.php';
try {
 $row=query('SELECT photo_url FROM waste_listings WHERE id=?','i',[(int)($_GET['id'] ?? 0)])->get_result()->fetch_assoc();
 $path=$row && $row['photo_url']?realpath(__DIR__.'/'.$row['photo_url']):false;$root=realpath(__DIR__.'/uploads');
 if(!$path || !$root || !str_starts_with($path,$root.DIRECTORY_SEPARATOR)){http_response_code(404);exit;}
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)){http_response_code(404);exit;}
 header('Content-Type: '.$mime);header('Content-Length: '.filesize($path));header('Cache-Control: public, max-age=300');readfile($path);
}catch(Throwable $e){http_response_code(404);}
