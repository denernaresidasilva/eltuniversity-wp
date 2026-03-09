/**
 * Plataforma de Webinars – Frontend Player
 * Custom YouTube player with chat, automations, and activity simulation
 */
(function () {
  'use strict';

  var config = window.WWPlayerConfig;
  if (!config) return;

  var webinarId     = config.webinarId;
  var videoId       = config.videoId;
  var tipo          = config.tipo;
  var bloquearAvanco= config.bloquearAvanco;
  var simulacaoAtiva= config.simulacaoAtiva;
  var simulacaoCount= config.simulacaoContagem || 0;
  var chatMensagens = config.chatMensagens || [];
  var automacoes    = config.automacoes || [];
  var apiUrl        = config.apiUrl;
  var nonce         = config.nonce;

  var player           = null;
  var participanteId   = 0;
  var chatShown        = {};
  var automacaoShown   = {};
  var lastTime         = 0;
  var tracking         = false;
  var trackInterval    = null;

  /* ─────────────────────────────────────────
     YouTube IFrame API loader
  ───────────────────────────────────────── */
  window.onYouTubeIframeAPIReady = function () {
    player = new YT.Player('ww-youtube-player', {
      videoId    : videoId,
      playerVars : {
        controls      : 0,
        disablekb     : 1,
        fs            : 0,
        iv_load_policy: 3,
        modestbranding: 1,
        rel           : 0,
        showinfo      : 0,
        autohide      : 1,
        wmode         : 'transparent',
        origin        : window.location.origin,
      },
      events: {
        onReady      : onPlayerReady,
        onStateChange: onPlayerStateChange,
      }
    });
  };

  var tag    = document.createElement('script');
  tag.src    = 'https://www.youtube.com/iframe_api';
  var first  = document.getElementsByTagName('script')[0];
  first.parentNode.insertBefore(tag, first);

  /* ─────────────────────────────────────────
     Player events
  ───────────────────────────────────────── */
  function onPlayerReady(event) {
    bindControls();
    startProgressLoop();
    trackEvent('inicio_video', {});
  }

  function onPlayerStateChange(event) {
    var btn = document.getElementById('ww-play-btn');
    if (!btn) return;
    if (event.data === YT.PlayerState.PLAYING) {
      btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>';
      tracking = true;
    } else {
      btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
      if (event.data === YT.PlayerState.PAUSED) {
        trackEvent('pause_video', { tempo: Math.round(player.getCurrentTime()) });
        tracking = false;
      }
    }
  }

  /* ─────────────────────────────────────────
     Controls
  ───────────────────────────────────────── */
  function bindControls() {
    var playBtn = document.getElementById('ww-play-btn');
    if (playBtn) {
      playBtn.addEventListener('click', function () {
        if (player.getPlayerState() === YT.PlayerState.PLAYING) {
          player.pauseVideo();
        } else {
          player.playVideo();
        }
      });
    }

    var progressBar = document.getElementById('ww-progress-bar');
    if (progressBar && !bloquearAvanco) {
      progressBar.addEventListener('click', function (e) {
        var rect = progressBar.getBoundingClientRect();
        var pct  = (e.clientX - rect.left) / rect.width;
        var dur  = player.getDuration();
        if (dur) player.seekTo(dur * pct, true);
      });
    }

    var fsBtn = document.getElementById('ww-fullscreen-btn');
    if (fsBtn) {
      fsBtn.addEventListener('click', function () {
        var wrapper = document.querySelector('.ww-player-wrapper');
        if (!document.fullscreenElement) {
          wrapper && wrapper.requestFullscreen && wrapper.requestFullscreen();
        } else {
          document.exitFullscreen && document.exitFullscreen();
        }
      });
    }

    // Chat send
    var sendBtn   = document.getElementById('ww-chat-send');
    var chatInput = document.getElementById('ww-chat-input');
    if (sendBtn && chatInput) {
      sendBtn.addEventListener('click', sendLiveChatMessage);
      chatInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') sendLiveChatMessage();
      });
    }
  }

  /* ─────────────────────────────────────────
     Progress loop
  ───────────────────────────────────────── */
  function startProgressLoop() {
    setInterval(function () {
      if (!player || typeof player.getCurrentTime !== 'function') return;

      var state = player.getPlayerState();
      if (state !== YT.PlayerState.PLAYING) return;

      var current  = Math.round(player.getCurrentTime());
      var duration = Math.round(player.getDuration()) || 1;

      // Progress bar
      var fill = document.getElementById('ww-progress-fill');
      if (fill) fill.style.width = ((current / duration) * 100) + '%';

      // Time display
      var timeEl = document.getElementById('ww-time');
      if (timeEl) timeEl.textContent = fmtTime(current) + ' / ' + fmtTime(duration);

      // Chat messages
      chatMensagens.forEach(function (m) {
        if (!chatShown[m.id] && m.tempo <= current && m.tempo > current - 6) {
          chatShown[m.id] = true;
          appendChatMessage(m.autor, m.mensagem, false);
        }
      });

      // Automations
      automacoes.forEach(function (a) {
        if (a.gatilho !== 'tempo_especifico') return;
        var t = parseInt((a.config && a.config.tempo) || 0, 10);
        if (!automacaoShown[a.id] && t <= current && t > current - 6) {
          automacaoShown[a.id] = true;
          triggerAutomacao(a);
        }
      });

      // Track time to API every 30s
      if (participanteId && tracking && current - lastTime >= 30) {
        lastTime = current;
        updateTempo(current);
      }

    }, 1000);
  }

  /* ─────────────────────────────────────────
     Chat
  ───────────────────────────────────────── */
  function appendChatMessage(autor, mensagem, isOwn) {
    var container = document.getElementById('ww-chat-messages');
    if (!container) return;

    var item = document.createElement('div');
    item.className = 'ww-chat-item' + (isOwn ? ' ww-chat-own' : '');

    var avatar = document.createElement('div');
    avatar.className = 'ww-chat-avatar';
    avatar.textContent = (autor && autor[0]) ? autor[0].toUpperCase() : '?';

    var body = document.createElement('div');
    body.className = 'ww-chat-body';

    var name = document.createElement('strong');
    name.className = 'ww-chat-author';
    name.textContent = autor;

    var text = document.createElement('p');
    text.className = 'ww-chat-text';
    text.textContent = mensagem;

    body.appendChild(name);
    body.appendChild(text);
    item.appendChild(avatar);
    item.appendChild(body);
    container.appendChild(item);
    container.scrollTop = container.scrollHeight;
  }

  function sendLiveChatMessage() {
    var input = document.getElementById('ww-chat-input');
    if (!input || !input.value.trim()) return;

    var msg = input.value.trim();
    input.value = '';
    appendChatMessage('Você', msg, true);

    fetch(apiUrl + '/webinars/' + webinarId + '/chat/ao-vivo', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify({ autor: 'Participante', mensagem: msg })
    }).catch(function () {});
  }

  /* ─────────────────────────────────────────
     Automations
  ───────────────────────────────────────── */
  function triggerAutomacao(a) {
    switch (a.acao) {
      case 'mostrar_botao':
        showBotaoCompra(a);
        break;
      case 'mostrar_popup':
        showPopup(a);
        break;
      case 'mostrar_notificacao':
        showNotificacao(a.config && a.config.texto ? a.config.texto : 'Nova notificação!');
        break;
      case 'redirecionar':
        if (a.config && a.config.url) {
          setTimeout(function () { window.location.href = a.config.url; }, 2000);
        }
        break;
      case 'enviar_webhook':
        if (a.config && a.config.url) {
          fetch(a.config.url, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ webinar_id: webinarId, participante_id: participanteId, automacao_id: a.id })
          }).catch(function () {});
        }
        break;
    }
    trackEvent('clique_botao', { automacao_id: a.id, acao: a.acao });
  }

  function showBotaoCompra(a) {
    var overlay = document.getElementById('ww-automation-overlays');
    if (!overlay) return;

    var btn = document.createElement('a');
    btn.className   = 'ww-oferta-btn';
    btn.textContent = (a.config && a.config.texto) ? a.config.texto : 'Garanta sua vaga!';
    btn.href        = (a.config && a.config.url) ? a.config.url : '#';
    btn.target      = '_blank';
    btn.style.background = (a.config && a.config.cor) ? a.config.cor : '#ef4444';

    var close = document.createElement('button');
    close.className   = 'ww-oferta-close';
    close.textContent = '×';
    close.onclick     = function () { wrapper.remove(); };

    var wrapper = document.createElement('div');
    wrapper.className = 'ww-oferta-wrapper';
    wrapper.appendChild(close);
    wrapper.appendChild(btn);
    overlay.appendChild(wrapper);

    trackEvent('clique_botao', { tipo: 'oferta', texto: btn.textContent });
  }

  function showPopup(a) {
    var texto = (a.config && a.config.texto) ? a.config.texto : 'Oferta especial!';
    var url   = (a.config && a.config.url) ? a.config.url : '';

    var backdrop = document.createElement('div');
    backdrop.className = 'ww-popup-backdrop';
    backdrop.onclick   = function () { backdrop.remove(); };

    var box = document.createElement('div');
    box.className = 'ww-popup-box';
    box.onclick   = function (e) { e.stopPropagation(); };

    var msg = document.createElement('p');
    msg.textContent = texto;

    var closebtn = document.createElement('button');
    closebtn.className   = 'ww-btn ww-btn-secondary';
    closebtn.textContent = 'Fechar';
    closebtn.onclick     = function () { backdrop.remove(); };

    box.appendChild(msg);
    if (url) {
      var linkbtn = document.createElement('a');
      linkbtn.href        = url;
      linkbtn.target      = '_blank';
      linkbtn.className   = 'ww-btn ww-btn-primary';
      linkbtn.textContent = 'Saber Mais';
      box.appendChild(linkbtn);
    }
    box.appendChild(closebtn);
    backdrop.appendChild(box);
    document.body.appendChild(backdrop);
  }

  function showNotificacao(texto) {
    var container = document.getElementById('ww-notifications');
    if (!container) return;

    var n = document.createElement('div');
    n.className   = 'ww-notification';
    n.textContent = texto;
    container.appendChild(n);
    setTimeout(function () { n.remove(); }, 5000);
  }

  /* ─────────────────────────────────────────
     Audience simulation
  ───────────────────────────────────────── */
  if (simulacaoAtiva) {
    var nomes = [
      'João', 'Maria', 'Pedro', 'Ana', 'Carlos', 'Fernanda', 'Lucas', 'Juliana',
      'Rodrigo', 'Camila', 'Rafael', 'Beatriz', 'Gustavo', 'Larissa', 'Felipe'
    ];
    var countEl = document.getElementById('ww-watching-count');

    setInterval(function () {
      // Fluctuate count
      var delta = Math.floor(Math.random() * 5) - 2;
      simulacaoCount = Math.max(1, simulacaoCount + delta);
      if (countEl) countEl.textContent = simulacaoCount;

      // Show random notification
      if (Math.random() > 0.6) {
        var nome = nomes[Math.floor(Math.random() * nomes.length)];
        var acao = Math.random() > 0.7 ? 'acabou de comprar 🎉' : 'acabou de entrar 👋';
        showNotificacao(nome + ' ' + acao);
      }
    }, 8000);
  }

  /* ─────────────────────────────────────────
     API helpers
  ───────────────────────────────────────── */
  function trackEvent(evento, dados) {
    fetch(apiUrl + '/analytics/track', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify({
        webinar_id      : webinarId,
        participante_id : participanteId || 0,
        evento          : evento,
        dados           : dados
      })
    }).catch(function () {});
  }

  function updateTempo(tempo) {
    if (!participanteId) return;
    fetch(apiUrl + '/participantes/' + participanteId + '/tempo', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify({ tempo: tempo })
    }).catch(function () {});
  }

  /* ─────────────────────────────────────────
     Get participant ID from session storage
  ───────────────────────────────────────── */
  var stored = sessionStorage.getItem('ww_participante_' + webinarId);
  if (stored) participanteId = parseInt(stored, 10) || 0;

  /* ─────────────────────────────────────────
     Utilities
  ───────────────────────────────────────── */
  function fmtTime(s) {
    var m = Math.floor(s / 60);
    var sec = String(s % 60).padStart(2, '0');
    return m + ':' + sec;
  }

})();
