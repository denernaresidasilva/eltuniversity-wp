/* Gerenciador de Leads SaaS – Public Form Handler */
(function () {
  'use strict';

  function initForm(form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      var btn = form.querySelector('.wplm-btn-submit');
      var msgs = form.querySelector('.wplm-messages');
      var listaId = form.dataset.listaId;

      btn.disabled = true;
      btn.classList.add('loading');
      msgs.className = 'wplm-messages';
      msgs.textContent = '';

      var data = { lista_id: parseInt(listaId, 10) };
      var elements = form.elements;
      for (var i = 0; i < elements.length; i++) {
        var el = elements[i];
        if (!el.name || el.name === 'wplm_nonce' || el.name === '_wpnonce') continue;
        if (el.type === 'checkbox') {
          data[el.name] = el.checked ? '1' : '0';
        } else {
          data[el.name] = el.value;
        }
      }

      try {
        var resp = await fetch(window.LeadsSaaSPublic.apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.LeadsSaaSPublic.nonce,
          },
          body: JSON.stringify(data),
        });
        var json = await resp.json();
        if (resp.ok && json.success) {
          msgs.className = 'wplm-messages success';
          msgs.textContent = json.message || 'Cadastro realizado com sucesso!';
          form.reset();
        } else {
          msgs.className = 'wplm-messages error';
          msgs.textContent = json.message || 'Erro ao enviar. Tente novamente.';
        }
      } catch (err) {
        msgs.className = 'wplm-messages error';
        msgs.textContent = 'Erro de conexão. Tente novamente.';
      } finally {
        btn.disabled = false;
        btn.classList.remove('loading');
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.wplm-form').forEach(initForm);
  });
})();
