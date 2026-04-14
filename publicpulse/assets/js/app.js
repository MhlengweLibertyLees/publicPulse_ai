/**
 * PublicPulse AI — Master JavaScript v2.0
 */
'use strict';

if (typeof Chart !== 'undefined') {
  Chart.defaults.font.family     = "'Plus Jakarta Sans', sans-serif";
  Chart.defaults.font.size       = 12;
  Chart.defaults.color           = '#64748b';
  Chart.defaults.borderColor     = '#e2e8f0';
  Chart.defaults.maintainAspectRatio = true;
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.legend.labels.padding       = 14;
  Chart.defaults.plugins.legend.labels.boxWidth      = 8;
  Chart.defaults.plugins.tooltip.backgroundColor     = '#0f172a';
  Chart.defaults.plugins.tooltip.borderColor         = '#1e293b';
  Chart.defaults.plugins.tooltip.borderWidth         = 1;
  Chart.defaults.plugins.tooltip.padding             = 10;
  Chart.defaults.plugins.tooltip.titleColor          = '#f1f5f9';
  Chart.defaults.plugins.tooltip.bodyColor           = '#94a3b8';
  Chart.defaults.plugins.tooltip.cornerRadius        = 8;
}

const C={blue:'#3b82f6',teal:'#0891b2',green:'#059669',yellow:'#d97706',red:'#dc2626',purple:'#7c3aed',orange:'#ea580c',gray:'#94a3b8'};
const PALETTE=[C.blue,C.teal,C.green,C.yellow,C.red,C.purple,C.orange,'#db2777','#16a34a','#0284c7'];

function hexToRgba(hex,a){if(!hex||!hex.startsWith('#'))return`rgba(59,130,246,${a})`;const r=parseInt(hex.slice(1,3),16),g=parseInt(hex.slice(3,5),16),b=parseInt(hex.slice(5,7),16);return`rgba(${r},${g},${b},${a})`;}

function renderDoughnut(id,data){
  const ctx=document.getElementById(id);if(!ctx)return null;
  return new Chart(ctx,{type:'doughnut',data:{labels:data.labels,datasets:[{data:data.values,backgroundColor:data.colors||PALETTE,borderWidth:3,borderColor:'#ffffff',hoverOffset:5}]},options:{cutout:'68%',plugins:{legend:{position:'bottom',labels:{padding:12}},tooltip:{callbacks:{label(c){const t=c.dataset.data.reduce((a,b)=>a+b,0);return` ${c.label}: ${c.raw} (${((c.raw/t)*100).toFixed(1)}%)`;}}}}}}); 
}

function renderBar(id,labels,datasets,opts={}){
  const ctx=document.getElementById(id);if(!ctx)return null;
  return new Chart(ctx,{type:'bar',data:{labels,datasets:datasets.map((d,i)=>({label:d.label,data:d.data,backgroundColor:d.color||PALETTE[i]||C.blue,borderRadius:5,borderSkipped:false,maxBarThickness:50,...(d.extra||{})}))},options:{scales:{x:{grid:{display:false},ticks:{maxRotation:40}},y:{grid:{color:'#f1f5f9'},beginAtZero:true}},plugins:{legend:{display:datasets.length>1}},...opts}});
}

function renderLine(id,labels,datasets,opts={}){
  const ctx=document.getElementById(id);if(!ctx)return null;
  return new Chart(ctx,{type:'line',data:{labels,datasets:datasets.map((d,i)=>{const col=d.color||PALETTE[i]||C.blue;return{label:d.label,data:d.data,borderColor:col,backgroundColor:d.fill!==false?hexToRgba(col,0.08):'transparent',tension:0.4,fill:d.fill!==false,pointRadius:3,pointHoverRadius:5,pointBackgroundColor:col,pointBorderColor:'#fff',pointBorderWidth:2,borderWidth:2.5,...(d.extra||{})}})},options:{scales:{x:{grid:{display:false}},y:{grid:{color:'#f1f5f9'},beginAtZero:true}},plugins:{legend:{display:datasets.length>1}},interaction:{mode:'index',intersect:false},...opts}});
}

