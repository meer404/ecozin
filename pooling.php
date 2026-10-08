<?php
require __DIR__.'/layout.php';
if(!user() || user()['role']!=='business') {header('Location: index.php?view=login');exit;}
$u=user(); page_start('pooling.php',t('nav_pool'));
$profile=json_decode($u['profile_data'] ?? '{}',true) ?: [];
$material=in_array($_GET['material'] ?? '',['Pomegranate','Walnut','Olive Pomace'],true)?$_GET['material']:($profile['product'] ?? 'Pomegranate');
$target=min(100000,max(0.1,(float)($_GET['target'] ?? 20)));
$radius=min(500,max(1,(float)($_GET['radius'] ?? 60)));
$originLat=(float)($_GET['lat'] ?? $profile['lat'] ?? 35.561); $originLng=(float)($_GET['lng'] ?? $profile['lng'] ?? 45.435);
if(abs($originLat)>90) $originLat=35.561; if(abs($originLng)>180) $originLng=45.435;
// Candidate lots: available, matching material, inside the radius, nearest first.
$candidates=[];
foreach(listings() as $l) {
 if($l['status']!=='available' || $l['product_type']!==$material) continue;
 $km=distance($originLat,$originLng,(float)$l['location_lat'],(float)$l['location_lng']);
 if($km>$radius) continue;
 $l['km']=round($km,1); $candidates[]=$l;
}
usort($candidates,fn($a,$b)=>$a['km']<=>$b['km']);
// Accumulate nearest-first until the buyer's target is met; keep the running total for display.
$pool=[]; $total=0.0;
foreach($candidates as $l) { if($total>=$target) break; $pool[]=$l; $total+=(float)$l['quantity_tons']; }
$farms=count(array_unique(array_column($pool,'farmer_id')));
$value=array_sum(array_map(fn($l)=>(float)$l['quantity_tons']*(float)$l['price_per_ton'],$pool));
// Suggested collection point: the pooled lot closest to the geographic centre of the pooled lots.
$point=null;
if($pool) {
 $cLat=array_sum(array_map(fn($l)=>(float)$l['location_lat'],$pool))/count($pool);
 $cLng=array_sum(array_map(fn($l)=>(float)$l['location_lng'],$pool))/count($pool);
 usort($pool,fn($a,$b)=>distance($cLat,$cLng,(float)$a['location_lat'],(float)$a['location_lng'])<=>distance($cLat,$cLng,(float)$b['location_lat'],(float)$b['location_lng']));
 $point=$pool[0];
 usort($pool,fn($a,$b)=>$a['km']<=>$b['km']);
}
?>
<div class="page-heading"><div><div class="eyebrow"><?=esc(t('pool_eyebrow'))?></div><h1><?=esc(t('pool_title'))?></h1><p><?=esc(t('pool_lead'))?></p></div></div>
<form class="map-controls" method="get"><label><?=esc(t('material'))?><select name="material" class="form-select"><?php foreach(['Pomegranate','Walnut','Olive Pomace'] as $m): ?><option <?=$material===$m?'selected':''?>><?=esc($m)?></option><?php endforeach ?></select></label><label><?=esc(t('pool_target'))?><input class="form-control" type="number" name="target" min="0.1" step="0.1" value="<?=esc($target)?>"></label><label><?=esc(t('pool_radius'))?><input class="form-control" type="number" name="radius" min="1" max="500" step="1" value="<?=esc($radius)?>"></label><label><?=esc(t('latitude'))?><input class="form-control" type="number" name="lat" step="any" min="-90" max="90" value="<?=esc($originLat)?>"></label><label><?=esc(t('longitude'))?><input class="form-control" type="number" name="lng" step="any" min="-180" max="180" value="<?=esc($originLng)?>"></label><button class="btn btn-green"><i data-lucide="layers"></i><?=esc(t('pool_build'))?></button></form>
<?php if(!$pool): ?><div class="alert alert-warning"><?=esc(t('pool_none'))?></div><?php else: ?>
<?php if($total<$target): ?><div class="alert alert-warning"><?=esc(t('pool_short'))?></div><?php endif ?>
<div class="stats-strip"><div><span class="stat-icon green"><i data-lucide="package-open"></i></span><div><strong><?=number_format($total,1)?><small> / <?=number_format($target,1)?> tons</small></strong><span><?=esc(t('pool_total'))?></span></div></div><div><span class="stat-icon peach"><i data-lucide="users"></i></span><div><strong><?=$farms?></strong><span><?=esc(t('pool_farms'))?></span></div></div><div><span class="stat-icon lilac"><i data-lucide="coins"></i></span><div><strong><?=number_format($value)?><small> IQD</small></strong><span>Indicative lot value</span></div></div><div><span class="stat-icon blue"><i data-lucide="map-pin"></i></span><div><strong><?=esc($point['full_name'])?></strong><span><?=esc(t('pool_point'))?></span></div></div></div>
<div class="pool-progress" role="img" aria-label="<?=esc(number_format($total,1).' of '.number_format($target,1).' tons pooled')?>"><span style="width:<?=min(100,round($total/$target*100))?>%"></span></div>
<div class="section-heading"><h2><?=esc(t('pool_total'))?> <span class="count"><?=count($pool)?></span></h2><button id="pool-route" class="btn btn-green"><i data-lucide="route"></i><?=esc(t('pool_route'))?></button></div>
<div id="route-result" class="route-result" role="status">Collection order is sequenced by road routing when you plan the route.</div>
<div class="table-responsive"><table class="table"><thead><tr><th>#</th><th>Farmer</th><th><?=esc(t('quantity'))?></th><th><?=esc(t('price'))?></th><th>Distance</th><th></th></tr></thead><tbody><?php foreach($pool as $i=>$l): ?><tr><td><?=$i+1?></td><td><b><?=esc($l['full_name'])?></b><?php if($point && $l['id']===$point['id']): ?> <span class="pilot-badge">COLLECTION POINT</span><?php endif ?><small class="d-block muted"><?=esc($l['description'])?></small></td><td><?=number_format((float)$l['quantity_tons'],3)?> t</td><td><?=number_format((float)$l['price_per_ton'])?></td><td><?=esc($l['km'])?> km</td><td><a class="icon-btn" title="Message farmer" aria-label="Message farmer" href="chat.php?receiver=<?=(int)$l['farmer_id']?>"><i data-lucide="message-circle"></i></a></td></tr><?php endforeach ?></tbody></table></div>
<div id="map"></div><div id="map-fallback" hidden></div>
<script type="application/json" id="pool-data"><?=json_encode(['lots'=>array_map(fn($l)=>['id'=>(int)$l['id'],'name'=>$l['full_name'],'tons'=>(float)$l['quantity_tons'],'lat'=>(float)$l['location_lat'],'lng'=>(float)$l['location_lng']],$pool),'origin'=>['lat'=>$originLat,'lng'=>$originLng]],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?></script>
<script src="assets/leaflet.js"></script><script src="assets/pooling.js"></script>
<?php endif; page_end(); ?>
