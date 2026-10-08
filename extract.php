<?php
// Turns a farmer's free sentence ("I have about 3 tons of pomegranate peels in Said Sadiq, dried")
// into the structured fields that save_listing expects.
//
// PHASE 1 (now): extract_listing_local() — deterministic, offline, no API key needed.
// PHASE 2 (next): fill in extract_listing_ai() with a Gemini responseSchema call. The seam is
// extract_listing(): it tries AI first and silently falls back, mirroring the ai_text() null
// contract used everywhere else in this project. Nothing else needs to change.
declare(strict_types=1);

const MATERIAL_WORDS = [
 'Pomegranate'=>['pomegranate','peel','peels','rind','هەنار','توێکڵی هەنار','رمان','قشور الرمان','قشر الرمان'],
 'Walnut'=>['walnut','walnuts','shell','shells','گوێز','توێکڵی گوێز','جوز','قشور الجوز','قشر الجوز'],
 'Olive Pomace'=>['olive','pomace','jift','jeft','oil cake','زەیتوون','جفتی زەیتوون','جفت','زيتون','تفل الزيتون','جفت الزيتون'],
];
const NUMBER_WORDS = [
 'one'=>1,'two'=>2,'three'=>3,'four'=>4,'five'=>5,'six'=>6,'seven'=>7,'eight'=>8,'nine'=>9,'ten'=>10,
 'half'=>0.5,'quarter'=>0.25,'twelve'=>12,'fifteen'=>15,'twenty'=>20,'thirty'=>30,'fifty'=>50,
 'یەک'=>1,'دوو'=>2,'سێ'=>3,'چوار'=>4,'پێنج'=>5,'شەش'=>6,'حەوت'=>7,'هەشت'=>8,'نۆ'=>9,'دە'=>10,'بیست'=>20,'سی'=>30,
 'واحد'=>1,'اثنان'=>2,'ثلاثة'=>3,'اربعة'=>4,'أربعة'=>4,'خمسة'=>5,'ستة'=>6,'سبعة'=>7,'ثمانية'=>8,'تسعة'=>9,'عشرة'=>10,
];
const CONDITION_WORDS = [
 'dry'=>['dry','dried','sun-dried','sundried','وشک','ووشک','جاف','مجفف'],
 'fresh'=>['fresh','wet','moist','damp','تەڕ','تازە','طازج','رطب'],
 'sorted'=>['sorted','clean','cleaned','separated','پاک','جیاکراوە','نظيف','مفروز'],
 'mixed'=>['mixed','unsorted','contaminated','تێکەڵ','مخلوط'],
];

// Normalises Arabic-Indic (٠-٩) and Extended Arabic-Indic (۰-۹) digits to ASCII so the quantity
// regex works regardless of keyboard. Also collapses Arabic/Persian comma and whitespace.
function normalize_text(string $text): string {
    $from=['٠','١','٢','٣','٤','٥','٦','٧','٨','٩','۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','،','٫'];
    $to  =['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9',',','.'];
    return trim(preg_replace('/\s+/u',' ',str_replace($from,$to,$text)));
}

function detect_material(string $haystack): ?string {
    $best=null;$bestLen=0;$bestPos=PHP_INT_MAX;
    foreach (MATERIAL_WORDS as $material=>$words) foreach ($words as $w) {
        $pos=mb_stripos($haystack,$w); if ($pos===false) continue;
        $len=mb_strlen($w);
        if ($len>$bestLen || ($len===$bestLen && $pos<$bestPos)) {$best=$material;$bestLen=$len;$bestPos=$pos;}
    }
    return $best;
}

// Returns tons, converting kg when the farmer speaks in kilos. Null when no quantity is stated.
function detect_quantity(string $haystack): ?float {
    $kg='kg|kilo|kilos|kilogram|kilograms|کیلۆ|کیلو|كيلو|كغم|كجم';
    $ton='tons|ton|tonne|tonnes|t\b|تۆن|تن|طن|اطنان|أطنان';
    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:'.$kg.')/iu',$haystack,$m)) return round(((float)$m[1])/1000,3);
    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:'.$ton.')/iu',$haystack,$m)) return (float)$m[1];
    foreach (NUMBER_WORDS as $word=>$value) if (preg_match('/(?<![\p{L}])'.preg_quote((string)$word,'/').'\s*(?:'.$ton.')/iu',$haystack)) return (float)$value;
    if (preg_match('/(\d+(?:\.\d+)?)/u',$haystack,$m)) {$n=(float)$m[1]; if ($n>0 && $n<=100000) return $n;}
    return null;
}

// Matches the longest alias first so "Said Sadiq" beats a stray "Sadiq".
function resolve_place(string $haystack): ?array {
    $places=require __DIR__.'/gazetteer.php';$best=null;$bestLen=0;
    foreach ($places as $place) foreach ($place['aliases'] as $alias) {
        if (mb_strlen($alias)>$bestLen && mb_stripos($haystack,$alias)!==false) {$best=$place;$bestLen=mb_strlen($alias);}
    }
    return $best;
}

function detect_conditions(string $haystack): array {
    $found=[];
    foreach (CONDITION_WORDS as $label=>$words) foreach ($words as $w) if (mb_stripos($haystack,$w)!==false) {$found[]=$label;break;}
    return $found;
}

function extract_listing_local(string $text): array {
    $clean=normalize_text($text);$material=detect_material($clean);$qty=detect_quantity($clean);$place=resolve_place($clean);$conditions=detect_conditions($clean);
    $missing=[];
    if (!$material) $missing[]='material';
    if (!$qty) $missing[]='quantity';
    if (!$place) $missing[]='location';
    return [
     'product_type'=>$material,'quantity_tons'=>$qty,
     'place'=>$place['name'] ?? null,'location_lat'=>$place['lat'] ?? null,'location_lng'=>$place['lng'] ?? null,
     'conditions'=>$conditions,'description'=>$clean,'missing'=>$missing,
     'source'=>'Local text reading · not live AI',
    ];
}

// PHASE 2 PLACEHOLDER. Implement with ai_text() + a JSON responseSchema prompt, then return the
// same array shape as extract_listing_local() (or null to fall back). Keep 'source' truthful.
function extract_listing_ai(string $text): ?array {
    return null;
}

function extract_listing(string $text): array {
    $ai=extract_listing_ai($text);
    return $ai ?: extract_listing_local($text);
}