function renderHBar(id,labels,data,color){
  const ctx=document.getElementById(id);if(!ctx)return null;
  return new Chart(ctx,{type:'bar',data:{labels,datasets:[{data,backgroundColor:color||C.blue,borderRadius:4,borderSkipped:false}]},options:{indexAxis:'y',scales:{x:{grid:{color:'#f1f5f9'},beginAtZero:true},y:{grid:{display:false},ticks:{font:{size:11}}}},plugins:{legend:{display:false}}}});
}

const TOAST_SVGS={success:'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',error:'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',warning:'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',info:'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary-light)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'};

function toast(message,type='info',duration=4000){
  let el=document.querySelector('.toast-container');
  if(!el){el=document.createElement('div');el.className='toast-container';document.body.appendChild(el);}
  const t=document.createElement('div');t.className=`toast ${type}`;
  t.innerHTML=`${TOAST_SVGS[type]||TOAST_SVGS.info}<span style="flex:1">${message}</span><button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1.1rem;line-height:1;margin-left:4px;padding:0">&times;</button>`;
  el.appendChild(t);
  setTimeout(()=>{t.style.animation='slideIn .25s ease reverse';setTimeout(()=>t.remove(),220);},duration);
}

function initNotifBell(){
  const btn=document.getElementById('notifBtn'),drop=document.getElementById('notifDropdown'),markBtn=document.getElementById('markAllBtn');
  if(!btn||!drop)return;
  btn.addEventListener('click',e=>{e.stopPropagation();drop.classList.toggle('open');});
  document.addEventListener('click',e=>{if(!btn.contains(e.target)&&!drop.contains(e.target))drop.classList.remove('open');});
  if(markBtn){markBtn.addEventListener('click',async e=>{e.preventDefault();await fetch(PP_URL+'/api/notifications.php?mark_all_read=1');document.querySelectorAll('.notif-item').forEach(el=>el.classList.remove('unread'));document.querySelectorAll('.notif-dot').forEach(el=>el.style.background='#cbd5e1');const badge=document.getElementById('notifBadge');if(badge)badge.style.display='none';drop.classList.remove('open');toast('All notifications marked as read','success');});}
}

function initSidebar(){
  const btn=document.getElementById('sidebarToggle'),side=document.getElementById('appSidebar');
  if(!btn||!side)return;
  btn.style.display='flex';
  btn.addEventListener('click',()=>side.classList.toggle('open'));
  document.addEventListener('click',e=>{if(side.classList.contains('open')&&!side.contains(e.target)&&!btn.contains(e.target))side.classList.remove('open');});
}

function animateCounter(el,target,dur=900){
  if(isNaN(target)||target<0)return;
  const start=performance.now();
  const step=now=>{const p=Math.min((now-start)/dur,1);const e=1-Math.pow(1-p,3);el.textContent=Math.round(target*e).toLocaleString();if(p<1)requestAnimationFrame(step);};
  requestAnimationFrame(step);
}

function confirmAction(msg,cb){
  const ov=document.createElement('div');ov.className='modal-overlay';
  ov.innerHTML=`<div class="modal" style="max-width:380px"><div class="modal-header"><span class="modal-title">Confirm Action</span><button onclick="this.closest('.modal-overlay').remove()" style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:var(--text-muted)">&times;</button></div><div class="modal-body" style="text-align:center;padding:28px 24px"><div style="width:48px;height:48px;background:var(--warning-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div><p style="font-size:.9rem;font-weight:600;margin-bottom:5px">${msg}</p><p style="font-size:.78rem;color:var(--text-muted)">This action cannot be undone.</p></div><div class="modal-footer"><button onclick="this.closest('.modal-overlay').remove()" class="btn btn-secondary">Cancel</button><button id="_cY" class="btn btn-danger">Confirm</button></div></div>`;
  document.body.appendChild(ov);
  ov.querySelector('#_cY').addEventListener('click',()=>{ov.remove();cb();});
  ov.addEventListener('click',e=>{if(e.target===ov)ov.remove();});
}

