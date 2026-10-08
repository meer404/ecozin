<?php
// CLI only: php setup.php. Creates this application's tables and adds demo data once.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/db_connection.php';
$server=new mysqli(getenv('DB_HOST') ?: '127.0.0.1',getenv('DB_USER') ?: 'root',getenv('DB_PASSWORD') ?: '', '',(int)(getenv('DB_PORT') ?: 3306));
$server->set_charset('utf8mb4');
$server->multi_query(file_get_contents(__DIR__.'/schema.sql'));
do {if($r=$server->store_result())$r->free();} while($server->more_results() && $server->next_result());
$users=[['Ahmad Mahmood','farmer@ecozin.test','farmer',['lat'=>35.36,'lng'=>45.72]],['Shilan Organic Studio','buyer@ecozin.test','business',['industry'=>'Organic cosmetics','product'=>'Pomegranate','lat'=>35.561,'lng'=>45.435]],['Darya Abdullah','darya@ecozin.test','farmer',[]],['Roj Olive Cooperative','roj@ecozin.test','farmer',[]],['Platform Administrator','admin@ecozin.test','admin',[]]];
foreach($users as [$name,$email,$role,$profile]) query('INSERT IGNORE INTO users(full_name,email,password,role,profile_data) VALUES(?,?,?,?,?)','sssss',[$name,$email,password_hash('EcozinDemo2026!',PASSWORD_DEFAULT),$role,json_encode($profile)]);
if((int)query('SELECT COUNT(*) AS n FROM waste_listings')->get_result()->fetch_assoc()['n']===0) {
 $samples=[['farmer@ecozin.test','Pomegranate',5.2,75000,35.36,45.72,'Sun-dried peels from this season. Sorted and ready for buyer sampling.'],['darya@ecozin.test','Walnut',3.8,50000,35.79,45.62,'Clean, hard walnut shells. Uncrushed; bulk collection available.'],['roj@ecozin.test','Olive Pomace',12.5,35000,35.18,45.98,'Fresh milling residue. Moisture testing and drying terms to be agreed.'],['farmer@ecozin.test','Pomegranate',2.1,65000,35.52,45.32,'Separated peel batch. Available for sample inspection before pickup.']];
 foreach($samples as [$email,$product,$q,$price,$lat,$lng,$desc]) {$id=query('SELECT id FROM users WHERE email=?','s',[$email])->get_result()->fetch_assoc()['id'];query('INSERT INTO waste_listings(farmer_id,product_type,quantity_tons,price_per_ton,location_lat,location_lng,description) VALUES(?,?,?,?,?,?,?)','isdddds',[$id,$product,$q,$price,$lat,$lng,$desc]);}
}
echo "EcoZin ready. Demo password: EcozinDemo2026!\n";
