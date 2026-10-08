<?php
require __DIR__.'/layout.php';
$me=user();
$viewId=(int)($_GET['id'] ?? 0);
// No id (or your own id) = your editable profile. Any other id = that person's public profile.
$own=!$viewId || ($me && $viewId===(int)$me['id']);
if ($own && !$me) {header('Location: index.php?view=login');exit;}
if ($own) { $p=$me; }
else {
 // email is selected for the admin-only line below; never rendered to anyone else. Password is never selected.
 $p=query('SELECT id,full_name,role,profile_data,created_at,email FROM users WHERE id=?','i',[$viewId])->get_result()->fetch_assoc();
 if (!$p) {http_response_code(404); page_start('profile.php',t('prof_title')); echo '<div class="alert alert-warning">'.esc(t('prof_missing')).'</div>'; page_end(); exit;}
}
$data=json_decode($p['profile_data'] ?? '{}',true) ?: [];
$isAdmin=$me && $me['role']==='admin';
page_start('profile.php',$own?t('prof_title'):$p['full_name']);
$places=require __DIR__.'/gazetteer.php';
// Public-facing numbers. A farmer's lots are already public through the marketplace and map.
$lots=[];$tons=0.0;$value=0.0;
if ($p['role']==='farmer') {
 $lots=query('SELECT w.*,u.full_name FROM waste_listings w JOIN users u ON u.id=w.farmer_id WHERE w.farmer_id=? AND w.status=? ORDER BY w.created_at DESC','is',[(int)$p['id'],'available'])->get_result()->fetch_all(MYSQLI_ASSOC);
 $tons=array_sum(array_map(fn($l)=>(float)$l['quantity_tons'],$lots));
 $value=array_sum(array_map(fn($l)=>(float)$l['quantity_tons']*(float)$l['price_per_ton'],$lots));
}
$follows=$p['role']==='business'?(int)query('SELECT COUNT(*) AS n FROM subscriptions WHERE business_id=?','i',[(int)$p['id']])->get_result()->fetch_assoc()['n']:0;
?>
<div class="profile-head"><span class="profile-avatar"><?=esc(mb_strtoupper(mb_substr($p['full_name'],0,2)))?></span><div><div class="eyebrow"><?=esc($own?t('prof_eyebrow'):t('prof_public'))?></div><h1><?=esc($p['full_name'])?><span>.</span></h1><p class="muted"><span class="pilot-badge"><?=esc(mb_strtoupper($p['role']))?></span> <?=esc(t('adm_joined'))?> <?=esc(substr((string)$p['created_at'],0,10))?><?php if($isAdmin && !$own && isset($p['email'])): ?> · <?=esc($p['email'])?><?php endif ?></p></div><div class="heading-actions"><?php if(!$own && $me && $me['role']!=='admin'): ?><a class="btn btn-green" href="chat.php?receiver=<?=(int)$p['id']?>"><i data-lucide="message-circle"></i><?=esc(t('prof_message'))?></a><?php endif ?><?php if($own): ?><a class="btn btn-light" href="profile.php?id=<?=(int)$p['id']?>"><i data-lucide="eye"></i><?=esc(t('prof_view_public'))?></a><?php endif ?></div></div>

<?php if($p['role']==='farmer'): ?>
<div class="stats-strip"><div><span class="stat-icon green"><i data-lucide="package"></i></span><div><strong><?=count($lots)?></strong><span><?=esc(t('sup_available'))?></span></div></div><div><span class="stat-icon peach"><i data-lucide="package-open"></i></span><div><strong><?=number_format($tons,1)?><small> t</small></strong><span><?=esc(t('pool_total'))?></span></div></div><div><span class="stat-icon lilac"><i data-lucide="coins"></i></span><div><strong><?=number_format($value)?><small> IQD</small></strong><span><?=esc(t('sup_value'))?></span></div></div><div><span class="stat-icon blue"><i data-lucide="map-pin"></i></span><div><strong>Slemani</strong><span><?=esc(t('prof_region'))?></span></div></div></div>
<?php elseif($p['role']==='business'): ?>
<div class="stats-strip"><div><span class="stat-icon green"><i data-lucide="factory"></i></span><div><strong><?=esc($data['industry'] ?: '—')?></strong><span><?=esc(t('prof_industry'))?></span></div></div><div><span class="stat-icon peach"><i data-lucide="sparkles"></i></span><div><strong><?=esc($data['product'] ?? '—')?></strong><span><?=esc(t('prof_preferred'))?></span></div></div><div><span class="stat-icon lilac"><i data-lucide="user-check"></i></span><div><strong><?=$follows?></strong><span><?=esc(t('buy_following'))?></span></div></div><div><span class="stat-icon blue"><i data-lucide="map-pin"></i></span><div><strong>Slemani</strong><span><?=esc(t('prof_region'))?></span></div></div></div>
<?php endif ?>

