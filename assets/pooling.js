document.addEventListener('DOMContentLoaded',()=>{
const data=JSON.parse(document.getElementById('pool-data').textContent),lots=data.lots,origin=data.origin,result=document.getElementById('route-result');
if(!window.L){result.textContent='Map library unavailable. Collection coordinates are listed below.';const f=document.getElementById('map-fallback');f.hidden=false;f.textContent=lots.map(l=>l.name+': '+l.lat+', '+l.lng).join(' | ');return;}
const map=L.map('map').setView([origin.lat,origin.lng],9);L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}).addTo(map).on('tileerror',()=>{result.textContent='Map tiles unavailable. Pins and coordinates remain usable.';});
const icon=(label,factory)=>L.divIcon({className:'',html:'<div class="farm-pin '+(factory?'factory-pin':'')+'">'+label+'</div>',iconSize:[30,30],iconAnchor:[15,15]});
lots.forEach((l,i)=>{const p=document.createElement('div');p.textContent=(i+1)+'. '+l.name+' · '+l.tons+' t';L.marker([l.lat,l.lng],{icon:icon(String(i+1),false)}).addTo(map).bindPopup(p);});
L.marker([origin.lat,origin.lng],{icon:icon('B',true)}).addTo(map).bindPopup('Your facility');
const all=lots.map(l=>[l.lat,l.lng]).concat([[origin.lat,origin.lng]]);map.fitBounds(all,{padding:[40,40]});
let route;
/* OSRM /trip orders the farm stops, then finishes at the buyer's facility. Failure never shows a
   straight line as if it were a road route. */
document.getElementById('pool-route').onclick=async()=>{const b=document.getElementById('pool-route');b.disabled=true;result.textContent='Planning collection route...';if(route)map.removeLayer(route);
 try{const coords=lots.map(l=>l.lng+','+l.lat).concat([origin.lng+','+origin.lat]).join(';');
  const r=await fetch('https://router.project-osrm.org/trip/v1/driving/'+coords+'?roundtrip=false&source=first&destination=last&overview=full&geometries=geojson',{signal:AbortSignal.timeout(15000)});
  if(!r.ok)throw Error();const j=await r.json();if(j.code!=='Ok'||!j.trips||!j.trips.length)throw Error();const trip=j.trips[0];
  route=L.geoJSON(trip.geometry,{style:{color:'#267357',weight:5}}).addTo(map);map.fitBounds(route.getBounds(),{padding:[30,30]});
  const tons=lots.reduce((s,l)=>s+l.tons,0);
  result.textContent=(trip.distance/1000).toFixed(1)+' km by road · '+Math.round(trip.duration/60)+' minutes driving · '+lots.length+' stops collecting '+tons.toFixed(1)+' tons · OSRM demo service, not live traffic';
 }catch(e){result.textContent='Road routing unavailable. All stops are pinned; no road distance estimate is available.';map.fitBounds(all,{padding:[40,40]});}
 finally{b.disabled=false;}};
});
