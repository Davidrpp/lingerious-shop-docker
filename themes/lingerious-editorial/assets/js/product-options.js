/* Progressive enhancement of native WooCommerce variation dropdowns. */
(() => {
  'use strict';
  const colors = Object.freeze({
    black:'#191919',white:'#f8f6f1',beige:'#d8c0a2',blue:'#336da3',
    'deep-blue':'#243c78','deep-purple':'#51305d',purple:'#8c4aa3',
    red:'#b52435','deep-red':'#751e30',burgundy:'#722c43','red-wine':'#672737',
    pink:'#e6a6b4',fuchsia:'#c52386','coral-red':'#db5f59',green:'#397149',
    emerald:'#1a7756',grey:'#999b9a',yellow:'#eac94a',orange:'#e78037',
    leopard:'repeating-linear-gradient(115deg,#b9956f 0 7px,#332d27 8px 12px,#d6bf9c 13px 21px)',
    zebra:'repeating-linear-gradient(120deg,#f9f8f5 0 6px,#232323 7px 12px)'
  });
  const $ = window.jQuery;
  const display = text => text.replace(/-/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase());
  const sizeNames = ['XS','S','M','L','XL','XXL','XXXL','4XL','5XL','6XL'];
  const conciseSize = text => {
    const value = text.trim();
    const match = value.match(/^(XS|S|M|L|XL|XXL|XXXL|[4-9]XL)\s*\(/i);
    return match ? match[1].toUpperCase() : value;
  };
  const sizeRank = text => {
    const value = conciseSize(text).toUpperCase().replace(/\s+/g,'');
    const alpha = sizeNames.indexOf(value);
    if (alpha >= 0) return alpha;
    const bra = value.match(/^(\d{2,3})([A-H]{1,3})$/);
    return bra ? 100 + Number(bra[1])*20 + bra[2].split('').reduce((n,c) => n*9+c.charCodeAt(0)-65,0) : 99999;
  };
  function init(form) {
    if (form.dataset.lgEnhanced) return;
    const selects = [...form.querySelectorAll('table.variations select[name^="attribute_"]')];
    if (!selects.length) return;
    const controls = [];
    for (const select of selects) {
      const row = select.closest('tr');
      const heading = row?.querySelector('th.label');
      let label = heading?.querySelector('label')?.textContent.trim() || display(select.name);
      const isColor = /color/i.test(select.name);
      const isSize = /size/i.test(select.name);
      if (!row || (!isColor && !isSize)) continue;
      const options = [...select.options].filter(item => item.value)
        .sort((a,b) => isSize ? sizeRank(a.textContent)-sizeRank(b.textContent) : 0);
      if (isSize) {
        const braSizes = options.length > 0 && options.every(item => /^\d{2,3}[a-h]{1,3}$/i.test(item.textContent.trim()));
        label = braSizes ? 'Bra size' : 'Size';
        const nativeLabel = heading?.querySelector('label');
        if (nativeLabel) nativeLabel.textContent = label;
      }
      const picker = document.createElement('div');
      picker.className = `lg-variant-picker ${isColor ? 'lg-variant-picker--color' : 'lg-variant-picker--size'}`;
      picker.setAttribute('role', 'group');
      picker.setAttribute('aria-label', `${label} options`);
      const selected = document.createElement('span');
      selected.className = 'lg-variant-current';
      selected.setAttribute('aria-live', 'polite');
      heading.append(selected);
      if (isSize && !heading.querySelector('.lg-variant-guide')) {
        const guide = document.createElement('a');
        guide.href = '/size-guide/';
        guide.className = 'lg-variant-guide';
        guide.textContent = 'Size guide';
        heading.append(guide);
      }
      const buttons = [];
      for (const option of options) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'lg-variant-option';
        button.dataset.value = option.value;
        button.setAttribute('aria-label', `${label}: ${option.textContent.trim()}`);
        button.setAttribute('aria-pressed', 'false');
        button.title = option.textContent.trim();
        button.addEventListener('pointerdown', () => button.classList.add('is-pointer-focus'));
        button.addEventListener('blur', () => button.classList.remove('is-pointer-focus'));
        if (isColor) {
          const tone = colors[option.value.toLowerCase()] || colors[option.value.toLowerCase().replace(/-set$/, '')];
          if (tone) {
            const swatch = document.createElement('span');
            swatch.className = 'lg-variant-swatch';
            swatch.style.background = tone;
            swatch.setAttribute('aria-hidden', 'true');
            button.append(swatch);
          } else button.classList.add('lg-variant-option--text');
        }
        if (!button.firstChild) button.textContent = isSize ? conciseSize(option.textContent) : option.textContent.trim();
        button.addEventListener('click', () => {
          if (button.disabled) return;
          if ($) $(select).val(option.value).trigger('change');
          else { select.value = option.value; select.dispatchEvent(new Event('change', {bubbles:true})); }
          window.requestAnimationFrame(refresh);
        });
        picker.append(button);
        buttons.push(button);
      }
      if (!buttons.length) continue;
      select.insertAdjacentElement('afterend', picker);
      if (isSize && options.some(item => conciseSize(item.textContent) !== item.textContent.trim())) {
        const details = document.createElement('details');
        details.className = 'lg-size-references';
        const summary = document.createElement('summary');
        summary.textContent = 'Supplier size references';
        const explanation = document.createElement('p');
        explanation.textContent = options.map(item => item.textContent.trim().replace(/or/ig, ' / ')).join(' · ') + '. These are supplier labels, not a verified fit chart.';
        details.append(summary, explanation);
        picker.after(details);
      }
      select.classList.add('lg-variant-native');
      select.setAttribute('aria-hidden', 'true');
      select.tabIndex = -1;
      controls.push({select, buttons, selected, label, isSize});
    }
    if (!controls.length) return;
    form.dataset.lgEnhanced = 'true';
    const galleryImage = form.closest('.product')?.querySelector('.woocommerce-product-gallery img.wp-post-image');
    const galleryLink = galleryImage?.closest('a');
    const originalPhoto = galleryImage ? {
      src: galleryImage.getAttribute('src'), srcset: galleryImage.getAttribute('srcset'),
      sizes: galleryImage.getAttribute('sizes'), alt: galleryImage.getAttribute('alt'),
      full_src: galleryImage.getAttribute('data-large_image') || galleryLink?.href
    } : null;
    function updatePhoto(image) {
      if (!galleryImage || !image?.src) return;
      for (const attr of ['srcset', 'sizes']) {
        if (image[attr]) galleryImage.setAttribute(attr, image[attr]);
        else galleryImage.removeAttribute(attr);
      }
      galleryImage.setAttribute('src', image.src);
      if (image.alt) galleryImage.setAttribute('alt', image.alt);
      galleryImage.setAttribute('data-large_image', image.full_src || image.src);
      if (galleryLink) galleryLink.href = image.full_src || image.src;
    }
    const colorControl = controls.find(({select}) => /color/i.test(select.name));
    const photoNote = document.createElement('p');
    photoNote.className = 'lg-variant-photo-note';
    photoNote.hidden = true;
    colorControl?.select.closest('tr')?.querySelector('td.value')?.append(photoNote);
    let available = null;
    try {
      const data = JSON.parse(form.getAttribute('data-product_variations') || 'false');
      if (Array.isArray(data)) available = data.filter(item => item.is_in_stock && item.is_purchasable && item.variation_is_active);
    } catch (_) { /* WooCommerce may use AJAX for large variation sets. */ }
    function matchesVariation(candidate, target, value) {
      if (candidate.attributes[target.name] && candidate.attributes[target.name] !== value) return false;
      return controls.every(({select}) => {
        if (select === target || !select.value) return true;
        const attribute = candidate.attributes[select.name];
        return !attribute || attribute === select.value;
      });
    }
    function refresh() {
      for (const control of controls) {
        const {select, buttons, selected, isSize} = control;
        const current = select.options[select.selectedIndex];
        selected.textContent = current?.value ? (isSize ? conciseSize(current.textContent) : current.textContent.trim().replace(/\s+Set$/i, '')) : '';
        for (const button of buttons) {
          const native = [...select.options].find(option => option.value === button.dataset.value);
          const inStock = !available || available.some(candidate => matchesVariation(candidate, select, button.dataset.value));
          const enabled = Boolean(native && !native.disabled && inStock);
          button.disabled = !enabled;
          button.classList.toggle('is-selected', select.value === button.dataset.value);
          button.setAttribute('aria-pressed', String(select.value === button.dataset.value));
          button.title = enabled ? button.getAttribute('aria-label') : `${button.getAttribute('aria-label')} â€” unavailable`;
        }
      }
    }
    if ($ && colorControl) {
      $(form).on('found_variation', (_, variation) => {
        photoNote.textContent = variation.lg_photo_note || '';
        photoNote.hidden = !variation.lg_photo_note;
      });
      $(form).on('hide_variation reset_data', () => {
        photoNote.textContent = '';
        photoNote.hidden = true;
      });
    }
    if ($ && galleryImage) {
      $(form).on('found_variation', (_, variation) => window.requestAnimationFrame(() => {
        if (form.querySelector('input[name="variation_id"]')?.value === String(variation.variation_id)) updatePhoto(variation.image);
      }));
      $(form).on('hide_variation reset_data', () => window.requestAnimationFrame(() => updatePhoto(originalPhoto)));
    }
    form.addEventListener('change', refresh);
    if ($) $(form).on('woocommerce_update_variation_values found_variation reset_data hide_variation woocommerce_variation_has_changed', refresh);
    refresh();
  }
  const start = () => document.querySelectorAll('form.variations_form').forEach(init);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once:true});
  else start();
})();
