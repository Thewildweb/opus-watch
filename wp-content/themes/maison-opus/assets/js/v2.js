/* v2 skin — homepage banner slider ([data-v2-slider], template-parts/home-v2.php).
   Crossfades every 6s; pauses on hover/focus and in background tabs; no
   autoplay under prefers-reduced-motion. Arrows, swipe and the counter work
   either way. */
(function () {
  'use strict';

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.querySelectorAll('[data-v2-slider]').forEach(function (slider) {
    var slides = slider.querySelectorAll('.v2-slide');
    var count = slider.querySelector('[data-v2-count]');
    var current = 0;
    var timer = null;
    if (slides.length < 2) {
      slider.querySelector('.v2-slider__nav').hidden = true;
      return;
    }

    function show(i) {
      slides[current].classList.remove('is-active');
      slides[current].setAttribute('aria-hidden', 'true');
      slides[current].tabIndex = -1;
      current = (i + slides.length) % slides.length;
      slides[current].classList.add('is-active');
      slides[current].removeAttribute('aria-hidden');
      slides[current].removeAttribute('tabindex');
      count.textContent = (current + 1) + ' / ' + slides.length;
      // Warm the next image so the fade never shows a blank.
      var next = slides[(current + 1) % slides.length].querySelector('img');
      if (next) next.loading = 'eager';
    }

    function stop() { clearInterval(timer); timer = null; }
    function start() {
      if (reduce || timer || slider.offsetParent === null) return; // hidden variant: idle
      timer = setInterval(function () { show(current + 1); }, 6000);
    }
    function restart() { stop(); start(); }

    slider.querySelector('[data-v2-prev]').addEventListener('click', function () { show(current - 1); restart(); });
    slider.querySelector('[data-v2-next]').addEventListener('click', function () { show(current + 1); restart(); });

    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', start);
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
    window.addEventListener('resize', function () { slider.offsetParent === null ? stop() : start(); });

    // Swipe: a horizontal drag of 40px+ changes slide and swallows the click.
    var x0 = null, swiped = false;
    slider.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; swiped = false; }, { passive: true });
    slider.addEventListener('touchend', function (e) {
      if (x0 === null) return;
      var dx = e.changedTouches[0].clientX - x0;
      x0 = null;
      if (Math.abs(dx) > 40) { swiped = true; show(current + (dx < 0 ? 1 : -1)); restart(); }
    });
    slider.addEventListener('click', function (e) {
      if (swiped && e.target.closest('.v2-slide')) { e.preventDefault(); swiped = false; }
    });

    show(0);
    start();
  });
})();
