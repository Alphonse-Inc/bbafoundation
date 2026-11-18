(function () {
  var configNode = document.getElementById('bbaf-config');
  var config = {};
  if (configNode) {
    try { config = JSON.parse(configNode.textContent || '{}'); } catch (error) { config = {}; }
  }

  var root = document.documentElement;
  var storedTheme = localStorage.getItem('bbaf-theme');
  if (storedTheme) {
    root.setAttribute('data-theme', storedTheme);
  }
  document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      localStorage.setItem('bbaf-theme', next);
    });
  });

  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  var slides = Array.prototype.slice.call(document.querySelectorAll('[data-slide]'));
  var dots = Array.prototype.slice.call(document.querySelectorAll('[data-slide-dot]'));
  var slideIndex = 0;
  function showSlide(index) {
    if (!slides.length) return;
    slideIndex = (index + slides.length) % slides.length;
    slides.forEach(function (slide, i) {
      slide.classList.toggle('is-active', i === slideIndex);
      slide.hidden = i !== slideIndex;
    });
    dots.forEach(function (dot, i) {
      dot.setAttribute('aria-selected', i === slideIndex ? 'true' : 'false');
    });
  }
  if (slides.length) {
    showSlide(0);
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduced) {
      setInterval(function () { showSlide(slideIndex + 1); }, 7000);
    }
    dots.forEach(function (dot, i) {
      dot.addEventListener('click', function () { showSlide(i); });
    });
  }

  document.querySelectorAll('[data-count]').forEach(function (node) {
    var target = parseInt(node.getAttribute('data-count') || '0', 10);
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      node.textContent = String(target);
      return;
    }
    var start = 0;
    var step = Math.max(1, Math.round(target / 24));
    var timer = setInterval(function () {
      start = Math.min(target, start + step);
      node.textContent = String(start);
      if (start >= target) clearInterval(timer);
    }, 40);
  });

  var countdown = document.querySelector('[data-countdown]');
  if (countdown) {
    var deadline = Date.parse(countdown.getAttribute('data-countdown') || '');
    function tick() {
      if (!deadline) return;
      var diff = deadline - Date.now();
      if (diff <= 0) {
        countdown.textContent = 'Applications are closed';
        return;
      }
      var days = Math.floor(diff / 86400000);
      var hours = Math.floor((diff % 86400000) / 3600000);
      var minutes = Math.floor((diff % 3600000) / 60000);
      countdown.textContent = days + ' days ' + hours + ' hours ' + minutes + ' minutes';
    }
    tick();
    setInterval(tick, 30000);
  }

  var faqSearch = document.querySelector('[data-faq-search]');
  if (faqSearch) {
    faqSearch.addEventListener('input', function () {
      var query = faqSearch.value.toLowerCase();
      document.querySelectorAll('[data-faq]').forEach(function (item) {
        var text = item.textContent.toLowerCase();
        item.hidden = query !== '' && text.indexOf(query) === -1;
      });
    });
  }

  var words = document.querySelector('[data-words]');
  var wordOut = document.querySelector('[data-wordcount]');
  if (words && wordOut) {
    var cap = parseInt(words.getAttribute('data-words') || '400', 10);
    function countWords() {
      var value = words.value.trim().replace(/\s+/g, ' ');
      var total = value === '' ? 0 : value.split(' ').length;
      wordOut.textContent = total + ' / ' + cap;
      wordOut.setAttribute('data-over', total > cap ? '1' : '0');
    }
    words.addEventListener('input', countWords);
    countWords();
  }

  var message = document.querySelector('[data-chars]');
  var charOut = document.querySelector('[data-charcount]');
  if (message && charOut) {
    function countChars() {
      if (message.value.length > 180) message.value = message.value.slice(0, 180);
      charOut.textContent = message.value.length + '/180';
    }
    message.addEventListener('input', countChars);
    countChars();
  }

  document.querySelectorAll('[data-rich]').forEach(function (box) {
    var area = box.querySelector('textarea');
    box.querySelectorAll('[data-cmd]').forEach(function (button) {
      button.addEventListener('click', function () {
        var cmd = button.getAttribute('data-cmd');
        var start = area.selectionStart;
        var end = area.selectionEnd;
        var selected = area.value.slice(start, end) || 'text';
        var wrap = { strong: ['<strong>', '</strong>'], em: ['<em>', '</em>'], h2: ['<h2>', '</h2>'], link: ['<a href="/">', '</a>'], li: ['<ul><li>', '</li></ul>'], p: ['<p>', '</p>'] };
        var pair = wrap[cmd] || ['', ''];
        area.setRangeText(pair[0] + selected + pair[1], start, end, 'end');
        area.focus();
      });
    });
  });

  var form = document.querySelector('[data-autosave]');
  if (form) {
    var status = document.querySelector('[data-save-status]');
    var timer = null;
    function saveDraft() {
      var data = new FormData(form);
      if (status) status.textContent = 'Saving…';
      fetch(form.getAttribute('data-autosave'), {
        method: 'POST',
        headers: { 'X-CSRF-Token': config.csrf || '', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: data
      }).then(function (response) { return response.json(); }).then(function (payload) {
        if (status) status.textContent = payload.ok ? 'Draft saved' : (payload.error || 'Could not save');
      }).catch(function () {
        if (status) status.textContent = 'Could not save';
      });
    }
    form.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(saveDraft, 1200);
    });
  }

  var fileInput = document.querySelector('[data-passport-input]');
  var preview = document.querySelector('[data-preview]');
  var kindSelect = document.querySelector('[data-passport-kind]');
  if (fileInput && preview) {
    fileInput.addEventListener('change', function () {
      var file = fileInput.files && fileInput.files[0];
      if (!file) return;
      if (!kindSelect || kindSelect.value !== 'passport') return;
      var reader = new FileReader();
      reader.onload = function () {
        var image = new Image();
        image.onload = function () {
          var size = Math.min(image.width, image.height);
          var canvas = document.createElement('canvas');
          canvas.width = 600;
          canvas.height = 600;
          var sx = (image.width - size) / 2;
          var sy = (image.height - size) / 2;
          canvas.getContext('2d').drawImage(image, sx, sy, size, size, 0, 0, 600, 600);
          preview.src = canvas.toDataURL('image/jpeg', 0.85);
          preview.hidden = false;
          canvas.toBlob(function (blob) {
            if (!blob) return;
            var cropped = new File([blob], 'passport.jpg', { type: 'image/jpeg' });
            var transfer = new DataTransfer();
            transfer.items.add(cropped);
            fileInput.files = transfer.files;
          }, 'image/jpeg', 0.85);
        };
        image.src = reader.result;
      };
      reader.readAsDataURL(file);
    });
  }

  var widget = document.querySelector('[data-eligibility]');
  if (widget) {
    widget.addEventListener('submit', function (event) {
      event.preventDefault();
      var data = new FormData(widget);
      var indigene = data.get('indigene') === 'yes';
      var enrolled = data.get('enrolled') === 'yes';
      var grades = data.get('grades') === 'yes';
      var out = widget.querySelector('[data-eligibility-result]');
      if (indigene && enrolled && grades) {
        out.textContent = 'From what you have entered, you appear to meet the published guidelines. This check does not admit you and it does not replace the full application.';
      } else {
        out.textContent = 'From what you have entered, one or more published guidelines may not be met. You can still read the About page. This check is information only.';
      }
    });
  }

  var poll = document.querySelector('[data-poll]');
  if (poll && config.poll) {
    function loadNotes() {
      if (document.hidden) return;
      fetch(config.poll, { headers: { 'Accept': 'application/json' } })
        .then(function (response) { return response.json(); })
        .then(function (payload) {
          var badge = document.querySelector('[data-note-count]');
          if (badge) {
            badge.hidden = !payload.count;
            badge.textContent = String(payload.count || 0);
          }
        }).catch(function () {});
    }
    loadNotes();
    setInterval(loadNotes, 45000);
    document.addEventListener('visibilitychange', loadNotes);
  }

  var top = document.querySelector('[data-back-top]');
  if (top) {
    top.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }

  var cookie = document.querySelector('[data-cookie]');
  if (cookie && !localStorage.getItem('bbaf-cookie')) {
    cookie.hidden = false;
    cookie.querySelectorAll('[data-cookie-choice]').forEach(function (button) {
      button.addEventListener('click', function () {
        localStorage.setItem('bbaf-cookie', button.getAttribute('data-cookie-choice'));
        cookie.hidden = true;
        if (button.getAttribute('data-cookie-choice') === 'accept') loadAnalytics();
      });
    });
  } else if (localStorage.getItem('bbaf-cookie') === 'accept') {
    loadAnalytics();
  }

  function loadAnalytics() {
    if (!config.ga || document.getElementById('bbaf-ga')) return;
    var script = document.createElement('script');
    script.id = 'bbaf-ga';
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(config.ga);
    document.head.appendChild(script);
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', config.ga);
  }

  if (location.hash === '#apply') {
    location.replace((config.apply || '/apply'));
  }

  document.querySelectorAll('[data-confirm]').forEach(function (node) {
    node.addEventListener('submit', function (event) {
      if (!window.confirm(node.getAttribute('data-confirm'))) event.preventDefault();
    });
  });
})();