<?php if($own): ?>
<div class="profile-layout"><section class="profile-card"><div class="section-heading"><h2><?=esc(t('prof_details'))?></h2></div>
<form id="profile-form"><input type="hidden" name="action" value="save_profile"><label><?=esc(t('prof_name'))?><input class="form-control" name="full_name" maxlength="120" required value="<?=esc($p['full_name'])?>"></label><label><?=esc(t('prof_email'))?><input class="form-control" value="<?=esc($p['email'] ?? '')?>" disabled><small class="muted"><?=esc(t('prof_email_fixed'))?></small></label><label><?=esc($p['role']==='farmer'?t('prof_farm_type'):t('prof_industry'))?><input class="form-control" name="industry" maxlength="200" value="<?=esc($data['industry'] ?? '')?>" placeholder="<?=esc($p['role']==='farmer'?'e.g. pomegranate orchard':'e.g. organic cosmetics')?>"></label><label><?=esc($p['role']==='farmer'?t('prof_main_material'):t('prof_preferred'))?><select class="form-select" name="product"><?php foreach(['Pomegranate','Walnut','Olive Pomace'] as $m): ?><option <?=($data['product'] ?? '')===$m?'selected':''?>><?=esc($m)?></option><?php endforeach ?></select><?php if($p['role']==='business'): ?><small class="muted"><?=esc(t('prof_preferred_hint'))?></small><?php endif ?></label>
<label><?=esc(t('prof_district'))?><select class="form-select" id="profile-place"><option value=""><?=esc(t('prof_district_pick'))?></option><?php foreach($places as $pl): ?><option value="<?=esc($pl['lat'].','.$pl['lng'])?>"><?=esc($pl['name'])?></option><?php endforeach ?></select></label>
<div class="form-pair"><label><?=esc(t('latitude'))?><input class="form-control" name="lat" type="number" step="any" min="-90" max="90" required value="<?=esc($data['lat'] ?? 35.561)?>"></label><label><?=esc(t('longitude'))?><input class="form-control" name="lng" type="number" step="any" min="-180" max="180" required value="<?=esc($data['lng'] ?? 45.435)?>"></label></div>
<button type="button" id="profile-locate" class="btn btn-light btn-sm"><i data-lucide="locate-fixed"></i><?=esc(t('sup_locate'))?></button>
<p class="quick-hint"><i data-lucide="info"></i><?=esc($p['role']==='business'?t('prof_coord_buyer'):t('prof_coord_farmer'))?></p>
<button class="btn btn-green w-100"><i data-lucide="check"></i><?=esc(t('prof_save'))?></button></form></section>

<section class="profile-card"><div class="section-heading"><h2><?=esc(t('prof_password'))?></h2></div>
<form id="password-form"><input type="hidden" name="action" value="change_password"><label><?=esc(t('prof_current'))?><input class="form-control" type="password" name="current_password" autocomplete="current-password" required></label><label><?=esc(t('prof_new'))?><input class="form-control" type="password" name="new_password" minlength="10" autocomplete="new-password" required><small class="muted"><?=esc(t('prof_new_hint'))?></small></label><button class="btn btn-green w-100"><i data-lucide="key-round"></i><?=esc(t('prof_change'))?></button></form>
<div class="section-heading mt-4"><h2><?=esc(t('prof_account'))?></h2></div>
<dl class="profile-meta"><dt><?=esc(t('adm_role'))?></dt><dd><?=esc(ucfirst($p['role']))?></dd><dt><?=esc(t('adm_joined'))?></dt><dd><?=esc(substr((string)$p['created_at'],0,10))?></dd><dt>ID</dt><dd>#<?=(int)$p['id']?></dd></dl>
<p class="muted"><?=esc(t('prof_role_fixed'))?></p></section></div>
<?php endif ?>

<?php if($p['role']==='farmer'): ?>
<div class="section-heading mt-4"><h2><?=esc($own?t('prof_my_lots'):t('prof_their_lots'))?> <span class="count"><?=count($lots)?></span></h2><?php if($own): ?><a class="btn btn-sm btn-light" href="farmer_dashboard.php"><i data-lucide="package"></i><?=esc(t('nav_inventory'))?></a><?php endif ?></div>
<?php if(!$lots): ?><p class="muted"><?=esc(t('prof_no_lots'))?></p><?php else: ?><div class="listing-grid"><?php foreach($lots as $l) listing_card($l); ?></div><?php endif ?>
<?php endif ?>
<?php page_end(); ?>
