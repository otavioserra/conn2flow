/**
 * galleries.widget.js — comportamento público do widget de galerias (req-019 / DEC-029, DEC-030).
 *
 * Incluído no site quando uma página renderiza o widget `galleries`. Para cada galeria
 * `.conn2flow-gallery` com mais de um slide, inicializa a lógica de carrossel/slider:
 *   - Lê os parâmetros `data-autoplay`, `data-speed`, `data-loop` do contêiner.
 *   - Navegação suave alterando o `scrollLeft` do `.gallery-slides-wrapper` (jQuery .animate).
 *   - Setas `.gallery-prev` / `.gallery-next` (com wrap/loop quando configurado).
 *   - Pontinhos `.gallery-dot` (sincroniza slide + classe ativa `.gallery-dot-active`).
 *   - Auto-play via setInterval, reiniciado a cada intervenção manual e pausado no hover.
 */
$(document).ready(function () {

    $('.conn2flow-gallery').each(function () {
        initGallery(this);
    });

    function initGallery(el) {
        var $gallery = $(el);
        if ($gallery.data('c2fGalleryReady')) return;     // evita dupla inicialização
        $gallery.data('c2fGalleryReady', true);

        // ===== Configuração de Altura e Margens Dinâmicas
        var height = parseInt($gallery.data('height'), 10);
        if (!isNaN(height) && height >= 1) {
            $gallery.css('height', height + 'px');
        }
        var marginLateral = parseInt($gallery.data('margin-lateral'), 10);
        if (!isNaN(marginLateral)) {
            $gallery.css({
                'margin-left': marginLateral + 'px',
                'margin-right': marginLateral + 'px'
            });
        }

        var $wrapper = $gallery.find('.gallery-slides-wrapper').first();
        if ($wrapper.length === 0) return;

        var $slides = $wrapper.find('.gallery-slide');
        var total = $slides.length;
        if (total <= 1) return;                            // 0/1 slide: nada a controlar

        // ===== Parâmetros (data-* já resolvidos pelo widget renderer).
        var autoplay = ($gallery.data('autoplay') === true || $gallery.data('autoplay') === 'true');
        var loop = !($gallery.data('loop') === false || $gallery.data('loop') === 'false');
        var speed = parseInt($gallery.data('speed'), 10);
        if (!(speed >= 500)) speed = 3000;

        var $dots = $gallery.find('.gallery-dot');
        var current = 0;
        var timer = null;

        function slideOffset(index) {
            var slide = $slides.get(index);
            var first = $slides.get(0);
            if (!slide || !first) return 0;
            return slide.offsetLeft - first.offsetLeft;
        }

        function setActiveDot(index) {
            if ($dots.length === 0) return;
            $dots.removeClass('gallery-dot-active');
            $dots.eq(index).addClass('gallery-dot-active');
        }

        function goTo(index, animate) {
            if (index < 0) index = loop ? total - 1 : 0;
            if (index >= total) index = loop ? 0 : total - 1;
            current = index;

            var left = slideOffset(index);
            if (animate === false) $wrapper.scrollLeft(left);
            else $wrapper.stop(true).animate({ scrollLeft: left }, 350);

            setActiveDot(index);
        }

        function next() { goTo(current + 1); }
        function prev() { goTo(current - 1); }

        function stopAutoplay() {
            if (timer) { clearInterval(timer); timer = null; }
        }
        function startAutoplay() {
            stopAutoplay();
            if (autoplay) timer = setInterval(next, speed);
        }
        // Reinicia a contagem do auto-play após uma intervenção manual.
        function restartAutoplay() { if (autoplay) startAutoplay(); }

        $gallery.on('click', '.gallery-next', function (e) { e.preventDefault(); next(); restartAutoplay(); });
        $gallery.on('click', '.gallery-prev', function (e) { e.preventDefault(); prev(); restartAutoplay(); });
        $gallery.on('click', '.gallery-dot', function (e) {
            e.preventDefault();
            var idx = parseInt($(this).attr('data-index'), 10);
            if (isNaN(idx)) idx = $dots.index(this);
            goTo(idx);
            restartAutoplay();
        });

        // Pausa o auto-play enquanto o cursor está sobre a galeria.
        $gallery.on('mouseenter', stopAutoplay).on('mouseleave', restartAutoplay);

        // Estado inicial (sem animação) e início do auto-play.
        goTo(0, false);
        startAutoplay();
    }
});
