<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../db_connection.php';
$ids=query('SELECT id FROM users WHERE email LIKE ? AND full_name=?','ss',['automation-%@ecozin.test','Automation farmer'])->get_result()->fetch_all(MYSQLI_ASSOC);
foreach($ids as $row){$id=(int)$row['id'];$photos=query('SELECT photo_url FROM waste_listings WHERE farmer_id=?','i',[$id])->get_result()->fetch_all(MYSQLI_ASSOC);foreach($photos as $photo){if($photo['photo_url'])unlink(__DIR__.'/../'.$photo['photo_url']);}query('DELETE FROM messages WHERE sender_id=? OR receiver_id=?','ii',[$id,$id]);query('DELETE FROM subscriptions WHERE business_id=? OR farmer_id=?','ii',[$id,$id]);query('DELETE FROM matches_and_ads WHERE business_id=? OR farmer_id=?','ii',[$id,$id]);query('DELETE FROM waste_listings WHERE farmer_id=?','i',[$id]);query('DELETE FROM users WHERE id=?','i',[$id]);}
$rows=query('SELECT media_url FROM messages WHERE message_text=?','s',['Automation attachment'])->get_result()->fetch_all(MYSQLI_ASSOC);foreach($rows as $r){if($r['media_url'])unlink(__DIR__.'/../'.$r['media_url']);}
query('DELETE FROM messages WHERE message_text IN (?,?)','ss',['Automation attachment','Demo quality check: can you share a sample?']);
echo "Automation test records cleaned.\n";
