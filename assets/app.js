const input = document.getElementById('productUrl');
const button = document.getElementById('convertBtn');
const resultBox = document.getElementById('converterResult');

let lastConvertedLinkId = null;

function showResult(html, type) {
  if (!resultBox) return;
  resultBox.className = 'converter-result ' + (type || '');
  resultBox.innerHTML = html;
}

async function convertLink() {
  const productUrl = input.value.trim();
  if (!productUrl) {
    showResult('Inserisci un link Amazon o un ASIN.', 'error');
    return;
  }

  button.disabled = true;
  button.textContent = 'Conversione...';

  try {
    const body = new URLSearchParams({ product_url: productUrl });
    const response = await fetch(window.appConfig.convertEndpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body
    });

    const raw = await response.text();
    let data;
    try { data = JSON.parse(raw); }
    catch (_e) { throw new Error('Risposta non valida dal server'); }

    if (!response.ok) throw new Error(data.error || 'Errore imprevisto');

    lastConvertedLinkId = data.link_id || null;

    showResult(
      '<strong>Link creato - punti in attesa di conferma</strong><br>' +
      'ASIN: ' + data.asin + '<br>' +
      'Categoria: ' + data.category + '<br>' +
      'Prezzo: &euro; ' + Number(data.product_price).toFixed(2) + '<br>' +
      'Commissione Amazon: ' + data.amazon_rate + '%<br>' +
      'La tua quota: ' + data.share_percent + '%<br>' +
      'Punti stimati: <strong>' + data.points + ' pts</strong> (in attesa)<br>' +
      'Tag: ' + data.tag + ' | Subtag: ' + data.subtag + '<br><br>' +
      '<a href="' + data.affiliate_url + '" target="_blank" rel="noopener" id="affiliateLink">Apri link affiliato</a> ' +
      '<button class="inline-copy" data-link="' + data.affiliate_url + '">Copia</button>',
      'success'
    );

    var affiliateAnchor = document.getElementById('affiliateLink');
    if (affiliateAnchor && lastConvertedLinkId) {
      affiliateAnchor.addEventListener('click', function () {
        startCheckoutTracking(lastConvertedLinkId);
      });
    }
  } catch (error) {
    showResult(error.message, 'error');
  } finally {
    button.disabled = false;
    button.textContent = 'Converti';
  }
}

if (button) {
  button.addEventListener('click', convertLink);
}
if (input) {
  input.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      convertLink();
    }
  });
}

document.addEventListener('click', function (event) {
  if (event.target.matches('.inline-copy')) {
    var link = event.target.getAttribute('data-link');
    navigator.clipboard.writeText(link);
    event.target.textContent = 'Copiato!';
    setTimeout(function () { event.target.textContent = 'Copia'; }, 2000);
  }

  if (event.target.matches('.redeem-btn')) {
    var rewardId = event.target.getAttribute('data-reward-id');
    event.target.disabled = true;
    event.target.textContent = 'Riscatto...';
    redeemReward(rewardId, event.target);
  }
});

async function redeemReward(rewardId, btn) {
  try {
    var response = await fetch(window.appConfig.redeemEndpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: new URLSearchParams({ reward_id: rewardId })
    });

    var data = await response.json();
    if (data.success) {
      btn.textContent = 'Richiesto!';
      btn.className = 'btn-sm btn-success redeem-btn';
      setTimeout(function () { location.reload(); }, 1500);
    } else {
      btn.textContent = data.error || 'Errore';
      btn.disabled = false;
    }
  } catch (_e) {
    btn.textContent = 'Errore';
    btn.disabled = false;
  }
}

function startCheckoutTracking(linkId) {
  sessionStorage.setItem('tracking_link_id', linkId);

  window.addEventListener('message', function handler(event) {
    if (!event.data || event.data.type !== 'AMAZON_CHECKOUT_CONFIRMED') return;
    var trackedId = event.data.linkId || sessionStorage.getItem('tracking_link_id');
    if (!trackedId) return;

    fetch(window.appConfig.confirmEndpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: new URLSearchParams({ link_id: trackedId })
    }).then(function () {
      sessionStorage.removeItem('tracking_link_id');
    });

    window.removeEventListener('message', handler);
  });
}

(function initCheckoutWatcher() {
  var storedLinkId = sessionStorage.getItem('tracking_link_id');
  if (!storedLinkId) return;

  var checkUrl = window.location.href;
  if (checkUrl.indexOf('/checkout/') !== -1 && checkUrl.indexOf('spc') !== -1) {
    var observer = new MutationObserver(function () {
      var placeOrderBtn = document.getElementById('placeOrder');
      if (!placeOrderBtn) return;

      observer.disconnect();
      placeOrderBtn.addEventListener('click', function () {
        if (window.opener) {
          window.opener.postMessage({
            type: 'AMAZON_CHECKOUT_CONFIRMED',
            linkId: storedLinkId
          }, '*');
        }

        fetch(window.appConfig.confirmEndpoint, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: new URLSearchParams({ link_id: storedLinkId })
        }).then(function () {
          sessionStorage.removeItem('tracking_link_id');
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }
})();