function initImageUpload(inputId,areaId,previewId,imgId){
  const input=document.getElementById(inputId),area=document.getElementById(areaId),preview=document.getElementById(previewId),img=document.getElementById(imgId);
  if(!input)return;
  const show=file=>{const r=new FileReader();r.onload=e=>{if(img)img.src=e.target.result;if(area)area.style.display='none';if(preview)preview.style.display='block';};r.readAsDataURL(file);};
  input.addEventListener('change',()=>{if(input.files?.[0])show(input.files[0]);});
  if(area){area.addEventListener('dragover',e=>{e.preventDefault();area.classList.add('drag-over');});area.addEventListener('dragleave',()=>area.classList.remove('drag-over'));area.addEventListener('drop',e=>{e.preventDefault();area.classList.remove('drag-over');if(e.dataTransfer.files?.[0]){const dt=new DataTransfer();dt.items.add(e.dataTransfer.files[0]);input.files=dt.files;show(e.dataTransfer.files[0]);}});area.addEventListener('click',()=>input.click());}
}

function clearImageUpload(inputId,areaId,previewId){
  const input=document.getElementById(inputId),area=document.getElementById(areaId),preview=document.getElementById(previewId);
  if(input)input.value='';if(area)area.style.display='block';if(preview)preview.style.display='none';
}

function getGPSLocation(latId,lngId,statusId){
  const status=document.getElementById(statusId);
  if(status)status.innerHTML='<span class="loader"></span>&nbsp;Getting location...';
  if(!navigator.geolocation){if(status)status.textContent='GPS not supported.';return;}
  navigator.geolocation.getCurrentPosition(pos=>{
    const lat=pos.coords.latitude.toFixed(6),lng=pos.coords.longitude.toFixed(6);
    const le=document.getElementById(latId),lne=document.getElementById(lngId);
    if(le)le.value=lat;if(lne)lne.value=lng;
    if(status)status.innerHTML=`<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span style="color:var(--success);margin-left:4px">Captured: ${lat}, ${lng}</span>`;
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=16`,{headers:{'Accept-Language':'en'}}).then(r=>r.json()).then(d=>{if(d&&d.display_name){const li=document.querySelector('[name="location"]');if(li&&!li.value)li.value=d.display_name.split(',').slice(0,3).join(', ');}}).catch(()=>{});
  },err=>{const msgs={1:'Permission denied.',2:'Position unavailable.',3:'Timed out.'};if(status)status.textContent=msgs[err.code]||'Could not get location.';},{enableHighAccuracy:true,timeout:8000,maximumAge:0});
}

function initRowClick(){document.querySelectorAll('[data-href]').forEach(row=>{if(row.tagName==='TR'){row.style.cursor='pointer';row.addEventListener('click',e=>{if(e.target.closest('a,button,input,select,label'))return;window.location.href=row.dataset.href;});}});}

function initCharCounters(){document.querySelectorAll('[data-maxlength]').forEach(el=>{const max=parseInt(el.dataset.maxlength,10);const ctr=document.createElement('span');ctr.style.cssText='float:right;font-size:.7rem;color:var(--text-muted)';el.parentElement.appendChild(ctr);const upd=()=>{const rem=max-el.value.length;ctr.textContent=`${el.value.length}/${max}`;ctr.style.color=rem<20?'var(--danger)':'var(--text-muted)';};upd();el.addEventListener('input',upd);});}

function showFlashMessage(){const el=document.getElementById('flashMsg');if(el)toast(el.dataset.msg,el.dataset.type||'info');}

function initAutoRefresh(interval=60000){setInterval(async()=>{try{const r=await fetch(PP_URL+'/api/kpis.php');const d=await r.json();if(d.success&&d.kpis){Object.entries(d.kpis).forEach(([k,v])=>{document.querySelectorAll(`[data-kpi="${k}"]`).forEach(el=>{el.textContent=v;});});const badge=document.getElementById('notifBadge');if(badge&&d.unread!==undefined){badge.textContent=d.unread;badge.style.display=d.unread>0?'flex':'none';}}}catch(_){}},interval);}

document.addEventListener('DOMContentLoaded',()=>{
  if(window.innerWidth<=768)initSidebar();
  initNotifBell();initCharCounters();initRowClick();showFlashMessage();
  document.querySelectorAll('[data-counter]').forEach(el=>{const t=parseInt(el.dataset.counter,10);if(!isNaN(t))animateCounter(el,t);});
});
