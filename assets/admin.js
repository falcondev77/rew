var endpoint = window.appConfig.approveEndpoint;

function postAction(params) {
  return fetch(endpoint, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
    body: new URLSearchParams(params)
  }).then(function (r) { return r.json(); });
}

document.addEventListener('click', function (event) {
  var target = event.target;

  if (target.matches('.approve-btn')) {
    var id = target.getAttribute('data-id');
    target.disabled = true;
    target.textContent = '...';
    postAction({ action: 'approve_link', link_id: id }).then(function (data) {
      if (data.success) {
        var row = document.getElementById('link-' + id);
        if (row) row.style.opacity = '0.3';
        target.textContent = 'Approvato';
        setTimeout(function () { location.reload(); }, 800);
      } else {
        target.textContent = 'Errore';
        target.disabled = false;
      }
    });
  }

  if (target.matches('.reject-btn')) {
    var id = target.getAttribute('data-id');
    target.disabled = true;
    target.textContent = '...';
    postAction({ action: 'reject_link', link_id: id }).then(function (data) {
      if (data.success) {
        var row = document.getElementById('link-' + id);
        if (row) row.style.opacity = '0.3';
        target.textContent = 'Rifiutato';
        setTimeout(function () { location.reload(); }, 800);
      } else {
        target.textContent = 'Errore';
        target.disabled = false;
      }
    });
  }

  if (target.matches('.toggle-reward-btn')) {
    var rewardId = target.getAttribute('data-id');
    var isActive = target.getAttribute('data-active');
    var newActive = isActive === '1' ? 0 : 1;
    target.disabled = true;
    postAction({ action: 'toggle_reward', reward_id: rewardId, new_active: newActive }).then(function () {
      location.reload();
    });
  }

  if (target.matches('.fulfill-btn')) {
    var id = target.getAttribute('data-id');
    target.disabled = true;
    target.textContent = '...';
    postAction({ action: 'fulfill_redemption', redemption_id: id }).then(function () {
      location.reload();
    });
  }

  if (target.matches('.reject-redeem-btn')) {
    var id = target.getAttribute('data-id');
    target.disabled = true;
    target.textContent = '...';
    postAction({ action: 'reject_redemption', redemption_id: id }).then(function () {
      location.reload();
    });
  }
});

var rewardForm = document.getElementById('addRewardForm');
if (rewardForm) {
  rewardForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var formData = new FormData(rewardForm);
    formData.append('action', 'add_reward');
    var msg = document.getElementById('rewardFormMsg');

    fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      body: new URLSearchParams(formData)
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        msg.className = 'form-msg form-msg-success';
        msg.textContent = 'Premio aggiunto!';
        setTimeout(function () { location.reload(); }, 800);
      } else {
        msg.className = 'form-msg form-msg-error';
        msg.textContent = data.error || 'Errore';
      }
    })
    .catch(function () {
      msg.className = 'form-msg form-msg-error';
      msg.textContent = 'Errore di rete';
    });
  });
}
