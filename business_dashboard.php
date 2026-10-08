<?php
require __DIR__.'/layout.php';
if(!user() || user()['role']!=='business') {header('Location: index.php?view=login');exit;}
$u=user(); page_start('business_dashboard.php',t('nav_matches'));
$profile=json_decode($u['profile_data'] ?? '{}',true) ?: [];
$items=array_values(array_filter(listings(),fn($l)=>$l['status']==='available'));
usort($items,fn($a,$b)=>match_listing($u,$b)['score']<=>match_listing($u,$a)['score']);
$scores=array_map(fn($l)=>match_listing($u,$l)['score'],$items);
$avg=$scores?round(array_sum($scores)/count($scores)):0;
$tons=array_sum(array_map(fn($l)=>(float)$l['quantity_tons'],$items));
// Farmers this buyer follows, with their currently available volume.
$following=query('SELECT s.farmer_id,u.full_name,s.created_at,COUNT(w.id) AS lots,COALESCE(SUM(w.quantity_tons),0) AS tons FROM subscriptions s JOIN users u ON u.id=s.farmer_id LEFT JOIN waste_listings w ON w.farmer_id=s.farmer_id AND w.status=? WHERE s.business_id=? GROUP BY s.farmer_id,u.full_name,s.created_at ORDER BY tons DESC','si',['available',$u['id']])->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<div class="page-heading"><div><div class="eyebrow"><?=esc(t('buy_eyebrow'))?></div><h1><?=esc(t('buy_title'))?></h1><p><?=esc(t('buy_lead'))?></p></div><div class="heading-actions"><a class="btn btn-light" href="pooling.php"><i data-lucide="layers"></i><?=esc(t('buy_open_pool'))?></a><a href="ai_knowledge_base.php" class="btn btn-green"><i data-lucide="sparkles"></i><?=esc(t('nav_knowledge'))?></a></div></div>
<div class="stats-strip"><div><span class="stat-icon green"><i data-lucide="target"></i></span><div><strong><?=count($items)?></strong><span><?=esc(t('buy_matched'))?></span></div></div><div><span class="stat-icon peach"><i data-lucide="package-open"></i></span><div><strong><?=number_format($tons,1)?><small> t</small></strong><span><?=esc(t('buy_reachable'))?></span></div></div><div><span class="stat-icon lilac"><i data-lucide="user-check"></i></span><div><strong><?=count($following)?></strong><span><?=esc(t('buy_following'))?></span></div></div><div><span class="stat-icon blue"><i data-lucide="gauge"></i></span><div><strong><?=$avg?><small>%</small></strong><span><?=esc(t('buy_avg'))?></span></div></div></div>
<div class="explain-band"><i data-lucide="sliders-horizontal"></i><div><b>Transparent matching</b><p>Your profile: <?=esc($profile['industry'] ?? 'Local business')?><?= isset($profile['product'])?' · preferred material '.esc($profile['product']):'' ?>. Scores use a deterministic suitability formula — 65 points for your preferred material, up to 25 for proximity, up to 10 for volume. This is not a lab assessment or a live AI prediction.</p></div></div>
<div class="section-heading"><h2><?=esc(t('buy_follow_list'))?> <span class="count"><?=count($following)?></span></h2></div>
<?php if(!$following): ?><p class="muted"><?=esc(t('buy_no_following'))?></p><?php else: ?>
<div class="table-responsive"><table class="table"><thead><tr><th><?=esc(t('farmer'))?></th><th><?=esc(t('sup_lots'))?></th><th><?=esc(t('sup_available'))?></th><th><?=esc(t('adm_joined'))?></th><th><?=esc(t('actions'))?></th></tr></thead><tbody><?php foreach($following as $f): ?><tr><td><b><a href="profile.php?id=<?=(int)$f['farmer_id']?>"><?=esc($f['full_name'])?></a></b></td><td><?=(int)$f['lots']?></td><td><?=number_format((float)$f['tons'],1)?> t</td><td class="muted"><?=esc(substr((string)$f['created_at'],0,10))?></td><td class="row-actions"><a class="btn btn-sm btn-light" href="chat.php?receiver=<?=(int)$f['farmer_id']?>"><i data-lucide="message-circle"></i>Message</a><button class="icon-btn danger unfollow" data-id="<?=(int)$f['farmer_id']?>" data-label="<?=esc($f['full_name'])?>" title="<?=esc(t('buy_unfollow'))?>" aria-label="<?=esc(t('buy_unfollow'))?>"><i data-lucide="user-minus"></i></button></td></tr><?php endforeach ?></tbody></table></div>
<?php endif ?>
<div class="section-heading mt-4"><h2><?=esc(t('buy_shortlist'))?> <span class="count"><?=count($items)?></span></h2><div class="muted"><span class="live-dot"></span> Ranked by material, proximity and volume</div></div>
<?php if(!$items): ?><p class="muted"><?=esc(t('none_yet'))?></p><?php else: ?><div class="listing-grid"><?php foreach($items as $l) listing_card($l,match_listing($u,$l)); ?></div><?php endif ?>
<section class="buyer-brief"><div class="section-heading"><h2><?=esc(t('buy_brief'))?></h2><button id="recommendations" class="btn btn-green"><i data-lucide="sparkles"></i><?=esc(t('buy_generate'))?></button></div><span id="recommendation-source" class="pilot-badge">READY</span><p id="recommendation-text" class="mt-3">A buying brief based on your preferences and currently available inventory.</p></section>
<?php page_end(); ?>
