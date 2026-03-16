const input = document.getElementById('productUrl');
const button = document.getElementById('convertBtn');
const resultBox = document.getElementById('converterResult');

function showResult(html, type = '') {
  resultBox.className = 'converter-result ' + type;
  resultBox.innerHTML = html;
}

async function convertLink() {
  const productUrl = input.value.trim();
  if (!productUrl) {
    showResult('Inserisci un link Amazon o un ASIN.', 'error');
    return;
  }

  button.disabled = true;
  button.textContent = 'Converting...';

  try {
    const body = new URLSearchParams({ product_url: productUrl });
    const response = await fetch(window.appConfig.convertEndpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
      },
      body
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.error || 'Errore imprevisto');
    }

    showResult(`
      <strong>Link creato con successo</strong><br>
      ASIN: ${data.asin}<br>
      Categoria: ${data.category}<br>
      Commissione Amazon stimata: ${data.amazon_rate}%<br>
      Percentuale riconosciuta all'utente: ${data.share_percent}%<br>
      Punti assegnati: <strong>${data.points} pts</strong><br>
      Tracking ID: ${data.tracking_id}<br><br>
      <a href="${data.affiliate_url}" target="_blank" rel="noopener">Apri link affiliato</a>
      <button class="inline-copy" data-link="${data.affiliate_url}">Copia</button>
    `, 'success');
  } catch (error) {
    showResult(error.message, 'error');
  } finally {
    button.disabled = false;
    button.textContent = 'Convert';
  }
}

button?.addEventListener('click', convertLink);
input?.addEventListener('keydown', (event) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    convertLink();
  }
});

document.addEventListener('click', async (event) => {
  if (!event.target.matches('.inline-copy')) return;
  const link = event.target.getAttribute('data-link');
  await navigator.clipboard.writeText(link);
  event.target.textContent = 'Copiato';
});
