// admin/product_images/templates.js

function addDragAffordances(fig, enabled) {
  if (!fig) return;
  const on = enabled !== false;
  fig.setAttribute('draggable', on ? 'true' : 'false');
  fig.setAttribute('tabindex','0');
  fig.setAttribute('aria-grabbed','false');
  if (!fig.querySelector('.js-drag-handle')) {
    const h = document.createElement('button'); h.setAttribute('draggable','true');
    h.type = 'button';
    // Mantener look & feel premium consistente con el template server (sin depender de Tailwind en JS)
    h.className = 'js-drag-handle img-drag-handle';
    h.title = 'Arrastrar para reordenar';
    h.setAttribute('aria-label','Arrastrar');
    h.textContent = '⋮⋮';
    fig.appendChild(h);
  }
  const handle = fig.querySelector('.js-drag-handle');
  if (handle) handle.classList.toggle('av3-hidden', !on);
}

export function createFigure({ item, csrf, pid, variantId }) {
  if (!item) return null;
  const id  = item?.id ?? item?.image_id ?? item?.imageId;
  const url = item?.thumb_url ?? item?.url ?? item?.path;
  const alt = item?.alt ?? '';
  const cov = !!(item?.is_cover ?? item?.cover ?? item?.isCover);
  if (!id || !url) return null;

  const g = document.getElementById('img-gallery');
  const delUrl   = g?.dataset?.deleteUrl || '/admin/products/image-delete';
  const coverUrl = g?.dataset?.coverUrl  || '/admin/products/image-cover';
  const assignUrl = g?.dataset?.assignUrl || '/admin/products/image-assign';
  const sortEnabled = (g?.dataset?.disableSort !== '1');

  const tpl = document.getElementById('tpl-gallery-item');
  if (tpl && 'content' in tpl) {
    const fig = tpl.content.firstElementChild.cloneNode(true);
    fig.dataset.id = String(id);

    const img = fig.querySelector('img');
    if (img) { img.src = url; img.alt = alt; }

    const badge = fig.querySelector('.cover-badge');
    if (badge) badge.classList.toggle('av3-hidden', !cov);
    fig.classList.toggle('ring-2', cov);
    fig.classList.toggle('ring-brand', cov);
    fig.classList.toggle('is-cover', cov);

    // Eliminar form
    const delForm = fig.querySelectorAll('form.img-action')[0];
    if (delForm) {
      delForm.action = delUrl;
      const iCsrf = delForm.querySelector('input[name="_csrf"]');
      const iPid  = delForm.querySelector('input[name="product_id"]');
      const iVar  = delForm.querySelector('input[name="variant_id"]');
      const iId   = delForm.querySelector('input[name="image_id"]');
      if (iCsrf) iCsrf.value = csrf || '';
      if (iPid)  iPid.value  = String(pid || 0);
      if (iVar)  iVar.value  = (variantId || '');
      if (iId)   iId.value   = String(id);
    }

    // Cover form
    const covForm = fig.querySelectorAll('form.img-action')[1];
    if (covForm) {
      covForm.action = coverUrl;
      const iCsrf = covForm.querySelector('input[name="_csrf"]');
      const iPid  = covForm.querySelector('input[name="product_id"]');
      const iVar  = covForm.querySelector('input[name="variant_id"]');
      const iId   = covForm.querySelector('input[name="image_id"]');
      if (iCsrf) iCsrf.value = csrf || '';
      if (iPid)  iPid.value  = String(pid || 0);
      if (iVar)  iVar.value  = (variantId || '');
      if (iId)   iId.value   = String(id);
      const btn = covForm.querySelector('.js-cover') || covForm.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = !!cov;
        btn.classList.toggle('av3-opacity-60', !!cov);
        btn.classList.toggle('av3-pointer-events-none', !!cov);
        btn.textContent = cov ? 'Portada' : 'Hacer portada';
        btn.classList.add('js-cover');
      }
    }

    // Assign form
    const asForm = fig.querySelector('form.img-assign');
    if (asForm) {
      asForm.action = assignUrl;
      const iCsrf = asForm.querySelector('input[name="_csrf"]');
      const iPid  = asForm.querySelector('input[name="product_id"]');
      const iId   = asForm.querySelector('input[name="image_id"]');
      const sel   = asForm.querySelector('select[name="variant_id"]');
      if (iCsrf) iCsrf.value = csrf || '';
      if (iPid)  iPid.value  = String(pid || 0);
      if (iId)   iId.value   = String(id);
      if (sel)   sel.value   = String(variantId || 0);
    }

    // checkbox (bulk) debe tener ID
    const chk = fig.querySelector('input.img-select[type="checkbox"]');
    if (chk) chk.value = String(id);

    // Badge default (si es portada)
    if (badge && badge.textContent.trim() === '') badge.textContent = 'Portada · General';

    addDragAffordances(fig, sortEnabled);
    return fig;
  }

  // Fallback seguro sin innerHTML
  const fig = document.createElement('figure');
  fig.className = 'av3-relative av3-border av3-rounded av3-pad-2';
  if (cov) { fig.classList.add('ring-2','ring-brand','is-cover'); }
  fig.dataset.id = String(id);

  const img = document.createElement('img');
  img.className = 'av3-w-full av3-aspect-square av3-object-cover av3-rounded';
  img.src = url; img.alt = alt;
  fig.appendChild(img);

  const badge = document.createElement('span');
  badge.className = 'cover-badge av3-absolute top-2 left-2 av3-text--10px av3-weight-strong av3-px-1-5 av3-py-0-5 av3-rounded av3-bg-brand av3-text-white';
  if (!cov) badge.classList.add('av3-hidden');
  badge.textContent = 'Portada · General';
  fig.appendChild(badge);

  const actions = document.createElement('div');
  actions.className = 'av3-flex av3-items-center av3-justify-between av3-mt-2';
  fig.appendChild(actions);

  const formDel = document.createElement('form');
  formDel.className = 'img-action'; formDel.method='post'; formDel.action = delUrl;
  formDel.innerHTML = '';
  actions.appendChild(formDel);
  const iCsrf1 = document.createElement('input'); iCsrf1.type='hidden'; iCsrf1.name='_csrf'; iCsrf1.value=csrf||'';
  const iPid1  = document.createElement('input'); iPid1.type='hidden'; iPid1.name='product_id'; iPid1.value=String(pid||0);
  const iVar1  = document.createElement('input'); iVar1.type='hidden'; iVar1.name='variant_id'; iVar1.value=(variantId||'');
  const iId1   = document.createElement('input'); iId1.type='hidden'; iId1.name='image_id'; iId1.value=String(id);
  const bDel   = document.createElement('button'); bDel.type='submit'; bDel.className='btn-xs btn-danger'; bDel.textContent='Eliminar';
  formDel.append(iCsrf1,iPid1,iVar1,iId1,bDel);

  const formCov = document.createElement('form');
  formCov.className = 'img-action'; formCov.method='post'; formCov.action = coverUrl;
  actions.appendChild(formCov);
  const iCsrf2 = document.createElement('input'); iCsrf2.type='hidden'; iCsrf2.name='_csrf'; iCsrf2.value=csrf||'';
  const iPid2  = document.createElement('input'); iPid2.type='hidden'; iPid2.name='product_id'; iPid2.value=String(pid||0);
  const iVar2  = document.createElement('input'); iVar2.type='hidden'; iVar2.name='variant_id'; iVar2.value=(variantId||'');
  const iId2   = document.createElement('input'); iId2.type='hidden'; iId2.name='image_id'; iId2.value=String(id);
  const bCov   = document.createElement('button'); bCov.type='submit'; bCov.className='btn-xs js-cover'; bCov.textContent=cov?'Portada':'Hacer portada';
  if (cov) { bCov.disabled=true; bCov.classList.add('av3-opacity-60','av3-pointer-events-none'); }
  formCov.append(iCsrf2,iPid2,iVar2,iId2,bCov);

  addDragAffordances(fig, sortEnabled);
  return fig;
}

