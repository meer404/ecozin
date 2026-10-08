<?php
declare(strict_types=1);
require_once __DIR__.'/db_connection.php';
ini_set('session.use_strict_mode','1');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
if (isset($_GET['lang']) && in_array($_GET['lang'],['en','ckb','ar'],true)) $_SESSION['lang']=$_GET['lang'];
function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES,'UTF-8'); }
function lang(): string { return $_SESSION['lang'] ?? 'en'; }
function rtl(): bool { return lang()!=='en'; }
function t(string $key): string { static $s; $s ??= require __DIR__.'/lang.php'; return $s[lang()][$key] ?? $s['en'][$key] ?? $key; }
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_user(?string $role = null): array {
    $u=user(); if (!$u || ($role && $u['role']!==$role)) { http_response_code(403); throw new RuntimeException('Please sign in with the correct account role.'); } return $u;
}
function csrf(): void { if (!hash_equals($_SESSION['csrf'],$_POST['csrf'] ?? '')) { http_response_code(403); throw new RuntimeException('Session expired. Refresh and try again.'); } }
// Removes an upload only when it really resolves inside uploads/. Missing or stray paths are ignored.
function drop_upload(?string $relative): void {
    if (!$relative) return;
    $path=realpath(__DIR__.'/'.$relative); $root=realpath(__DIR__.'/uploads');
    if ($path && $root && str_starts_with($path,$root.DIRECTORY_SEPARATOR) && is_file($path)) @unlink($path);
}
function listings(): array { return query('SELECT w.*,u.full_name FROM waste_listings w JOIN users u ON u.id=w.farmer_id ORDER BY w.created_at DESC')->get_result()->fetch_all(MYSQLI_ASSOC); }
function distance(float $a,float $b,float $c,float $d): float { return 6371*2*asin(min(1,sqrt(pow(sin(deg2rad($c-$a)/2),2)+cos(deg2rad($a))*cos(deg2rad($c))*pow(sin(deg2rad($d-$b)/2),2)))); }
function match_listing(array $u,array $l): array {
    $p=json_decode($u['profile_data'] ?? '{}',true) ?: []; $wanted=$p['product'] ?? 'Pomegranate';
    $km=distance((float)($p['lat'] ?? 35.561),(float)($p['lng'] ?? 45.435),(float)$l['location_lat'],(float)$l['location_lng']);
    $score=($wanted===$l['product_type'] ? 65 : 15)+max(0,25-$km/4)+min(10,(float)$l['quantity_tons']*2);
    return ['score'=>round($score),'distance'=>round($km,1),'reason'=>($wanted===$l['product_type']?'Matches your preferred material':'Alternative material').'; '.round($km,1).' km straight-line distance; '.$l['quantity_tons'].' tons available.'];
}
function ai_text(string $prompt): ?string {
    $key=getenv('GEMINI_API_KEY'); if (!$key || !function_exists('curl_init')) return null;
    $model=getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash';
    if (!preg_match('/^[a-zA-Z0-9.\-]+$/',$model)) return null;
    $ch=curl_init('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent');
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-goog-api-key: '.$key],CURLOPT_POSTFIELDS=>json_encode(['contents'=>[['parts'=>[['text'=>$prompt]]]],'generationConfig'=>['maxOutputTokens'=>1200]]),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>20]);
    $raw=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if ($code!==200 || !$raw) return null;
    $data=json_decode($raw,true); return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
}
