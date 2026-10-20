'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const closeSidebar = () => {sidebar?.classList.remove('open');backdrop?.classList.remove('visible');};
  document.querySelectorAll('[data-toggle-sidebar]').forEach(button => button.addEventListener('click', () => {
    sidebar?.classList.toggle('open');backdrop?.classList.toggle('visible');
  }));
  backdrop?.addEventListener('click', closeSidebar);
  document.addEventListener('keydown', e => {if(e.key==='Escape') closeSidebar();});
  document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', e => {
    if (form.dataset.autoContinue === '1') return;
    if (!window.confirm(form.dataset.confirm || 'Yakin ingin melanjutkan?')) e.preventDefault();
  }));
  document.querySelectorAll('button[data-confirm]').forEach(button => button.addEventListener('click', e => {
    if (!window.confirm(button.dataset.confirm || 'Yakin ingin melanjutkan?')) e.preventDefault();
  }));
  const form = document.getElementById('calculatorForm');
  if (form) {
  const result = document.getElementById('calcResult');
  const rupiah = number => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(number);
  const showError = message => {result.hidden = false;result.replaceChildren();const p=document.createElement('p');p.className='result-warning';p.textContent=message;result.append(p);};
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('button[type=submit]');
    button.disabled = true;button.textContent='Calculating... ♡';
    try {
      const url = new URL(form.dataset.calculateUrl,window.location.href);
      url.searchParams.set('currency',form.querySelector('[name="currency"]').value);
      url.searchParams.set('price',form.querySelector('[name="price"]').value);
      url.searchParams.set('order_type',form.querySelector('[name="order_type"]').value);
      const response = await fetch(url,{headers:{Accept:'application/json'}});
      const data = await response.json();
      if(!response.ok) {showError(data.message || 'Periksa input, lalu coba lagi.');return;}
      const lines = [
        ['Harga × kurs',rupiah(data.subtotal)],
        [`Fee ${data.order_type_label}`,rupiah(data.fee)],
        ['Estimated total',rupiah(data.total)]
      ];
      result.replaceChildren();
      const rate = document.createElement('div');rate.className='result-line';
      const name=document.createElement('span');name.textContent=`Kurs 1 ${data.currency}`;
      const value=document.createElement('strong');value.textContent=rupiah(data.rate);
      rate.append(name,value);result.append(rate);
      lines.forEach(([label,amount],index) => {
        const line=document.createElement('div');line.className='result-line'+(index===2?' result-total':'');
        const k=document.createElement('span');k.textContent=label;
        const v=document.createElement('strong');v.textContent=amount;
        line.append(k,v);result.append(line);
      });
      result.hidden = false;
    } catch(error) {showError('Perhitungan gagal tersambung. Coba kembali.');}
    finally {button.disabled=false;button.textContent='Calculate My Price ♡';}
  });
  }

  const upload = document.getElementById('serenityExcel');
  if (upload) {
    const output = document.getElementById('selectedExcelFile');
    upload.addEventListener('change', () => {
      const file = upload.files?.[0];
      output.textContent = file ? `${file.name} · ${(file.size / 1024).toFixed(0)} KB` : 'Belum ada file dipilih.';
    });
  }

  const importContinue = document.getElementById('serenityImportCommit');
  if (importContinue?.dataset.autoContinue === '1') {
    const agree = importContinue.querySelector('[name="confirmed"]');
    const auto = importContinue.querySelector('[name="auto_continue"]');
    if (agree && auto) {agree.checked = true;auto.checked = true;}
    // Do not trigger the interactive data-confirm prompt for every committed chunk.
    importContinue.removeAttribute('data-confirm');
    window.setTimeout(() => importContinue.requestSubmit(), 500);
  }

  // Batch recap is progressively enhanced: without JS the full table remains visible.
  const tbody = document.getElementById('batchItemRows');
  if (tbody) {
    const search = document.getElementById('batchItemSearch');
    const buyer = document.getElementById('batchCustomerFilter');
    const sort = document.getElementById('batchItemSort');
    const count = document.getElementById('batchVisibleCount');
    const empty = document.getElementById('batchNoResults');
    const rows = Array.from(tbody.querySelectorAll('tr[data-buyer]'));
    const normalize = value => (value || '').toLocaleLowerCase('id-ID').normalize('NFKC').trim();
    const apply = () => {
      const query = normalize(search.value);
      const selected = buyer.value;
      const order = sort.value;
      const sorted = [...rows];
      if (order === 'customer') sorted.sort((a,b) => a.dataset.name.localeCompare(b.dataset.name,'id'));
      else if (order === 'item') sorted.sort((a,b) => a.dataset.item.localeCompare(b.dataset.item,'id'));
      else if (order === 'qtydesc') sorted.sort((a,b) => Number(b.dataset.qty)-Number(a.dataset.qty));
      if (order === 'initial') sorted.sort((a,b) => Number(a.dataset.position)-Number(b.dataset.position));
      tbody.append(...sorted);
      let visible = 0;
      sorted.forEach(row => {
        const matches = (!selected || row.dataset.buyer === selected) &&
          (!query || normalize(row.dataset.name).includes(query) || normalize(row.dataset.item).includes(query));
        row.hidden = !matches;
        if (matches) visible++;
      });
      count.textContent = `Menampilkan ${visible} dari ${rows.length} barang`;
      empty.hidden = visible !== 0;
    };
    search.addEventListener('input', apply);
    buyer.addEventListener('change', apply);
    sort.addEventListener('change', apply);
  }
});
